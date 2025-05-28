<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParachuteInspectionItem extends Model
{
    use SoftDeletes;

    protected $table = 'parachute_inspection_items';

    protected $guarded = [];

    // protected $fillable = [
    //     'parachute_inspection_id',
    //     'description',
    //     'image_url',
    //     'image_file_name',
    //     'image_file_size',
    //     'image_file_size',
    // ];

    public function inspection()
    {
        return $this->belongsTo(ParachuteInspection::class, 'parachute_inspection_id', 'id');
    }

    public function descriptions()
    {
        return $this->hasMany(ParachuteInspectionItemDescription::class);
    }
}
