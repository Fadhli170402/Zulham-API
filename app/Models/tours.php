<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tours extends Model
{
    protected $table = 'tours';
    protected $primaryKey = 'id_tour';
    protected $fillable = [
        'address_tour',
        'tour_name',
    ];

    public function rating()
    {
        return $this->hasMany(ratings::class, 'id_tour');
    }
}
