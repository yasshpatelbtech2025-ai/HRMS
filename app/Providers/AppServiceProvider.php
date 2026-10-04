<?php

namespace App\Providers;

use App\Models\Firm;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
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

        // N+1 guard: outside production a relation loaded lazily in a loop throws,
        // so missing eager loads show up in development and the tests.
        Model::preventLazyLoading(! $this->app->isProduction());

        // Roles (config/access.php): admins manage users and companies and can delete; users cannot.
        Gate::define('admin', fn (User $user) => $user->isAdmin());
        Gate::define('delete-records', fn (User $user) => $user->isAdmin());

        $this->defineRateLimits();

        // Sign-ins and sign-outs in the activity log.
        Event::listen(Login::class, fn (Login $e) => Activity::record('signed-in', userId: $e->user->getAuthIdentifier()));
        Event::listen(Logout::class, fn (Logout $e) => $e->user && Activity::record('signed-out', userId: $e->user->getAuthIdentifier()));
        Event::listen(Failed::class, fn (Failed $e) => Activity::record('sign-in-failed', description: (string) ($e->credentials['email'] ?? ''), userId: $e->user?->getAuthIdentifier()));
        Event::listen(Lockout::class, fn (Lockout $e) => Activity::record('locked-out', description: (string) $e->request->input('email')));
    }

    /**
     * Request limits (429 "Too many requests" beyond them). Sign-in also
     * locks one email after 5 wrong passwords (LoginRequest).
     */
    private function defineRateLimits(): void
    {
        // Many emails tried from one address (credential stuffing).
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(20)->by('login|'.$request->ip()));
        // Forgot / reset / confirm / change password.
        RateLimiter::for('password', fn (Request $request) => Limit::perMinute(6)->by('password|'.$request->ip()));

        // Signed-in use, per account: generous for browsing, tighter for saves,
        // tightest for exports and PDFs (the heaviest requests).
        RateLimiter::for('app', function (Request $request) {
            $key = $request->user()?->id ?? $request->ip();

            if (Str::is(['*export*', '*pdf*', '*payslip*', '*template*', '*download*'], (string) $request->route()?->getName())) {
                return Limit::perMinute(30)->by("exports|{$key}");
            }

            return $request->isMethodSafe()
                ? Limit::perMinute(300)->by("reads|{$key}")
                : Limit::perMinute(60)->by("writes|{$key}");
        });
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
