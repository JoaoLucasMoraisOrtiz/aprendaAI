<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentRecommendation extends Model
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
        'question_id',
        'content',
        'type',
        'priority',
        'metadata',
        'viewed',
        'applied',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'viewed' => 'boolean',
        'applied' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Get the user that owns the recommendation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the topic that the recommendation is for.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|null
     */
    public function topic(): ?BelongsTo
    {
        return $this->topic_id ? $this->belongsTo(Topic::class) : null;
    }

    /**
     * Get the question that the recommendation is for.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|null
     */
    public function question(): ?BelongsTo
    {
        return $this->question_id ? $this->belongsTo(Question::class) : null;
    }
}
