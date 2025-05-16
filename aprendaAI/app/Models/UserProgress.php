<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProgress extends Model
{
    use HasFactory;

    /**
     * Nome da tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'user_progress';

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'topic_id',
        'proficiency_level',
        'last_activity_date',
        'mastery_status',
        'questions_answered',
        'questions_correct',
        'last_interaction',
        'adaptive_recommendations',
        'focus_areas',
        'learning_streak',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'proficiency_level' => 'float',
        'last_activity_date' => 'date',
        'questions_answered' => 'integer',
        'questions_correct' => 'integer',
        'last_interaction' => 'datetime',
        'adaptive_recommendations' => 'array',
        'focus_areas' => 'array',
        'learning_streak' => 'integer',
    ];

    /**
     * Get the user that owns the progress.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the topic that owns the progress.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Check if the user has mastered this topic.
     *
     * @return bool
     */
    public function isMastered(): bool
    {
        return $this->proficiency_level >= 90 && $this->mastery_status === 'hard';
    }

    /**
     * Scope a query to only include progress records with a certain proficiency level.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  float  $level
     * @param  string  $operator
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithProficiencyLevel($query, $level, $operator = '>=')
    {
        return $query->where('proficiency_level', $operator, $level);
    }

    /**
     * Scope a query to only include progress records that have been recently updated.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecentlyUpdated($query, $days = 7)
    {
        return $query->where('last_interaction', '>=', now()->subDays($days));
    }

    /**
     * Scope a query to filter by mastery status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByMasteryStatus($query, string $status)
    {
        return $query->where('mastery_status', $status);
    }

    /**
     * Scope a query to filter by proficiency range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $min
     * @param int $max
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByProficiencyRange($query, int $min, int $max)
    {
        return $query->whereBetween('proficiency_level', [$min, $max]);
    }

    /**
     * Scope a query to get recently active progress within the specified number of days.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecentlyActive($query, int $days = 7)
    {
        $cutoffDate = now()->subDays($days);
        return $query->where('last_activity_date', '>=', $cutoffDate);
    }
}
