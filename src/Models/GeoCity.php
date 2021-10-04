<?php

namespace Digitalion\LaravelGeo\Models;

use Digitalion\LaravelGeo\Traits\ModelDynamicTableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeoCity extends Model
{
	use HasFactory, ModelDynamicTableTrait;

	protected $guarded = [];
	public $timestamps = false;
	protected $table = null;



	//*** RELATIONSHIPS ***//

	public function province()
	{
		return $this->belongsTo(GeoProvince::class);
	}
	public function region()
	{
		return $this->belongsTo(GeoRegion::class);
	}
}
