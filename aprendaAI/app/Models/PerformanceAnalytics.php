<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceAnalytics extends Model
{
    use HasFactory;

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'topic_id',
        'analysis_date',
        'mastery_score',
        'performance_data',
        'recommendations',
        'trend',
        'questions_answered',
        'accuracy_rate',
        'average_answer_time',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'analysis_date' => 'date',
        'mastery_score' => 'float',
        'performance_data' => 'array',
        'recommendations' => 'array',
        'accuracy_rate' => 'float',
        'average_answer_time' => 'integer',
    ];

    /**
     * Get the user that owns the performance analytics.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the topic that the performance analytics is for.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|null
     */
    public function topic(): ?BelongsTo
    {
        return $this->topic_id ? $this->belongsTo(Topic::class) : null;
    }

    /**
     * Check if there's an improvement in performance.
     *
     * @return bool
     */
    public function isImproving(): bool
    {
        return $this->trend === 'improving';
    }

    /**
     * Check if performance is declining.
     *
     * @return bool
     */
    public function isDeclining(): bool
    {
        return $this->trend === 'declining';
    }
}
