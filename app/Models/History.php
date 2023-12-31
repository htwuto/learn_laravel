<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    protected $table = 'history';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'time',
        'action',
        'content'
    ];
}
