<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    protected $fillable = ['user_id', 'fcm_token'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
