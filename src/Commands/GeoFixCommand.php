<?php

namespace Digitalion\LaravelGeo\Commands;

use Digitalion\LaravelGeo\Enums\GoogleMapsAddressComponentsEnum;
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
		$class_namespace = (($lv >= 8) ? '\\App\\Models\\' : '\\App\\') . ucfirst($arg_model);
		if (!class_exists($class_namespace)) {
			$this->warn('Model not found.');
			return;
		}
		$items = $class_namespace::withoutGlobalScopes()
			->where(function ($q) {
				$q->whereNotNull(GoogleMapsAddressComponentsEnum::Route)->orWhere(GoogleMapsAddressComponentsEnum::Route, '<>', '');
			})
			->where(function ($q) {
				$q->whereNull(GoogleMapsAddressComponentsEnum::Latitude)->orWhereNull(GoogleMapsAddressComponentsEnum::Longitude);
			})
			->get();
		$this->line('Found ' . $items->count() . ' items without coordinates but with the road indicated.');
		$updated = $this->withProgressBar($items, function ($item) use ($gMaps) {
			if (empty($item->deleted_at)) {
				$address = $item->address;
				if (!empty($address)) {
					$data = $gMaps->getGeoDataFromAddress($address);

					try {
						$item->update($data);
					} catch (\Throwable $th) {
						//throw $th;
					}
				}
			}
		})->count();

		$this->comment("\nUpdated address fields of $updated items.");
	}
}
