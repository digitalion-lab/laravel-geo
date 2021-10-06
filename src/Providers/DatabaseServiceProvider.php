<?php

namespace Digitalion\LaravelGeo\Providers;

use Digitalion\LaravelGeo\Models\GeoCity;
use Digitalion\LaravelGeo\Models\GeoProvince;
use Digitalion\LaravelGeo\Models\GeoRegion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
	/**
	 * Register services.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}

	/**
	 * Bootstrap services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$default_country = strtoupper(config('geo.geocoding.country'));

		$models = ['GeoCity', 'GeoProvince', 'GeoRegion'];
		$with_geo_relations = true;
		foreach ($models as $model) {
			$model_namespace = '\\Digitalion\\LaravelGeo\\Models\\' . $model;
			$model_class = new $model_namespace();
			$table_name = $model_class->getTable();
			$with_geo_relations = Schema::hasTable($table_name);
			if (!$with_geo_relations) break;
		}

		Blueprint::macro('address', function (bool $required = true) use ($default_country, $with_geo_relations) {
			$this->string('route', 100)->nullable(!$required);
			$this->string('street_number', 25)->nullable();
			$this->unsignedMediumInteger('postal_code')->nullable(!$required);
			$this->string('locality', 100)->nullable();
			if ($with_geo_relations) $this->foreignIdFor(GeoCity::class)->nullable()->constrained()->nullOnDelete();
			$this->string('city', 100)->nullable(!$required);
			if ($with_geo_relations) $this->foreignIdFor(GeoProvince::class)->nullable()->constrained()->nullOnDelete();
			$this->string('province', 2)->nullable(!$required);
			if ($with_geo_relations) $this->foreignIdFor(GeoRegion::class)->nullable()->constrained()->nullOnDelete();
			$this->string('region', 100)->nullable(!$required);
			$this->string('country', 5)->nullable(!$required)->default($default_country);
			$this->double('latitude', 11, 8)->nullable(!$required)->default(0.0);
			$this->double('longitude', 11, 8)->nullable(!$required)->default(0.0);
		});
	}
}
