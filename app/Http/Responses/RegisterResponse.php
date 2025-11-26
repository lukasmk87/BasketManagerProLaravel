<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $user = $request->user();

        // Super Admins and Admins skip onboarding
        if ($user && ($user->hasRole('super_admin') || $user->hasRole('admin'))) {
            return $request->wantsJson()
                ? new JsonResponse('', 201)
                : redirect()->intended(route('dashboard'));
        }

        // All other users go to onboarding
        return $request->wantsJson()
            ? new JsonResponse('', 201)
            : redirect()->route('onboarding.index');
    }
}
