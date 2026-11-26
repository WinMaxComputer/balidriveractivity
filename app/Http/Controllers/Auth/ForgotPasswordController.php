<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\DeliverableMail;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails {
        sendResetLinkEmail as private sendConfiguredResetLinkEmail;
    }

    public function sendResetLinkEmail(Request $request)
    {
        if (!DeliverableMail::configured()) {
            return back()->withErrors(['email' => 'Password reset is unavailable until email delivery is configured.']);
        }

        return $this->sendConfiguredResetLinkEmail($request);
    }
}
