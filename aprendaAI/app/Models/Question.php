<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'topic_id',
        'content',
        'difficulty_level',
        'type',
        'explanation',
        'is_active',
        'llm_explanation',
        'llm_metadata',
        'is_llm_generated',
        'difficulty_score',
        'topic_tags'
    ];

    /**
     * Os valores que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_llm_generated' => 'boolean',
        'difficulty_score' => 'float',
        'llm_metadata' => 'array',
        'topic_tags' => 'array',
    ];

    /**
     * Get the topic that owns the question.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Get the exams associated with this question.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function exams()
    {
        return $this->belongsToMany(Exam::class)
                    ->using(ExamQuestion::class);
    }

    /**
     * Get the answers for this question.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    /**
     * Get the user answers for this question.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userAnswers(): HasMany
    {
        return $this->hasMany(UserAnswer::class);
    }

    /**
     * Get the correct answers for this question.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function correctAnswers()
    {
        return $this->answers()->where('is_correct', true)->get();
    }

    /**
     * Scope a query to filter questions by difficulty level.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $level
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDifficulty($query, $level)
    {
        return $query->where('difficulty_level', $level);
    }

    /**
     * Scope a query to filter questions by type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the explanation caches for this question.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function explanationCaches(): HasMany
    {
        return $this->hasMany(ExplanationCache::class);
    }
}
