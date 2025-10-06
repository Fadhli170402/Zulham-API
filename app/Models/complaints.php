<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class complaints extends Model
{
    protected $table = 'complaints';
    protected $primaryKey = 'id_complaint';

    protected $fillable = [
        'complaint',
        'complaint_date',
        'id_users',
        'id_location',
        'id_tour',
            'status',

    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_users');
    }

    public function location()
    {
        return $this->belongsTo(locations::class, 'id_location', 'id_location');
    }

    public function media()
    {
        return $this->hasMany(medias::class, 'id_complaint', 'id_complaint');
    }
    public function tour()
    {
        return $this->belongsTo(tours::class, 'id_tour', 'id_tour');
    }
}
