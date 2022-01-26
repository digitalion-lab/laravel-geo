<?php

use Digitalion\LaravelGeo\Enums\GoogleMapsAddressComponentsEnum;
use Digitalion\LaravelGeo\Models\GeoProvince;
use Digitalion\LaravelGeo\Models\GeoRegion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeoTables extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		$table_prefix = config('geo.tables_prefix');

		Schema::create($table_prefix . 'regions', function (Blueprint $table) {
			$table->id();
			$table->string('code', config('geo.database.' . GoogleMapsAddressComponentsEnum::Region, 5));
			$table->string('name');
			$table->string('country');
			$table->json('polygon')->nullable();
		});

		Schema::create($table_prefix . 'provinces', function (Blueprint $table) use ($table_prefix) {
			$table->id();
			$table->foreignIdFor(GeoRegion::class)->constrained($table_prefix . 'regions')->cascadeOnDelete();
			$table->string('code', config('geo.database.' . GoogleMapsAddressComponentsEnum::Province, 2));
			$table->string('name');
			$table->json('polygon')->nullable();
		});

		$countryFields = $this->countryFields();
		Schema::create($table_prefix . 'cities', function (Blueprint $table) use ($table_prefix, $countryFields) {
			$table->id();
			$table->foreignIdFor(GeoRegion::class)->constrained($table_prefix . 'regions')->cascadeOnDelete();
			$table->foreignIdFor(GeoProvince::class)->constrained($table_prefix . 'provinces')->cascadeOnDelete();
			$table->string('code', config('geo.database.' . GoogleMapsAddressComponentsEnum::City, 100));
			foreach ($countryFields as $field => $length) {
				$table->string($field, $length)->nullable();
			}
			$table->string('postal_code', config('geo.database.' . GoogleMapsAddressComponentsEnum::PostalCode, 5))->nullable();
			$table->string('name');
			$table->json('polygon')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		$table_prefix = config('geo.tables_prefix');
		Schema::dropIfExists($table_prefix . 'cities');
		Schema::dropIfExists($table_prefix . 'provinces');
		Schema::dropIfExists($table_prefix . 'regions');
	}


	private function countryFields(): array
	{
		$fields = [];
		$country = '';

		switch ($country) {
			case 'IT':
				$fields['istat_code'] = 10;
				$fields['catasto_code'] = 10;
				$fields['zona_climatica'] = 5;
				$fields['zona_sismica'] = 5;
				break;
		}

		return $fields;
	}
}
