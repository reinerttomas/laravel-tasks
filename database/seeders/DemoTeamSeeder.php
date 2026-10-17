<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\ArchiveProject;
use App\Actions\CreateComment;
use App\Actions\CreateProject;
use App\Actions\CreateTask;
use App\Actions\CreateTeam;
use App\Actions\SwitchTeam;
use App\Enums\ProjectColor;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * @phpstan-type DemoComment array{0: 'owner'|'admin'|'member', 1: string}
 * @phpstan-type DemoTask array{title: string, status: TaskStatus, priority: TaskPriority, assignee: 'owner'|'admin'|'member'|null, dueInDays: int|null, description: string|null, comments: list<DemoComment>}
 * @phpstan-type DemoProject array{name: string, identifier: string, color: ProjectColor, description: string, archived: bool, tasks: list<DemoTask>}
 */
final class DemoTeamSeeder extends Seeder
{
    /**
     * Seed the "Acme Inc." team: one user per role, three projects (one archived) and their tasks and comments.
     */
    public function run(
        CreateTeam $createTeam,
        SwitchTeam $switchTeam,
        CreateProject $createProject,
        ArchiveProject $archiveProject,
        CreateTask $createTask,
        CreateComment $createComment,
    ): void {
        $users = [
            'owner' => User::factory()->create(['name' => 'Olivia Owens', 'email' => 'owner@example.com']),
            'admin' => User::factory()->create(['name' => 'Adam Novak', 'email' => 'admin@example.com']),
            'member' => User::factory()->create(['name' => 'Maya Lopez', 'email' => 'member@example.com']),
            'viewer' => User::factory()->create(['name' => 'Victor Chen', 'email' => 'viewer@example.com']),
        ];

        $team = $createTeam->handle($users['owner'], 'Acme Inc.');

        $this->addMember($team, $users['admin'], TeamRole::Admin, $switchTeam);
        $this->addMember($team, $users['member'], TeamRole::Member, $switchTeam);
        $this->addMember($team, $users['viewer'], TeamRole::Viewer, $switchTeam);

        $authors = [$users['owner'], $users['admin'], $users['member']];

        foreach ($this->projects() as $projectIndex => $demoProject) {
            $project = $createProject->handle($team, $demoProject['name'], $demoProject['identifier'], $demoProject['color'], $demoProject['description']);
            $this->backdate($project, now()->subDays(90 - $projectIndex * 20));

            foreach ($demoProject['tasks'] as $taskIndex => $demoTask) {
                $createdAt = now()->subDays(60 - $projectIndex * 5 - $taskIndex)->setTime(9 + $taskIndex % 8, 15);

                $task = $createTask->handle($project, $authors[$taskIndex % 3], $demoTask['title'], [
                    'description' => $demoTask['description'],
                    'status' => $demoTask['status'],
                    'priority' => $demoTask['priority'],
                    'assignee_id' => $demoTask['assignee'] === null ? null : $users[$demoTask['assignee']]->id,
                    'due_date' => $demoTask['dueInDays'] === null ? null : today()->addDays($demoTask['dueInDays']),
                ]);
                $this->backdate($task, $createdAt);

                foreach ($demoTask['comments'] as $commentIndex => [$author, $body]) {
                    $comment = $createComment->handle($task, $users[$author], $body);
                    $this->backdate($comment, $createdAt->addHours(3 + $commentIndex * 20));
                }
            }

            if ($demoProject['archived']) {
                $archiveProject->handle($project);
            }
        }
    }

    private function addMember(Team $team, User $user, TeamRole $role, SwitchTeam $switchTeam): void
    {
        $team->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        $switchTeam->handle($user, $team);
    }

    /**
     * Spread the demo history over past weeks instead of stamping everything with the seeding time.
     */
    private function backdate(Model $model, CarbonImmutable $at): void
    {
        $model->forceFill(['created_at' => $at, 'updated_at' => $at])->save();
    }

