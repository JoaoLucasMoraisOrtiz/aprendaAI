<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'order',
        'icon',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the topics associated with this subject.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    /**
     * Get only active topics for this subject
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activeTopics(): HasMany
    {
        return $this->topics()->where('is_active', true);
    }

    /**
     * Get topics by difficulty level
     *
     * @param string $level
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTopicsByDifficulty(string $level)
    {
        return $this->topics()->where('difficulty_level', $level)->get();
    }
    
    /**
     * Scope a query to only include active subjects.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
