<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParachuteInspectionItemDescription extends Model
{
    use SoftDeletes;

    protected $table = 'parachute_inspection_item_descriptions';

    protected $guarded = [];

    public function parachuteInspectionItem()
    {
        return $this->belongsTo(ParachuteInspectionItem::class, 'parachute_inspection_item_id');
    }
}
