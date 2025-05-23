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
    ];

    public function users()
    {
        return $this->belongsTo(User::class, 'id_users');
    }

    public function location()
    {
        return $this->belongsTo(locations::class, 'id_location');
    }

    public function media()
    {
        return $this->hasMany(medias::class, 'id_complaint');
    }
}
