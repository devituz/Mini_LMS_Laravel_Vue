<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends BaseModel
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


//
//
//    public function groups()
//    {
//        return $this->belongsToMany(Group::class)->withTimestamps();
//    }

// Student.php
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_student')
            ->using(GroupStudent::class)
            ->withTimestamps();
    }



}
