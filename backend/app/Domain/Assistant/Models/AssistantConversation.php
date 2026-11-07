<?php

declare(strict_types=1);

namespace App\Domain\Assistant\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un fil de questions à l'assistant, sur un module, pour un utilisateur.
 *
 * @property int $id
 * @property int $user_id
 * @property string $module
 * @property string $title
 */
class AssistantConversation extends Model
{
    protected $fillable = ['user_id', 'module', 'title'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<AssistantMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(AssistantMessage::class, 'conversation_id')->orderBy('id');
    }
}
