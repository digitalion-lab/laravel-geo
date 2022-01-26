<?php

namespace Digitalion\LaravelGeo\Providers;

use Digitalion\LaravelGeo\Enums\GoogleMapsAddressComponentsEnum;
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
		$db_connection = config('database.default');

		$models = ['GeoCity', 'GeoProvince', 'GeoRegion'];
		$with_geo_relations = true;
		foreach ($models as $model) {
			$model_namespace = '\\Digitalion\\LaravelGeo\\Models\\' . $model;
			$model_class = new $model_namespace();
			$table_name = $model_class->getTable();
			$with_geo_relations = false;
			try {
				$with_geo_relations = Schema::connection($db_connection)->hasTable($table_name);
			} catch (\Throwable $th) {
				$with_geo_relations = false;
			}
			if (!$with_geo_relations) break;
		}

		Blueprint::macro('address', function (bool $required = true) use ($default_country, $with_geo_relations) {
			// route
			$field = GoogleMapsAddressComponentsEnum::Route;
			$this->string($field, config("geo.database.$field", 100))->nullable(!$required);

			// street number
			$field = GoogleMapsAddressComponentsEnum::StreetNumber;
			$this->string($field, config("geo.database.$field", 25))->nullable();

			// postal code
			$field = GoogleMapsAddressComponentsEnum::PostalCode;
			$this->string($field, config("geo.database.$field", 5))->nullable();

			// locality
			$field = GoogleMapsAddressComponentsEnum::Locality;
			$this->string($field, config("geo.database.$field", 100))->nullable();

			// geo city id
			if ($with_geo_relations) $this->foreignIdFor(GeoCity::class)->nullable()->constrained()->nullOnDelete();

			// city
			$field = GoogleMapsAddressComponentsEnum::City;
			$this->string($field, config("geo.database.$field", 100))->nullable(!$required);

			// geo province id
			if ($with_geo_relations) $this->foreignIdFor(GeoProvince::class)->nullable()->constrained()->nullOnDelete();

			// province
			$field = GoogleMapsAddressComponentsEnum::Province;
			$this->string($field, config("geo.database.$field", 2))->nullable(!$required);

			// geo region id
			if ($with_geo_relations) $this->foreignIdFor(GeoRegion::class)->nullable()->constrained()->nullOnDelete();

			// region
			$field = GoogleMapsAddressComponentsEnum::Region;
			$this->string($field, config("geo.database.$field", 100))->nullable(!$required);

			// region
			$field = GoogleMapsAddressComponentsEnum::Country;
			$this->string($field, config("geo.database.$field", 5))->nullable(!$required)->default($default_country);

			// latitude
			$field = GoogleMapsAddressComponentsEnum::Latitude;
			$this->double($field, 11, 8)->nullable(!$required);

			// longitude
			$field = GoogleMapsAddressComponentsEnum::Longitude;
			$this->double($field, 11, 8)->nullable(!$required);
		});
	}
}
