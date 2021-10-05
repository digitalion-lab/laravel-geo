<?php

namespace Digitalion\LaravelGeo\Helpers;

interface MapHelperInterface
{
	public function getGeoDataFromAddress(string $address): array;

	public static function getMapUrl(float $latitude, float $longitude): string;
	public static function getMapImageUrl(float $latitude, float $longitude): string;
}
