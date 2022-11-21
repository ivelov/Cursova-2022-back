<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conferences extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'date',
        'time',
        'title',
        'country',
        'latitude',
        'longitude',
        'user_id',
        'category_id'
    ];
}
