<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Parachute extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $guarded = [];

    public function inspections()
    {
        return $this->hasMany(ParachuteInspection::class, 'parachute_id', 'id');
    }
}
