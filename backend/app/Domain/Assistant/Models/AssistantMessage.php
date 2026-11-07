<?php

declare(strict_types=1);

namespace App\Domain\Assistant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un message d'une conversation : la question de l'utilisateur ou la
 * réponse de l'assistant (avec le modèle qui l'a produite).
 *
 * @property int $id
 * @property int $conversation_id
 * @property string $role
 * @property string $content
 * @property string|null $model
 */
class AssistantMessage extends Model
{
    public const ROLE_USER = 'user';

    public const ROLE_ASSISTANT = 'assistant';

    protected $fillable = ['conversation_id', 'role', 'content', 'model'];

    /** @return BelongsTo<AssistantConversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AssistantConversation::class, 'conversation_id');
    }
}
