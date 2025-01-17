<?php namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Thujohn\Rss\Rss;
use Jobs, Session, File, Carbon, Lang, Validator, Blade, App, DB;

class AppServiceProvider extends ServiceProvider {

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        /*
        |--------------------------------------------------------------------------
        | Custom Validation Rules
        |--------------------------------------------------------------------------
        |
        | This is the right place for custom validators.
        |
        */

        Validator::extend('alpha_spaces', function($attribute, $value)
        {
            return preg_match('/^[\pL\s]+$/u', $value);
        });

        /*
        |--------------------------------------------------------------------------
        | Blade Extensions
        |--------------------------------------------------------------------------
        |
        | This is the right place to setup blade extensions
        | that do not belong to modules.
        |
        */

        /*
         * Helper. Renders a widget.
         */
        Blade::directive('widget', function($expression) {
            return '<?php echo HTML::widget'.$expression.'; ?>';
        });

        /*
        |--------------------------------------------------------------------------
        | Visitor Statistics
        |--------------------------------------------------------------------------
        |
        | Updates the global visitor statistics.
        |
        */

        if (! App::runningInConsole() and installed()) {
            $today          = time();
            $isNewVisitor   = (Session::get('ipLogged') == null);

            if (Session::get('ipLogged') and (Session::get('ipLogged') != date('d', $today))) {
                $isNewVisitor = true; // Change of day makes every user a new visitor
            }

            if ($isNewVisitor) {   
                $ip = getenv('REMOTE_ADDR'); // Get the client agent's IP

                $rowsAffected = DB::table('visits')->whereIp($ip)->whereVisitedAt(date('Y-m-d', $today))
                                    ->increment('user_agents');

                if (! $rowsAffected) {
                    DB::table('visits')->insert(array('ip' => $ip, 'user_agents' => 1, 'visited_at' => date('Y-m-d', $today)));
                }
                
                Session::put('ipLogged', date('d', $today)); // Keep in our session-mind the day we logged this IP
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Jobs
        |--------------------------------------------------------------------------
        |
        | Register Jobs
        |
        */

        Jobs::addLazy('updateStreams', 'App\Modules\Streams\UpdateStreamsJob');
        Jobs::addLazy('deleteUserActivities', 'Contentify\DeleteUserActivitiesJob');

        /*
        |--------------------------------------------------------------------------
        | Language Settings
        |--------------------------------------------------------------------------
        |
        | Set the language for the user (also if not logged in)
        |
        */

        if (! Session::has('app.locale')) {
            if (user()) {
                Session::set('app.locale', user()->language->code);
                App::setLocale(Session::get('app.locale'));
            } else {
                if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                    $clientLanguage = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
                    $languages = File::directories(base_path().'/resources/lang');

                    array_walk($languages, function(&$value, $key)
                    {
                        $value = basename($value);
                    });

                    if (in_array($clientLanguage, $languages)) {
                        Session::set('app.locale', $clientLanguage);
                    } else {
                        Session::set('app.locale', Lang::getLocale());
                    }
                } else {
                    Session::set('app.locale', Lang::getLocale());
                }
            }
        } else {
            App::setLocale(Session::get('app.locale'));
        }

        Carbon::setToStringFormat(trans('app.date_format'));
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        /*
         * The RSS package does not have a service provider for Laravel 5 (only for Laravel 4).
         * Therefore we have to register the rss service so the facade can use it.
         */
        $this->app['rss'] = $this->app->share(function($app)
        {
            return new Rss;
        });
    }

}
