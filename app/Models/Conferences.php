<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conferences extends Model
{
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
