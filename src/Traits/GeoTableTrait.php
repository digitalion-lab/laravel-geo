<?php

namespace Digitalion\LaravelGeo\Traits;

trait GeoTableTrait
{
	protected $table = null;

	public function initializeGeoTableTrait()
	{
		$table_prefix = config('project-tools.geo.tables_prefix');
		$table = $table_prefix . strtolower(str_replace('Geo', '', __CLASS__));
		$this->setTable($table);
	}
}
