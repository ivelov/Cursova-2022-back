<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Listener extends Model
{
    protected $fillable = [
        'user_id',
        'conference_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function conference()
    {
        return $this->belongsTo(Conferences::class);
    }
}
