<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class medias extends Model
{
    protected $table = 'medias';
    protected $primaryKey = 'id_media';

    protected $fillable = [
        'id_complaint',
        'path',
        'media_type'
    ];

    public function complaint()
    {
        return $this->belongsTo(complaints::class, 'id_complaint');
    }
}
