<?php

namespace Digitalion\LaravelGeo;

use Digitalion\LaravelGeo\Commands\DataGeoCommand;
use Digitalion\LaravelGeo\Commands\MakeClassCommand;
use Digitalion\LaravelGeo\Commands\MakeEnumCommand;
use Digitalion\LaravelGeo\Commands\MakeHelperCommand;
use Digitalion\LaravelGeo\Commands\MakeLangCommand;
use Digitalion\LaravelGeo\Commands\MakeScopeCommand;
use Digitalion\LaravelGeo\Commands\MakeTraitCommand;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class LaravelGeoServiceProvider extends ServiceProvider
{
	/**
	 * Register services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->mergeConfigFrom(__DIR__ . '/../config/geo.php', 'geo');
	}
	/**
	 * Bootstrap services.
	 *
	 * @return void
	 */
	public function boot()
	{
		if ($this->app->runningInConsole()) {

			$this->publishes([
				__DIR__ . '/../config/geo.php' => config_path('geo.php'),
			], 'laravel-geo-configs');

			$this->publishes([
				__DIR__ . '/../database/migrations/create_geo_tables.php' => database_path('migrations/create_geo_tables.php')
			], 'laravel-geo-migrations');


			// registering artisan commands
			$this->commands([
				DataGeoCommand::class,
			]);
		}
	}
}
