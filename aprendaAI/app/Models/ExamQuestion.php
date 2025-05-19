<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ExamQuestion extends Pivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'exam_question';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'exam_id',
        'question_id',
    ];
}
