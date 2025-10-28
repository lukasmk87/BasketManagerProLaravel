<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserPreferencesController extends Controller
{
    /**
     * Update the user's locale preference.
     */
    public function updateLocale(Request $request)
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(['de', 'en'])],
        ]);

        $user = Auth::user();
        $user->update([
            'locale' => $validated['locale'],
        ]);

        return back();
    }
}
