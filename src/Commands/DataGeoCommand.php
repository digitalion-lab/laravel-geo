<?php

namespace Digitalion\LaravelGeo\Commands;

use DB;
use Digitalion\LaravelGeo\Models\GeoCity;
use Digitalion\LaravelGeo\Models\GeoProvince;
use Digitalion\LaravelGeo\Models\GeoRegion;
use Illuminate\Console\Command;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Schema;

class DataGeoCommand extends Command
{
	protected $signature = 'data:geo';
	protected $description = 'Populating the tables of regions, provinces, and cities for the Italian state only.';
	public $table_prefix;

	/**
	 * Execute the console command.
	 *
	 * @return int
	 */
	public function handle()
	{
		$this->table_prefix = config('project-tools.geo.tables_prefix');

		$this->clean();
		$this->dataIstat();
		$this->dataExtra();
	}

	private function clean()
	{
		Schema::disableForeignKeyConstraints();

		DB::table($this->table_prefix . 'cities')->truncate();
		DB::table($this->table_prefix . 'provinces')->truncate();
		DB::table($this->table_prefix . 'regions')->truncate();

		Schema::enableForeignKeyConstraints();
	}

	private function dataIstat()
	{
		$csv = file_get_contents('https://www.istat.it/storage/codici-unita-amministrative/Elenco-comuni-italiani.csv');
		// correggo gli errori del csv
		$csv = str_replace("\n(", ' (', utf8_encode($csv));

		$data = [];
		$rows = str_getcsv($csv, "\n");
		foreach ($rows as &$row) $data[] = str_getcsv($row, ';');
		array_shift($data);

		$cities = [];
		foreach ($data as $d) {
			$region = $d[10];
			if (substr($region, 0, 6) == 'Valle ') $region = 'Valle d\'Aosta';
			if (substr($region, 0, 8) == 'Trentino') $region = 'Trentino-Alto Adige';
			$cities[] = [
				'istat_code' => $d[15], // 4 per il codice alfanumerico, 15 per il numerico
				'catasto_code' => $d[19],
				'city' => trim($d[6]),
				'region' => $region,
				'province' => $d[11],
				'province_code' => $d[14],
			];
		}

		$regions = collect($cities)
			->pluck('region')
			->unique()
			->map(function ($item) {
				return ['name' => $item];
			})
			->all();
		GeoRegion::insert($regions);

		$regions = GeoRegion::all();
		$provinces = collect($cities)
			->map(function ($item) use ($regions) {
				$region_id = $regions->where('name', $item['region'])->first()->id;
				return [
					$this->table_prefix . 'region_id' => $region_id,
					'code' => $item['province_code'],
					'name' => $item['province'],
				];
			})
			->unique()
			->all();
		if (empty($provinces->firstWhere('code', 'VS'))) {
			$provinces[] = [
				$this->table_prefix . 'region_id' => GeoRegion::firstWhere('name', 'Sardegna')->id,
				'code' => 'VS',
				'name' => 'Medio Campidano',
			];
		}
		GeoProvince::insert($provinces);

		$provinces = GeoProvince::all();
		$cities = collect($cities)
			->map(function ($item) use ($regions, $provinces) {
				$region_id = $regions->where('name', $item['region'])->first()->id;
				$province_id = $provinces->where('code', $item['province_code'])->first()->id;
				return [
					$this->table_prefix . 'region_id' => $region_id,
					$this->table_prefix . 'province_id' => $province_id,
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

	public function dataExtra()
	{
		$csv = File::get(__DIR__ . '/../../assets/zone_climatiche.csv');

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
}
