<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyPlan extends Model
{
    use HasFactory;

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'goal',
        'start_date',
        'end_date',
        'metadata',
        'status',
        'is_adaptive',
        'priority'
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'metadata' => 'array',
        'is_adaptive' => 'boolean',
    ];

    /**
     * Get the user that owns the study plan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * This comment is kept for documentation purposes, but the actual scopeActive method
     * is defined later in this class with more complete implementation
     */

    /**
     * Scope a query to only include adaptive study plans.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAdaptive($query)
    {
        return $query->where('is_adaptive', true);
    }

    /**
     * Get the study sessions associated with this plan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(StudySession::class);
    }

    /**
     * Get the number of completed sessions in the plan.
     *
     * @return int
     */
    public function getCompletedSessionsCount(): int
    {
        return $this->sessions()
            ->where('is_completed', true)
            ->count();
    }

    /**
     * Get the total number of sessions in the plan.
     *
     * @return int
     */
    public function getTotalSessionsCount(): int
    {
        return $this->sessions()->count();
    }

    /**
     * Get the completion percentage of the study plan.
     *
     * @return float
     */
    public function getCompletionPercentage(): float
    {
        $totalSessions = $this->getTotalSessionsCount();
        
        if ($totalSessions === 0) {
            return 0;
        }
        
        return ($this->getCompletedSessionsCount() / $totalSessions) * 100;
    }
    
    /**
     * Alias for getCompletionPercentage.
     *
     * @return float
     */
    public function progressPercentage(): float
    {
        return $this->getCompletionPercentage();
    }

    /**
     * Check if the study plan is completed.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        if ($this->status === 'completed') {
            return true;
        }
        
        return $this->getCompletedSessionsCount() === $this->getTotalSessionsCount();
    }

    /**
     * Check if the study plan is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }
        
        $today = now()->startOfDay();
        return $today >= $this->start_date && $today <= $this->end_date;
    }

    /**
     * Check if the study plan is abandoned.
     *
     * @return bool
     */
    public function isAbandoned(): bool
    {
        return $this->status === 'abandoned';
    }

    /**
     * Get the next scheduled session.
     *
     * @return \App\Models\StudySession|null
     */
    public function getNextSession(): ?StudySession
    {
        return $this->sessions()
            ->where('status', 'pending')
            ->where('scheduled_date', '>=', now())
            ->orderBy('scheduled_date', 'asc')
            ->first();
    }

    /**
     * Get all overdue sessions.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOverdueSessions()
    {
        return $this->sessions()
            ->where('status', 'pending')
            ->where('scheduled_date', '<', now())
            ->orderBy('scheduled_date', 'asc')
            ->get();
    }

    /**
     * Scope a query to only include active study plans.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include study plans for the current week.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCurrentWeek($query)
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        return $query->where(function ($q) use ($startOfWeek, $endOfWeek) {
            $q->whereBetween('start_date', [$startOfWeek, $endOfWeek])
              ->orWhereBetween('end_date', [$startOfWeek, $endOfWeek])
              ->orWhere(function ($q2) use ($startOfWeek, $endOfWeek) {
                  $q2->where('start_date', '<', $startOfWeek)
                     ->where('end_date', '>', $endOfWeek);
              });
        });
    }
}
