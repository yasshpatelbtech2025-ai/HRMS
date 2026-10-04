<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * The password reset email, sent from the queue so "Email password reset
 * link" doesn't wait on the mail server.
 */
class ResetPasswordQueued extends ResetPassword implements ShouldQueue
{
    use Queueable;
}
