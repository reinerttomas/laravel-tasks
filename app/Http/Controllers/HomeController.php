<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Responses\Concerns\RedirectsToCurrentTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class HomeController
{
    use RedirectsToCurrentTeam;

    /**
     * Send the user to their current team's dashboard; guests are sent to login by the auth middleware.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        return to_route('dashboard', ['current_team' => $this->currentTeam($request)]);
    }
}
