<?php

namespace Digitalion\LaravelGeo\Commands;

use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class GeoDataCommand extends Command
{
	protected $signature = 'geo:data {country?}';
	protected $description = 'Populating the tables of regions, provinces, and cities for the Italian state only.';
	public $tables_prefix;
	public $country;

	/**
	 * Execute the console command.
	 *
	 * @return int
	 */
	public function handle()
	{
		$this->tables_prefix = config('geo.tables_prefix');
		$this->country = strtoupper($this->argument('country') ?: config('geo.geocoding.country'));

		if (empty($this->country)) {
			$this->warn('Operation aborted: no country indicated or found in the configuration file.');
			return;
		}

		$exists = $this->checkTablesExists();
		if ($exists) {
			$this->clean();
			$this->populate();
		} else {
			$this->warn('Operation aborted: geo tables do not exist.');
		}
	}

	private function checkTablesExists(): bool
	{
		$models = ['GeoCity', 'GeoProvince', 'GeoRegion'];
		$exists = true;
		foreach ($models as $model) {
			$model_namespace = '\\Digitalion\\LaravelGeo\\Models\\' . $model;
			$model_class = new $model_namespace();
			$table_name = $model_class->getTable();
			$exists = Schema::hasTable($table_name);
			if (!$exists) break;
		}

		return $exists;
	}

	private function clean()
	{
		if ($this->confirm('Do you want to delete all current geo table data?')) {
			Schema::disableForeignKeyConstraints();

			DB::table($this->tables_prefix . 'cities')->truncate();
			DB::table($this->tables_prefix . 'provinces')->truncate();
			DB::table($this->tables_prefix . 'regions')->truncate();

			Schema::enableForeignKeyConstraints();
		}
	}

	private function populate()
	{
		$class_namespace = '\\Digitalion\\LaravelGeo\\Commands\\GeoData\\GeoData' . $this->country;
		if (class_exists($class_namespace)) {
			$class = new $class_namespace();
			$class->populate();
		}
	}
}
