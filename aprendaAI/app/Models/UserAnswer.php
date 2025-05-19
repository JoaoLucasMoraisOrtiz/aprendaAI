<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAnswer extends Model
{
    use HasFactory;

    /**
     * Nome da tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'user_answers';

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'question_id',
        'answer_id',
        'is_correct',
        'time_spent',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_correct' => 'boolean',
        'time_spent' => 'integer',
    ];

    /**
     * Get the user that owns the answer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the question that owns the answer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Get the selected answer for this user answer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|null
     */
    public function selectedAnswer(): ?BelongsTo
    {
        return $this->belongsTo(Answer::class, 'answer_id');
    }

    /**
     * Scope a query to only include correct answers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCorrect($query)
    {
        return $query->where('is_correct', true);
    }

    /**
     * Scope a query to only include incorrect answers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIncorrect($query)
    {
        return $query->where('is_correct', false);
    }

    /**
     * Scope a query to only include answers from a specific period.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $period
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFromPeriod($query, $period = 'week')
    {
        if ($period === 'week') {
            return $query->where('created_at', '>=', now()->subWeek());
        } elseif ($period === 'month') {
            return $query->where('created_at', '>=', now()->subMonth());
        } elseif ($period === 'year') {
            return $query->where('created_at', '>=', now()->subYear());
        }
        
        return $query;
    }
}
