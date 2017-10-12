<?php namespace Nifus\Users;

use Illuminate\Support\ServiceProvider;

class UsersServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $this->app['Users'] = $this->app->share(function($app)
        {
        });
    }

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}
    public function boot()
    {
        $this->package('nifus/users','users');
        \View::addNamespace('users', dirname( __FILE__ ) . "/../..");
         require __DIR__ . '/../../routes.php';
    }

}