<?php

namespace App\Support;

class DeliverableMail
{
    public static function configured(): bool
    {
        $transport = config('mail.mailers.'.config('mail.default').'.transport');

        return in_array($transport, ['smtp', 'sendmail', 'ses', 'postmark', 'resend'], true);
    }
}
