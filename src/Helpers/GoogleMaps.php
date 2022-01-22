<?php

namespace Digitalion\LaravelGeo\Helpers;

use GuzzleHttp\Client;
use Spatie\Geocoder\Geocoder;

class GoogleMaps
{
	public $geocoder;

	/**
	 * CONSTRUCTOR
	 */

	public function __construct()
	{
		$this->initGeocoder();
	}

	private function initGeocoder(): void
	{
		$client = new Client();
		$this->geocoder = new Geocoder($client);

		// configuration
		if (!empty(config('geo.google_maps_api_key'))) $this->geocoder->setApiKey(config('geo.google_maps_api_key'));
		$config = config('geo.geocoding', []);
		if (!empty($config['country'])) $this->geocoder->setCountry(strtoupper($config['country']));
		if (!empty($config['region'])) $this->geocoder->setRegion(strtolower($config['region']));
		if (!empty($config['bounds'])) $this->geocoder->setBounds($config['bounds']);
		if (!empty($config['language'])) $this->geocoder->setLanguage(strtolower($config['language']));
	}


	/**
	 * PUBLIC METHODS
	 */

	public function getGeoDataFromAddress(string $address): array
	{
		$data = [];
		if (!empty($address)) {
			$result = $this->geocoder->getCoordinatesForAddress($address);

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
		}

		return $data;
	}


	/**
	 * PUBLIC STATIC METHODS
	 */

	public static function getMapUrl($latitude,  $longitude): string
	{
		$coords = (is_float($latitude) && !is_float($longitude))
			? number_format((float) $latitude, 7, '.', '') . ',' . number_format((float) $longitude, 7, '.', '')
			: '';
		if (!empty($coords)) {
			$url = 'https://maps.google.com?q=' . $coords;
			$maptype = config('geo.map.maptype', 'roadmap');
			switch ($maptype) {
				case 'satellite':
					$url .= '&t=k';
					break;

				case 'terrain':
					$url .= '&t=p';
					break;

				case 'hybrid':
					$url .= '&t=h';
					break;

				case 'roadmap':
				default:
					$url .= '&t=m';
					break;
			}
			$zoom = intval(config('geo.map.zoom', 13));
			$url .= '&z=' . $zoom;

			return $url;
		}
		return '';
	}

	public static function getMapImageUrl($latitude,  $longitude): string
	{
		$apikey = config('geo.google_maps_api_key');
		$maptype = config('geo.map.maptype', 'roadmap');
		$format = strtolower(config('geo.map.format', 'png'));
		$width = intval(config('geo.map.width', 600));
		$height = intval(config('geo.map.height', 400));
		$zoom = intval(config('geo.map.zoom', 13));
		$markerIconUrl = config('geo.marker_icon_url');
		$coords = (!empty($latitude) || !empty($longitude)) ? $latitude . ',' . $longitude : '';
		$icon = '';
		if (!empty($markerIconUrl)) {
			$icon = '&markers=icon:' . $markerIconUrl . '|' . $coords;
		}

		return 'https://maps.googleapis.com/maps/api/staticmap?' .
			'center=' . $coords .
			'&zoom=' . $zoom .
			'&scale=2' .
			'&size=' . $width . 'x' . $height .
			'&maptype=' . $maptype .
			'&key=' . $apikey .
			'&format=' . $format .
			'&visual_refresh=true' .
			$icon;
	}


	/**
	 * PRIVATE METHODS
	 */

	private function filter_address_components($collection, string $property)
	{
		$item = $collection->filter(function ($value, $key) use ($property) {
			return boolval(array_search($property, $value->types) !== false);
		})->first();

		if (empty($item)) return null;
		return $item->short_name;
	}
}
