<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Responses\Concerns\RedirectsToCurrentTeam;
use Laravel\Fortify\Fortify;
use Spatie\LoginLink\Http\Controllers\LoginLinkController as BaseLoginLinkController;
use Spatie\LoginLink\Http\Requests\LoginLinkRequest;

/**
 * Local-only one-click login; lands on the current team's dashboard like a regular login.
 */
final class LoginLinkController extends BaseLoginLinkController
{
    use RedirectsToCurrentTeam;

    protected function getRedirectUrl(LoginLinkRequest $request): string
    {
        return redirect()->intended($this->redirectPathForCurrentTeam($request, Fortify::redirects('login')))->getTargetUrl();
    }
}
