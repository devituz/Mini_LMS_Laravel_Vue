<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    /** @use HasFactory<\Database\Factories\StudentFactory> */
    use HasFactory;


    protected $fillable = [
        'full_name',
        'phone',
        'birth_date',
        'balance',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'balance' => 'decimal:2',
    ];




    public function groups()
    {
        return $this->belongsToMany(Group::class)->withTimestamps();
    }


}
