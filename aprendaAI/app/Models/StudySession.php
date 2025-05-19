<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudySession extends Model
{
    use HasFactory;

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'study_plan_id',
        'topic_id',
        'duration',
        'scheduled_date',
        'status',
        'is_completed',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'duration' => 'integer',
        'scheduled_date' => 'date',
        'status' => 'string',
        'is_completed' => 'boolean',
    ];

    /**
     * Get the study plan that owns the session.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function studyPlan(): BelongsTo
    {
        return $this->belongsTo(StudyPlan::class);
    }

    /**
     * Get the topic associated with the session.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Check if the session is completed.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the session is pending.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the session is skipped.
     *
     * @return bool
     */
    public function isSkipped(): bool
    {
        return $this->status === 'skipped';
    }

    /**
     * Check if the session is overdue.
     *
     * @return bool
     */
    public function isOverdue(): bool
    {
        return $this->isPending() && $this->scheduled_date < now()->startOfDay();
    }

    /**
     * Check if the session is due today.
     *
     * @return bool
     */
    public function isDueToday(): bool
    {
        return $this->isPending() && $this->scheduled_date->isToday();
    }

    /**
     * Scope a query to only include sessions with a specific status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include upcoming sessions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', 'pending')
            ->where('scheduled_date', '>=', now()->startOfDay())
            ->orderBy('scheduled_date', 'asc');
    }

    /**
     * Scope a query to only include overdue sessions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->where('scheduled_date', '<', now()->startOfDay())
            ->orderBy('scheduled_date', 'asc');
    }
}
