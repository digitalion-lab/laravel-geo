<?php

namespace Digitalion\LaravelGeo\Models;

use Digitalion\LaravelGeo\Traits\GeoTableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeoRegion extends Model
{
	use HasFactory, GeoTableTrait;

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
