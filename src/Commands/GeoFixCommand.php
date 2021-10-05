<?php

namespace Digitalion\LaravelGeo\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Spatie\Geocoder\Geocoder;

class GeoFixCommand extends Command
{
	protected $signature = 'geo:fix {model}';
	protected $description = 'Correcting and formatting addresses in a table';

	public function handle()
	{
		$arg_model = $this->argument('model');
		$client = new Client();
		$geocoder = new Geocoder($client);

		// configuration
		if (!empty(config('geo.google_maps_api_key'))) $geocoder->setApiKey(config('geo.google_maps_api_key'));
		$config = config('geo.geocoding', []);
		if (!empty($config['country'])) $geocoder->setCountry(strtoupper($config['country']));
		if (!empty($config['region'])) $geocoder->setRegion(strtolower($config['region']));
		if (!empty($config['bounds'])) $geocoder->setBounds($config['bounds']);
		if (!empty($config['language'])) $geocoder->setLanguage(strtolower($config['language']));

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
				$result = $geocoder->getCoordinatesForAddress($address);

				$latitude = $result['lat'] ?? null;
				$longitude = $result['lng'] ?? null;

				$data = compact('latitude', 'longitude');
				if (!empty($result['address_components'])) {
					$address = collect($result['address_components']);

					$route = $this->filter_address_components($address, 'route');
					$street_number = $this->filter_address_components($address, 'street_number');
					$postal_code = $this->filter_address_components($address, 'postal_code');
					$province = $this->filter_address_components($address, 'administrative_area_level_2');
					$city = $this->filter_address_components($address, 'administrative_area_level_3');
					$region = $this->filter_address_components($address, 'administrative_area_level_1');
					$country = $this->filter_address_components($address, 'country');

					$data = array_merge($data, compact('route', 'street_number', 'postal_code', 'province', 'city', 'region', 'country'));
				}

				$item->update($data);
				$updated++;
			}
		}

		$this->comment("Updated address fields of $updated items.");
	}

	private function filter_address_components($collection, string $property)
	{
		$item = $collection->filter(function ($value, $key) use ($property) {
			return boolval(array_search($property, $value->types) !== false);
		})->first();

		if (empty($item)) return null;
		return $item->short_name;
	}
}
