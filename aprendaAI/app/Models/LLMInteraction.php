<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LLMInteraction extends Model
{
    use HasFactory;
    
    /**
     * Nome da tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'llm_interactions';

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'interaction_type',
        'prompt',
        'response',
        'tokens_used',
        'model_used',
        'duration_ms',
        'metadata',
        'options',
        'status',
        'context_type',
        'context_id',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'options' => 'array',
        'tokens_used' => 'integer',
        'response' => 'array',
        'duration_ms' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the interaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|null
     */
    public function user(): ?BelongsTo
    {
        return $this->user_id ? $this->belongsTo(User::class) : null;
    }

    /**
     * Check if the interaction was successful.
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }
    
    /**
     * Scope a query to filter by interaction type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('interaction_type', $type);
    }

    /**
     * Scope a query to only include successful interactions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope a query to only include failed interactions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Calculate average response time.
     *
     * @param string|null $interactionType
     * @return float
     */
    public static function averageResponseTime(?string $interactionType = null)
    {
        $query = self::query();
        
        if ($interactionType) {
            $query->where('interaction_type', $interactionType);
        }
        
        return $query->avg('duration_ms') ?? 0;
    }

    /**
     * Calculate total tokens used.
     *
     * @param string|null $interactionType
     * @return int
     */
    public static function totalTokensUsed(?string $interactionType = null)
    {
        $query = self::query();
        
        if ($interactionType) {
            $query->where('interaction_type', $interactionType);
        }
        
        return (int) $query->sum('tokens_used') ?? 0;
    }
}
