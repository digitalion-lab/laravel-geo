<?php

namespace Digitalion\LaravelGeo\Traits;

use Digitalion\LaravelGeo\Helpers\GoogleMaps;

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
		if (\Str::endsWith($address, ' - ')) {
			$address = \Str::substr($address, 0, -3);
		}

		return $address;
	}

	public function getGmapsUrlAttribute()
	{
		return GoogleMaps::getMapUrl($this->latitude, $this->longitude);
	}

	public function getGmapsImageAttribute()
	{
		return GoogleMaps::getMapImageUrl($this->latitude, $this->longitude);
	}
}
