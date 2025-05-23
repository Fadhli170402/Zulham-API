<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ratings extends Model
{
    protected $table = 'rating';
    protected $primaryKey = 'id_rating';

    protected $fillable = [
        'value',
        'comment',
        'rating_date',
        'id_tour',
        'id_users',
    ];

    public function tour()
    {
        return $this->belongsTo(tours::class, 'id_tour');
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'id_users');
    }
}
