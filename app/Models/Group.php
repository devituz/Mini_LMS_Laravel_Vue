<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    /** @use HasFactory<\Database\Factories\GroupFactory> */
    use HasFactory;


    protected $fillable = [
        'name',
        'teacher_id',
        'monthly_fee',
        'start_date',
        'time',
    ];

    protected $casts = [
        'monthly_fee' => 'decimal:2',
        'start_date' => 'date',
    ];

    public function teacher(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }


    // Group.php
    public function students()
    {
        return $this->belongsToMany(Student::class)
            ->using(GroupStudent::class) // <- bu yerda modeldan foydalanish
            ->withTimestamps();          // Agar jadvalda created_at bor bo‘lsa
    }


}
