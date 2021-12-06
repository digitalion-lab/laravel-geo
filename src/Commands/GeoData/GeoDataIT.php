<?php

namespace Digitalion\LaravelGeo\Commands\GeoData;

use Digitalion\LaravelGeo\Commands\GeoData\GeoDataInterface;
use Digitalion\LaravelGeo\Models\GeoCity;
use Digitalion\LaravelGeo\Models\GeoProvince;
use Digitalion\LaravelGeo\Models\GeoRegion;

class GeoDataIT implements GeoDataInterface
{
	public $tables_prefix;

	public function populate()
	{
		$this->tables_prefix = config('geo.tables_prefix');

		$this->dataCities();
		$this->dataClimate();
	}

	private function dataCities()
	{
		$csv = file_get_contents('https://open.digitalion.it/assets/laravel-geo/data/it-cities.csv');
		// correggo gli errori del csv
		$csv = str_replace("\n(", ' (', utf8_encode($csv));

		$data = [];
		$rows = str_getcsv($csv, "\n");
		foreach ($rows as &$row) $data[] = str_getcsv($row, ';');
		array_shift($data);

		$cities = [];
		foreach ($data as $d) {
			$cities[] = [
				'istat_code' => trim(strval($d[15])), // 4 per il codice alfanumerico, 15 per il numerico
				'catasto_code' => trim(strval($d[19])),
				'city' => trim(strval($d[6])),
				'city_code' => trim(strval($d[3])),
				'region' => trim(strval($d[10])),
				'region_code' => trim(strval($d[0])),
				'province' => trim(strval($d[11])),
				'province_code' => trim(strval($d[2])),
			];
		}
		$cities = $this->fixDataCities($cities);

		$regions = collect($cities)
			->map(function ($item) {
				return [
					'code' => $item['region_code'],
					'name' => $item['region'],
					'country' => 'IT',
				];
			})
			->unique()
			->all();
		$regions = $this->fixDataRegions($regions);
		GeoRegion::insert($regions);

		$regions = GeoRegion::all();
		echo 'Regions generated: ' . $regions->count() . "\n";

		$provinces = collect($cities)
			->map(function ($item) use ($regions) {
				$region_id = $regions->where('name', $item['region'])->first()->id;
				return [
					$this->tables_prefix . 'region_id' => $region_id,
					'code' => $item['province_code'],
					'name' => $item['province'],
				];
			})
			->unique()
			->all();
		$provinces = $this->fixDataProvinces($provinces);
		GeoProvince::insert($provinces);

		$provinces = GeoProvince::all();
		echo 'Provinces generated: ' . $provinces->count() . "\n";

		$cities = collect($cities)
			->map(function ($item) use ($regions, $provinces) {
				$region_id = $regions->where('name', $item['region'])->first()->id;
				$province_id = $provinces->where('code', $item['province_code'])->first()->id;
				return [
					$this->tables_prefix . 'region_id' => $region_id,
					$this->tables_prefix . 'province_id' => $province_id,
					'code' => $item['city_code'],
					'istat_code' => $item['istat_code'],
					'catasto_code' => $item['catasto_code'],
					'name' => $item['city'],
				];
			})
			->unique()
			->all();
		GeoCity::insert($cities);

		$cities = GeoCity::all();
		echo 'Cities generated: ' . $cities->count() . "\n";
	}

	public function dataClimate()
	{
		$csv = file_get_contents('https://open.digitalion.it/assets/laravel-geo/data/it-climate.csv');

		$data = [];
		$csv_rows = str_getcsv($csv, "\n");
		foreach ($csv_rows as &$csv_row) $data[] = str_getcsv($csv_row, ',');
		array_shift($data);

		$updated = 0;
		$notfound = [];
		$total = intval(count($data));
		foreach ($data as $row) {
			$name = $this->fixClimateCityName($row[1]);
			$zona_climatica = preg_replace("/[^a-zA-Z0-9]+/", "", $row[5]);
			$zona_sismica = preg_replace("/[^a-zA-Z0-9]+/", "", $row[6]);
			$polygon_str = str_replace(['MULTIPOLYGON ', '(', ')'], '', $row[8]);
			$polygon_points = explode(',', trim($polygon_str));
			$polygon = [];
			foreach ($polygon_points as $point) {
				$coords = explode(' ', trim($point));
				$polygon[] = [
					'latitude'	=> $coords[1],
					'longitude'	=> $coords[0],
				];
			}

			$data = compact('polygon', 'zona_climatica', 'zona_sismica');
			try {
				$city = GeoCity::where('name', $name)->first();
				if (!empty($city)) {
					$city->update($data);
					$updated++;
				} else {
					$notfound[] = $name;
				}
			} catch (Throwable $th) {
				logger()->debug($data);
				report($th);
			}
		}

		if (count($notfound) > 0) {
			$message = "[COMMAND] geo:data IT\n[data]\n";
			$message .= 'Cities not found: ' . implode(', ', $notfound);
			logger()->debug($message);
		}
		$notfound = count($notfound);
		echo "$updated city updated of $total. $notfound cities not founded. \n";
	}

	private function fixClimateCityName(string $string)
	{
		return trim($string);
	}

	private function fixDataCities(array $items): array
	{
		foreach ($items as $key => $item) {
			$region = $item['region'];
			if (substr($region, 0, 6) == 'Valle ') $region = 'Valle d\'Aosta';
			if (substr($region, 0, 8) == 'Trentino') $region = 'Trentino-Alto Adige';
			$item['region'] = $region;
			$items[$key] = $item;
		}

		return $items;
	}

	private function fixDataProvinces(array $items): array
	{
		$items = collect($items);
		if (empty($items->firstWhere('code', 'VS'))) {
			$items->push([
				$this->tables_prefix . 'region_id' => GeoRegion::firstWhere('name', 'Sardegna')->id,
				'code' => 'VS',
				'name' => 'Medio Campidano',
			]);
		}
		return $items->toArray();
	}

	private function fixDataRegions(array $items): array
	{
		return $items;
	}
}
