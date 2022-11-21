<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    public $timestamps = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'start_time',
        'end_time',
        'description',
        'user_id',
        'conf_id',
        'category_id',
        'meeting_id',
    ];

}