    /**
     * @return list<DemoProject>
     */
    private function projects(): array
    {
        return [
            [
                'name' => 'Website Redesign',
                'identifier' => 'WEB',
                'color' => ProjectColor::Blue,
                'description' => 'Rebuild the marketing site on the new design system, with faster pages and a self-serve pricing flow.',
                'archived' => false,
                'tasks' => [
                    $this->task('Audit the current site for broken links', TaskStatus::Done, TaskPriority::Medium, 'member', comments: [
                        ['member', 'Found 37 broken links, mostly old blog posts. Fixed or redirected all of them.'],
                        ['owner', 'Great work, thanks!'],
                    ]),
                    $this->task('Define the new information architecture', TaskStatus::Done, TaskPriority::High, 'admin', description: "Group pages into **Product**, **Solutions**, **Pricing** and **Resources**.\n\n- Merge the two FAQ pages\n- Move case studies under Resources"),
                    $this->task('Design the homepage hero', TaskStatus::Done, TaskPriority::High, 'member', comments: [
                        ['admin', 'Can we try a version with the product screenshot on the right?'],
                        ['member', 'Added it as variant B in Figma.'],
                        ['owner', 'Variant B it is.'],
                    ]),
                    $this->task('Set up the design tokens package', TaskStatus::Done, TaskPriority::Medium, 'admin'),
                    $this->task('Build the navigation menu', TaskStatus::InProgress, TaskPriority::High, 'member', dueInDays: 2, description: "Desktop mega menu plus a mobile drawer.\n\n- [x] Desktop layout\n- [ ] Keyboard navigation\n- [ ] Mobile drawer", comments: [
                        ['member', 'Desktop version is up on the preview branch.'],
                    ]),
                    $this->task('Migrate blog posts to the new CMS', TaskStatus::InProgress, TaskPriority::Medium, 'admin', dueInDays: -3, comments: [
                        ['admin', 'About half done. The import script chokes on embedded videos.'],
                        ['member', 'I can take the video embeds if that helps.'],
                    ]),
                    $this->task('Write copy for the pricing page', TaskStatus::InProgress, TaskPriority::Urgent, 'owner', dueInDays: -1, comments: [
                        ['admin', 'Legal needs to review the refund wording before we publish.'],
                    ]),
                    $this->task('Add structured data for search engines', TaskStatus::Todo, TaskPriority::Low, 'admin', dueInDays: 14),
                    $this->task('Fix layout shift on product pages', TaskStatus::Todo, TaskPriority::High, 'member', dueInDays: -2, description: 'Images load without dimensions and push the content down. Lighthouse reports a CLS of 0.31.'),
                    $this->task('Create the case study template', TaskStatus::Todo, TaskPriority::Medium, 'member', dueInDays: 7),
                    $this->task('Set up redirects from old URLs', TaskStatus::Todo, TaskPriority::Urgent, 'admin', dueInDays: 4, comments: [
                        ['owner', 'This has to ship together with the launch or we lose our search ranking.'],
                    ]),
                    $this->task('Add a cookie consent banner', TaskStatus::Todo, TaskPriority::High, 'owner', dueInDays: 10),
                    $this->task('Integrate the newsletter signup form', TaskStatus::Todo, TaskPriority::None, null),
                    $this->task('Run accessibility testing with a screen reader', TaskStatus::Backlog, TaskPriority::Medium, null),
                    $this->task('Translate the site into German', TaskStatus::Backlog, TaskPriority::Low, null, description: 'Waiting on the localisation budget for next quarter.'),
                    $this->task('Add dark mode', TaskStatus::Backlog, TaskPriority::None, null, comments: [
                        ['member', 'Nice to have. The tokens package already supports it.'],
                    ]),
                    $this->task('Explore an interactive product tour', TaskStatus::Backlog, TaskPriority::Low, 'member'),
                    $this->task('Add a careers page', TaskStatus::Backlog, TaskPriority::None, null),
                    $this->task('Replace the chat widget', TaskStatus::Canceled, TaskPriority::Low, 'admin', comments: [
                        ['owner', 'Support decided to keep the current widget, closing this.'],
                    ]),
                    $this->task('Set up performance budgets in CI', TaskStatus::Todo, TaskPriority::Medium, 'admin', dueInDays: 21),
                    $this->task('Optimise hero images', TaskStatus::Done, TaskPriority::Medium, 'member'),
                    $this->task('Link the status page from the footer', TaskStatus::Todo, TaskPriority::Low, 'owner', dueInDays: 5),
                ],
            ],
            [
                'name' => 'Mobile App',
                'identifier' => 'MOB',
                'color' => ProjectColor::Violet,
                'description' => 'Native iOS and Android app for managing tasks on the go, starting with offline support and push notifications.',
                'archived' => false,
                'tasks' => [
                    $this->task('Choose a cross-platform framework', TaskStatus::Done, TaskPriority::High, 'owner', comments: [
                        ['admin', 'React Native fits best, we can share the API client with the web app.'],
                        ['member', 'Agreed, and the team already knows TypeScript.'],
                    ]),
                    $this->task('Set up the app build pipeline', TaskStatus::Done, TaskPriority::Medium, 'admin'),
                    $this->task('Implement login with passkeys', TaskStatus::InProgress, TaskPriority::Urgent, 'admin', dueInDays: -4, description: 'Use the platform passkey APIs and fall back to email and password.', comments: [
                        ['admin', 'iOS works. Android needs the asset links file on the domain.'],
                        ['owner', 'I will ask ops to deploy the asset links file today.'],
                    ]),
                    $this->task('Build the task list screen', TaskStatus::InProgress, TaskPriority::High, 'member', dueInDays: 3),
                    $this->task('Cache tasks for offline use', TaskStatus::InProgress, TaskPriority::Medium, 'owner', dueInDays: 6, comments: [
                        ['member', 'Should we sync comments too, or only tasks for now?'],
                        ['owner', 'Only tasks for the first release.'],
                    ]),
                    $this->task('Design the app icon', TaskStatus::Done, TaskPriority::Low, 'member'),
                    $this->task('Send push notifications for assigned tasks', TaskStatus::Todo, TaskPriority::High, 'admin', dueInDays: 12),
                    $this->task('Handle deep links to tasks', TaskStatus::Todo, TaskPriority::Medium, 'member', dueInDays: -5),
                    $this->task('Add pull to refresh', TaskStatus::Todo, TaskPriority::Low, 'member', dueInDays: 9),
                    $this->task('Write the App Store listing', TaskStatus::Todo, TaskPriority::Medium, 'owner', dueInDays: 20),
                    $this->task('Crash when rotating on the task detail screen', TaskStatus::Todo, TaskPriority::Urgent, 'member', dueInDays: 1, description: "Steps to reproduce:\n\n1. Open any task\n2. Rotate the device\n\nThe app crashes with `IllegalStateException` on Android 14.", comments: [
                        ['admin', 'Reproduced on a Pixel 8.'],
                        ['member', 'Looks like the editor state is not restored. Looking into it.'],
                    ]),
                    $this->task('Support tablets with a two-column layout', TaskStatus::Backlog, TaskPriority::Low, null),
                    $this->task('Add home screen widgets', TaskStatus::Backlog, TaskPriority::None, null),
                    $this->task('Add biometric unlock', TaskStatus::Backlog, TaskPriority::Medium, null),
                    $this->task('Build an Apple Watch app', TaskStatus::Canceled, TaskPriority::None, null, comments: [
                        ['owner', 'Out of scope for this year.'],
                    ]),
                    $this->task('Set up crash reporting', TaskStatus::Done, TaskPriority::High, 'admin'),
                    $this->task('Run a beta with ten customers', TaskStatus::Backlog, TaskPriority::High, 'owner'),
                    $this->task('Add voice input for task titles', TaskStatus::Canceled, TaskPriority::Low, 'member'),
                ],
            ],
            [
                'name' => 'Legacy Billing Migration',
                'identifier' => 'BILL',
                'color' => ProjectColor::Amber,
                'description' => 'Move all customers from the legacy billing system to the new payment provider.',
                'archived' => true,
                'tasks' => [
                    $this->task('Export customer records from the legacy system', TaskStatus::Done, TaskPriority::High, 'admin'),
                    $this->task('Map legacy plans to new price IDs', TaskStatus::Done, TaskPriority::High, 'owner', comments: [
                        ['admin', 'Three legacy plans have no equivalent. Grandfather them?'],
                        ['owner', 'Yes, keep their price until renewal.'],
                    ]),
                    $this->task('Migrate stored payment methods', TaskStatus::Done, TaskPriority::Urgent, 'admin'),
                    $this->task('Email customers about the new invoices', TaskStatus::Done, TaskPriority::Medium, 'member'),
                    $this->task('Reconcile the first month of invoices', TaskStatus::Done, TaskPriority::High, 'owner', comments: [
                        ['member', 'Two invoices were off by a cent due to rounding, corrected manually.'],
                    ]),
                    $this->task('Decommission the legacy billing server', TaskStatus::Done, TaskPriority::Medium, 'admin'),
                    $this->task('Support invoices in multiple currencies', TaskStatus::Canceled, TaskPriority::Low, null, comments: [
                        ['owner', 'Moved to next year\'s roadmap.'],
                    ]),
                    $this->task('Archive old invoices as PDFs', TaskStatus::Done, TaskPriority::Low, 'member'),
                    $this->task('Update the billing FAQ', TaskStatus::Done, TaskPriority::None, 'member'),
                    $this->task('Build a dunning email sequence', TaskStatus::Canceled, TaskPriority::Medium, 'admin'),
                ],
            ],
        ];
    }

    /**
     * @param  'owner'|'admin'|'member'|null  $assignee
     * @param  list<DemoComment>  $comments
     * @return DemoTask
     */
    private function task(
        string $title,
        TaskStatus $status,
        TaskPriority $priority,
        ?string $assignee,
        ?int $dueInDays = null,
        ?string $description = null,
        array $comments = [],
    ): array {
        return [
            'title' => $title,
            'status' => $status,
            'priority' => $priority,
            'assignee' => $assignee,
            'dueInDays' => $dueInDays,
            'description' => $description,
            'comments' => $comments,
        ];
    }
}
