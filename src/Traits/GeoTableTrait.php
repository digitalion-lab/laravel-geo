<?php

namespace Digitalion\LaravelGeo\Traits;

use Illuminate\Support\Str;

trait GeoTableTrait
{
	protected $table = null;

	public function initializeGeoTableTrait()
	{
		$table_prefix = config('geo.tables_prefix');
		$table = strtolower(str_replace('Geo', $table_prefix, class_basename(__CLASS__)));
		$table = Str::plural($table);
		$this->setTable($table);
	}
}
