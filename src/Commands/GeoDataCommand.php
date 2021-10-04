<?php

namespace Digitalion\LaravelGeo\Commands;

use DB;
use Digitalion\LaravelGeo\Models\GeoCity;
use Digitalion\LaravelGeo\Models\GeoProvince;
use Digitalion\LaravelGeo\Models\GeoRegion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class GeoDataCommand extends Command
{
	protected $signature = 'geo:data';
	protected $description = 'Populating the tables of regions, provinces, and cities for the Italian state only.';
	public $tables_prefix;
	public $default_country;

	/**
	 * Execute the console command.
	 *
	 * @return int
	 */
	public function handle()
	{
		$this->tables_prefix = config('geo.tables_prefix');
		$this->default_country = config('geo.default_country');

		$this->clean();
		$this->dataCities();
		$this->dataClimate();
	}

	private function clean()
	{
		Schema::disableForeignKeyConstraints();

		DB::table($this->tables_prefix . 'cities')->truncate();
		DB::table($this->tables_prefix . 'provinces')->truncate();
		DB::table($this->tables_prefix . 'regions')->truncate();

		Schema::enableForeignKeyConstraints();
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
				'region' => trim(strval($d[10])),
				'province' => trim(strval($d[11])),
				'province_code' => trim(strval($d[14])),
			];
		}
		$cities = $this->fixDataCities($cities);

		$regions = collect($cities)
			->pluck('region')
			->unique()
			->map(function ($item) {
				return ['name' => $item];
			})
			->all();
		$regions = $this->fixDataRegions($regions);
		GeoRegion::insert($regions);

		$regions = GeoRegion::all();
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
		$provinces = $this->fixDataProvinces($provinces->toArray());
		GeoProvince::insert($provinces);

		$provinces = GeoProvince::all();
		$cities = collect($cities)
			->map(function ($item) use ($regions, $provinces) {
				$region_id = $regions->where('name', $item['region'])->first()->id;
				$province_id = $provinces->where('code', $item['province_code'])->first()->id;
				return [
					$this->tables_prefix . 'region_id' => $region_id,
					$this->tables_prefix . 'province_id' => $province_id,
					'istat_code' => $item['istat_code'],
					'catasto_code' => $item['catasto_code'],
					'name' => $item['city'],
				];
			})
			->unique()
			->all();
		GeoCity::insert($cities);

		$cities = GeoCity::all();
		echo 'Data generate: ' . $regions->count() . ' regions, ' . $provinces->count() . ' provinces, ' . $cities->count() . ' cities.' . "\n";
	}

	public function dataClimate()
	{
		$csv = file_get_contents('https://open.digitalion.it/assets/laravel-geo/data/it-climate.csv');

		$data = [];
		$csv_rows = str_getcsv($csv, "\n");
		foreach ($csv_rows as &$csv_row) $data[] = str_getcsv($csv_row, ',');
		array_shift($data);

		$inserted = 0;
		$updated = 0;
		$total = intval(count($data));
		foreach ($data as $row) {
			$city = trim($row[1]);
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

			$data = compact('city', 'polygon', 'zona_climatica', 'zona_sismica');
			try {
				$city = GeoCity::updateOrCreate(
					compact('city'),
					$data
				);
				if ($city->wasRecentlyCreated) {
					$inserted++;
				} else {
					$updated++;
				}
			} catch (Throwable $th) {
				logger()->debug($data);
				report($th);
			}
		}

		echo "$inserted city added and $updated cities updated of $total.\n";
	}

	private function fixDataCities(array $items): array
	{
		foreach ($items as $key => $item) {
			$region = $item['region'];
			switch ($this->default_country) {
				case 'IT':
					if (substr($region, 0, 6) == 'Valle ') $region = 'Valle d\'Aosta';
					if (substr($region, 0, 8) == 'Trentino') $region = 'Trentino-Alto Adige';
					break;
			}
			$item['region'] = $region;
			$items[$key] = $item;
		}

		return $items;
	}

	private function fixDataProvinces(array $items): array
	{
		switch ($this->default_country) {
			case 'IT':
				$items = collect($items);
				if (empty($items->firstWhere('code', 'VS'))) {
					$items->push([
						$this->tables_prefix . 'region_id' => GeoRegion::firstWhere('name', 'Sardegna')->id,
						'code' => 'VS',
						'name' => 'Medio Campidano',
					]);
				}
				$items = $items->toArray();
				break;
		}

		return $items;
	}

	private function fixDataRegions(array $items): array
	{
		return $items;
	}
}
