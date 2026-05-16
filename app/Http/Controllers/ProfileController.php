<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function editPin(Request $request): View
    {
        return view('profile.pin-edit', ['user' => $request->user()]);
    }

    public function updatePin(Request $request): RedirectResponse
    {
        $request->validate([
            'pin'                  => ['required', 'digits_between:4,6', 'confirmed'],
            'pin_confirmation'     => ['required'],
            'current_password'     => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $user->update([
            'pin'                => Hash::make($request->pin),
            'pin_reset_required' => false,
        ]);

        AuditLogger::crud('profile.pin_updated', 'user', $user->id, $user->name);

        return back()->with('status', 'pin-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
