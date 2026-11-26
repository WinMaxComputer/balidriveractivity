<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\CustomerPasswordSetup;
use App\Support\DeliverableMail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class CustomerAuthController extends Controller
{
    public function loginForm()
    {
        return $this->privateView('pages.customer-login', ['mailAvailable' => DeliverableMail::configured()]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);
        $user = User::whereRaw('LOWER(email) = ?', [mb_strtolower($data['email'])])->first();
        if (!$user || !$user->has_local_password
            || !Auth::attempt(['email' => $user->email, 'password' => $data['password']])) {
            return back()->withErrors(['email' => 'Invalid credentials.'])
                ->withInput($request->only('email'));
        }
        $request->session()->regenerate();

        if ($user->role === 'admin') {
            return redirect()->intended(route('customer.dashboard'));
        }

        $intended = $request->session()->pull('url.intended');
        if (is_string($intended) && Str::startsWith($intended, route('customer.verify.notice').'/')) {
            return redirect()->to($intended);
        }

        return redirect()->route('customer.dashboard');
    }

    public function verificationNotice(Request $request)
    {
        if ($request->user()->email_verified_at) {
            return redirect()->route('customer.dashboard');
        }

        return $this->privateView('pages.customer-verify-email', ['mailAvailable' => DeliverableMail::configured()]);
    }

    public function sendVerification(Request $request)
    {
        if ($request->user()->email_verified_at) {
            return redirect()->route('customer.dashboard');
        }
        if (!DeliverableMail::configured()) {
            return redirect()->route('customer.verify.notice')
                ->withErrors(['email' => 'Email verification is unavailable until email delivery is configured. Please contact us.']);
        }

        $request->user()->sendEmailVerificationNotification();

        return redirect()->route('customer.verify.notice')
            ->with('customer_message', 'A verification link has been sent to your email address.');
    }

    public function verifyEmail(Request $request, string $id, string $hash)
    {
        $user = $request->user();
        abort_unless(hash_equals((string) $user->getKey(), $id)
            && hash_equals(sha1($user->getEmailForVerification()), $hash), 403);
        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return redirect()->route('customer.dashboard')
            ->with('customer_message', 'Email verified.')
            ->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function requestSetup(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        if (!DeliverableMail::configured()) {
            return redirect()->route('customer.login')
                ->withErrors(['email' => 'Password setup is unavailable until email delivery is configured. Please contact us.']);
        }

        $email = mb_strtolower($data['email']);
        $reservation = DB::table('reservations')->whereRaw('LOWER(guest_email) = ?', [$email])->first();
        if ($reservation) {
            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
            if (!$user) {
                $user = User::firstOrCreate(['email' => $email], [
                    'name' => $reservation->guest_name ?: $email,
                    'password' => Hash::make(Str::random(40)),
                ]);
                if ($user->wasRecentlyCreated) {
                    $user->has_local_password = false;
                    $user->save();
                }
            }
            if (in_array($user->role, [null, 'user', 'customer'], true)) {
                $result = Password::sendResetLink(['email' => $user->email], function ($user, $token) {
                    $user->notify(new CustomerPasswordSetup($token));
                });
                if (!in_array($result, [Password::RESET_LINK_SENT, Password::RESET_THROTTLED], true)) {
                    throw new \RuntimeException('Unable to initiate customer password setup.');
                }
            }
        }

        return redirect()->route('customer.login')
            ->with('customer_message', 'If this email has a booking, a password setup link has been sent.');
    }

    public function setupForm(Request $request, string $token)
    {
        return $this->privateView('pages.customer-password-setup', ['token' => $token, 'email' => $request->query('email', '')]);
    }

    public function setupPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'token' => ['required', 'string', 'size:64'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $result = DB::transaction(function () use ($data) {
            DB::table('password_reset_tokens')->where('email', $data['email'])->lockForUpdate()->first();
            return Password::reset($data, function (User $user, string $password) {
                $user->password = Hash::make($password);
                $user->has_local_password = true;
                $user->email_verified_at = $user->email_verified_at ?? now();
                $user->setRememberToken(Str::random(60));
                $user->save();
            });
        });

        if ($result !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'This link is invalid or has expired. Request a new link.']);
        }

        return redirect()->route('customer.login')->with('customer_message', 'Password saved. You can now sign in.');
    }

    public function changePassword(Request $request)
    {
        abort_unless($request->user()->email_verified_at, 403);
        $rules = [
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ];
        if ($request->user()->has_local_password !== false) {
            $rules['current_password'] = ['required', 'current_password'];
        }
        $data = $request->validate($rules);
        $request->user()->forceFill([
            'password' => Hash::make($data['password']),
            'has_local_password' => true,
        ])->setRememberToken(Str::random(60));
        $request->user()->save();

        return redirect()->route('customer.dashboard')->with('customer_message', 'Password updated.');
    }

    private function privateView(string $view, array $data = [])
    {
        return response()->view($view, $data)
            ->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }

}
