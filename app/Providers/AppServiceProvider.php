<?php

namespace App\Providers;

use App\Models\Firm;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->useFirmMailAccount();
    }

    /**
     * Send email through the SMTP account set in Company › Firm › Mail setup,
     * when there is one (cached encrypted - it holds the SMTP password;
     * cleared when the firm is saved).
     */
    private function useFirmMailAccount(): void
    {
        if ($this->app->runningUnitTests()) {
            return;
        }

        try {
            $mail = Cache::rememberForever('firm-mail', function () {
                $firm = Firm::query()->first();
                $smtp = $firm?->mailConfig();

                return $smtp ? Crypt::encrypt(['smtp' => $smtp, 'from' => ['address' => $firm->mail_username, 'name' => $firm->establishment_name]]) : false;
            });
            $mail = $mail ? Crypt::decrypt($mail) : null;
        } catch (Throwable) {
            return; // e.g. before the firms table exists
        }

        if ($mail) {
            config(['mail.default' => 'smtp', 'mail.mailers.smtp' => $mail['smtp'], 'mail.from' => $mail['from']]);
        }
    }
}
