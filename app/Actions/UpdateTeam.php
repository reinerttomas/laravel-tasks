<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Team;
use Illuminate\Support\Facades\DB;

final readonly class UpdateTeam
{
    /**
     * Rename the team.
     */
    public function handle(Team $team, string $name): Team
    {
        return DB::transaction(function () use ($team, $name): Team {
            $team = Team::query()->whereKey($team->id)->lockForUpdate()->firstOrFail();

            $team->update(['name' => $name]);

            return $team;
        });
    }
}
