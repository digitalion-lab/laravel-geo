<?php

namespace Digitalion\LaravelGeo\Commands;

use Digitalion\LaravelGeo\Helpers\GoogleMaps;
use Illuminate\Console\Command;

class GeoFixCommand extends Command
{
	protected $signature = 'geo:fix {model}';
	protected $description = 'Correcting and formatting addresses in a table';

	public function handle()
	{
		$arg_model = $this->argument('model');
		$gMaps = new GoogleMaps();

		$version = app()->version();
		$lv = intval(substr($version, 0, strpos($version, '.')));
		$class_namespace = (($lv >= 8) ? 'App\\Models\\' : 'App\\') . ucfirst($arg_model);
		if (!class_exists($class_namespace)) {
			$this->warn('Model not found.');
			return;
		}
		$items = $class_namespace::withoutGlobalScopes()->whereNotNull('route')->where('route', '<>', '')->where(function ($query) {
			$query->whereNull('latitude')->orWhereNull('longitude');
		})->get();
		$this->line('Found ' . $items->count() . ' items without coordinates but with the road indicated.');
		$updated = 0;
		foreach ($items as $item) {
			$address = $item->address;
			if (!empty($address)) {
				$data = $gMaps->getGeoDataFromAddress($address);

				$item->update($data);
				$updated++;
			}
		}

		$this->comment("Updated address fields of $updated items.");
	}
}
