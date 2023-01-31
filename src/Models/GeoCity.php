<?php

namespace Digitalion\LaravelGeo\Models;

use Digitalion\LaravelGeo\Traits\GeoTableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeoCity extends Model
{
	use HasFactory, GeoTableTrait;

	protected $casts = [
		'polygon' => 'json',
	];
	protected $guarded = [];
	protected $table = null;
	public $timestamps = false;



	//*** RELATIONSHIPS ***//

	public function province()
	{
		return $this->belongsTo(GeoProvince::class, 'geo_province_id')->orderBy('name');
	}
	public function region()
	{
		return $this->belongsTo(GeoRegion::class, 'geo_region_id')->orderBy('name');
	}
}
