<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class BaseModel extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     */
    protected $appends = ['created_at_formatted', 'updated_at_formatted'];

    /**
     */
    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at
            ? Carbon::parse($this->created_at)->format('d.m.Y')
            : null;
    }

    /**
     */
    public function getUpdatedAtFormattedAttribute()
    {
        return $this->updated_at
            ? Carbon::parse($this->updated_at)->format('d.m.Y')
            : null;
    }

    /**
     */
    public $timestamps = true;

    /**
     */
    public function getNameAttribute()
    {
        return $this->full_name ?? ($this->first_name . ' ' . $this->last_name);
    }
}
