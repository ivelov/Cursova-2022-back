<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    public $timestamps = false;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function conference()
    {
        return $this->belongsTo(Conferences::class, 'conf_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
