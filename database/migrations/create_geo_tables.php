<?php

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
			$table->string('code', 5);
			$table->string('name');
			$table->string('country');
			$table->json('polygon')->nullable();
		});
		Schema::create($table_prefix . 'provinces', function (Blueprint $table) use ($table_prefix) {
			$table->id();
			$table->foreignIdFor(GeoRegion::class)->constrained($table_prefix . 'regions')->cascadeOnDelete();
			$table->string('code', 5);
			$table->string('name');
			$table->json('polygon')->nullable();
		});
		Schema::create($table_prefix . 'cities', function (Blueprint $table) use ($table_prefix) {
			$table->id();
			$table->foreignIdFor(GeoRegion::class)->constrained($table_prefix . 'regions')->cascadeOnDelete();
			$table->foreignIdFor(GeoProvince::class)->constrained($table_prefix . 'provinces')->cascadeOnDelete();
			$table->string('istat_code', 10)->nullable();
			$table->string('catasto_code', 10)->nullable();
			$table->unsignedSmallInteger('postal_code')->nullable();
			$table->string('name');
			$table->string('zona_climatica', 5)->nullable();
			$table->string('zona_sismica', 5)->nullable();
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
}
