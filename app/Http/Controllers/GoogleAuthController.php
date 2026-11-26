<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use GuzzleHttp\Exception\GuzzleException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Illuminate\Http\Request;

class GoogleAuthController extends Controller
{
    //
    /**
     * Redirect the user to Google’s OAuth page.
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the callback from Google.
     */
    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException | GuzzleException $exception) {
            report($exception);
            return redirect()->route('customer.login')->withErrors(['email' => 'Google authentication failed. Please try again.']);
        }

        if (($googleUser->user['email_verified'] ?? false) !== true
            || !is_string($googleUser->email) || strlen($googleUser->email) > 255
            || !filter_var($googleUser->email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->route('customer.login')->withErrors(['email' => 'Google did not provide a verified email address.']);
        }
        $email = mb_strtolower($googleUser->email);
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if (!$user) {
            $user = User::firstOrCreate(['email' => $email], [
                'name' => mb_substr($googleUser->name ?: $email, 0, 255),
                'password' => Hash::make(Str::random(40)),
            ]);
            if ($user->wasRecentlyCreated) {
                $user->has_local_password = false;
            }
        }
        if (!in_array($user->role, [null, 'user', 'customer'], true)) {
            return redirect()->route('customer.login')->withErrors(['email' => 'This account must use its existing sign-in method.']);
        }
        $user->email_verified_at = $user->email_verified_at ?? now();
        $user->save();
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('customer.dashboard');
    }
}
