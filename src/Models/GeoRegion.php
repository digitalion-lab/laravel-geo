<?php

namespace Digitalion\LaravelGeo\Models;

use Digitalion\LaravelGeo\Traits\ModelDynamicTableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeoRegion extends Model
{
	use HasFactory, ModelDynamicTableTrait;

	protected $guarded = [];
	public $timestamps = false;
	protected $table = null;



	//*** RELATIONSHIPS ***//

	public function cities()
	{
		return $this->hasMany(GeoCity::class);
	}
	public function provinces()
	{
		return $this->hasMany(GeoProvince::class);
	}
}
