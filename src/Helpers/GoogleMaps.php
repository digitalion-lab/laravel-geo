<?php

namespace Digitalion\LaravelGeo\Helpers;

use Digitalion\LaravelGeo\Enums\GoogleMapsAddressComponentsEnum;
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

			$data = [
				GoogleMapsAddressComponentsEnum::Latitude => $latitude,
				GoogleMapsAddressComponentsEnum::Longitude => $longitude,
			];
			if (!empty($result['address_components'])) {
				$address = collect($result['address_components']);
				$components = $this->filter_address_components($address);

				$data = array_merge($data, $components);
			}
		}

		return $data;
	}


	/**
	 * PUBLIC STATIC METHODS
	 */

	public static function getMapUrl($latitude,  $longitude): string
	{
		$coords = (is_float($latitude) && is_float($longitude))
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

	private function filter_address_components($components)
	{
		$item = [];

		foreach ($components->all() as $component) {
			if (!empty($component->types)) {
				foreach ($component->types as $type) {
					$field = '';
					switch ($type) {
						case 'route':
							$field = GoogleMapsAddressComponentsEnum::Route;
							break;
						case 'street_number':
							$field = GoogleMapsAddressComponentsEnum::StreetNumber;
							break;
						case 'postal_code':
							$field = GoogleMapsAddressComponentsEnum::PostalCode;
							break;
						case 'country':
							$field = GoogleMapsAddressComponentsEnum::Country;
							break;
						case 'locality':
							$field = GoogleMapsAddressComponentsEnum::Locality;
							break;
						case 'administrative_area_level_1':
							$field = GoogleMapsAddressComponentsEnum::Region;
							break;
						case 'administrative_area_level_2':
							$field = GoogleMapsAddressComponentsEnum::Province;
							break;
						case 'administrative_area_level_3':
							$field = GoogleMapsAddressComponentsEnum::City;
							break;
					}
					if (!empty($field)) {
						$item[$field] = $this->check_value_for_db($field, $component->short_name);
					}
				}
			}
		}

		return $item;
	}

	private function check_value_for_db($field, $value)
	{
		switch ($field) {
			case GoogleMapsAddressComponentsEnum::StreetNumber:
				$value = \Str::limit($value, config("geo.database.$field", 25));
				break;
			case GoogleMapsAddressComponentsEnum::Route:
				$value = \Str::limit($value, config("geo.database.$field", 100));
				break;
			case GoogleMapsAddressComponentsEnum::PostalCode:
				if (strlen($value) > config("geo.database.$field", 5)) $value = '';
				break;
			case GoogleMapsAddressComponentsEnum::City:
				$value = \Str::limit($value, config("geo.database.$field", 100));
				break;
			case GoogleMapsAddressComponentsEnum::Locality:
				$value = \Str::limit($value, config("geo.database.$field", 100));
				break;
			case GoogleMapsAddressComponentsEnum::Province:
				if (strlen($value) > config("geo.database.$field", 2)) $value = '';
				break;
			case GoogleMapsAddressComponentsEnum::Country:
				$value = \Str::limit($value, config("geo.database.$field", 5));
				break;
			case GoogleMapsAddressComponentsEnum::Region:
				$value = \Str::limit($value, config("geo.database.$field", 100));
				break;
		}

		return $value;
	}
}
