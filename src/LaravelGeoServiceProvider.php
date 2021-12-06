<?php

namespace Digitalion\LaravelGeo;

use Digitalion\LaravelGeo\Commands\GeoDataCommand;
use Digitalion\LaravelGeo\Commands\GeoFixCommand;
use Illuminate\Support\ServiceProvider;

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
				__DIR__ . '/../database/migrations/create_geo_tables.php' => database_path('migrations/'.date('Y_m_d_His_').'create_geo_tables.php')
			], 'laravel-geo-migrations');


			// registering artisan commands
			$this->commands([
				GeoDataCommand::class,
				GeoFixCommand::class,
			]);
		}
	}
}
