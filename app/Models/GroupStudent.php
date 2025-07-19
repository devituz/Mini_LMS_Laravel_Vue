<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class GroupStudent extends Pivot
{
    protected $table = 'group_student';

    protected $fillable = [
        'group_id',
        'student_id',
        'created_at',
        'updated_at',
    ];

    public $timestamps = true;

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
