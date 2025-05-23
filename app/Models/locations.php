<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class locations extends Model
{
    protected $table = 'locations';
    protected $primaryKey = 'id_location';

    protected $fillable = [
        'latitude',
        'longitude',
        'complete_address',
    ];

    public function complaint()
    {
        return $this->hasOne(complaints::class, 'id_location');
    }
}
