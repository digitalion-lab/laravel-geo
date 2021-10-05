<?php

namespace Digitalion\LaravelGeo\Traits;

trait HasAddressTrait
{
	public function initializeHasAddressTrait()
	{
		$this->appends = array_merge(['address', 'gmaps_url', 'gmaps_image'], $this->appends);
	}

	public function getAddressAttribute()
	{
		$address = '';
		if (!empty($this->route)) {
			$address .= $this->route;
			if (!empty($this->street_number)) $address .= ', ' . $this->street_number;
		}
		if (!empty($address)) $address .= ' - ';
		if (!empty($this->postal_code)) $address .= $this->postal_code . ' ';
		if (!empty($this->city)) $address .= $this->city . ' ';
		if (!empty($this->province)) $address .= '(' . $this->province . ')';

		return $address;
	}

	public function getGmapsUrlAttribute()
	{
		return 'https://www.google.com/maps/place/' . $this->latitude . ',' . $this->longitude;
	}

	public function getGmapsImageAttribute(): string
	{
		$apikey = config('portal.google.gmaps_key');
		$maptype = config('geo.map.maptype', 'roadmap');
		$format = strtolower(config('geo.map.format', 'png'));
		$width = config('geo.map.width', 600);
		$height = config('geo.map.height', 400);
		$zoom = config('geo.map.zoom', 13);

		return 'https://maps.googleapis.com/maps/api/staticmap?' .
			'center=' . $this->latitude . ',' . $this->longitude .
			'&zoom=' . $zoom .
			'&scale=2' .
			'&size=' . $width . 'x' . $height .
			'&maptype=' . $maptype .
			'&key=' . $apikey .
			'&format=' . $format .
			'&visual_refresh=true';
	}
}
