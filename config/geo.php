<?php

return [
	/**
	 * the prefix to give to the tables with the list of regions, provinces and cities.
	 */
	'tables_prefix' => 'geo_',

	/**
	 * The api key used when sending Geocoding requests to Google.
	 */
	'google_maps_api_key' => env('GMAPS_API_KEY', ''),


	'geocoding' => [

		/**
		 * The language param used to set response translations for textual data.
		 *
		 * More info: https://developers.google.com/maps/faq#languagesupport
		 */
		'language' => '',

		/**
		 * The region param used to finetune the geocoding process.
		 *
		 * More info: https://developers.google.com/maps/documentation/geocoding/intro#RegionCodes
		 */
		'region' => '',

		/**
		 * The bounds param used to finetune the geocoding process.
		 *
		 * More info: https://developers.google.com/maps/documentation/geocoding/intro#Viewports
		 */
		'bounds' => '',

		/**
		 * The country param used to limit results to a specific country.
		 *
		 * More info: https://developers.google.com/maps/documentation/javascript/geocoding#GeocodingRequests
		 */
		'country' => '',
	],


	'map' => [

		/**
		 * The map type format.
		 *
		 * More info: https://developers.google.com/maps/documentation/maps-static/start#MapTypes
		 */
		'maptype' => 'roadmap',

		/**
		 * Defines the language to use for display of labels on map tiles.
		 *
		 * More info: https://developers.google.com/maps/faq#languagesupport
		 */
		'language' => '',

		/**
		 * Defines the format of the resulting image.
		 *
		 * More info: https://developers.google.com/maps/documentation/maps-static/start#map-parameters
		 */
		'format' => 'png',

		/**
		 * The width of the generated map image
		 *
		 * More info: https://developers.google.com/maps/documentation/maps-static/start#map-parameters
		 */
		'width' => 600,

		/**
		 * The height of the generated map image
		 *
		 * More info: https://developers.google.com/maps/documentation/maps-static/start#map-parameters
		 */
		'height' => 400,

		/**
		 * The zoom level of the generated map image
		 *
		 * More info: https://developers.google.com/maps/documentation/maps-static/start#location
		 */
		'zoom' => 13,
	],
];
