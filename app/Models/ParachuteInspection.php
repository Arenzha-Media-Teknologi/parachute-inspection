<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParachuteInspection extends Model
{
    use SoftDeletes;

    protected $table = 'parachute_inspections';

    protected $fillable = [
        'number',
        'date',
        'activity_name',
        'person_in_charge',
        'parachute_id',
        'created_by',
    ];

    public function items()
    {
        return $this->hasMany(ParachuteInspectionItem::class, 'parachute_inspection_id', 'id');
    }

    public function parachute()
    {
        return $this->belongsTo(Parachute::class, 'parachute_id');
    }
}
