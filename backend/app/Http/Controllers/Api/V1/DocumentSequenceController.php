<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Settings\Models\DocumentSequence;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Numérotation des documents : préfixe + compteur courant par type de document.
 */
final class DocumentSequenceController extends Controller
{
    /**
     * @return array{data: array<int, array<string, mixed>>}
     */
    public function index(): array
    {
        $rows = DocumentSequence::query()
            ->orderBy('key')
            ->get()
            ->map(fn (DocumentSequence $s): array => [
                'id' => $s->id,
                'key' => $s->key,
                'prefix' => $s->prefix,
                'current' => $s->current,
            ])
            ->values()
            ->all();

        return ['data' => $rows];
    }

    public function update(Request $request, DocumentSequence $documentSequence): JsonResponse
    {
        /** @var array{prefix: string, current: int} $data */
        $data = $request->validate([
            'prefix' => ['required', 'string', 'max:20'],
            'current' => ['required', 'integer', 'min:0'],
        ]);

        $documentSequence->update($data);

        return response()->json(['data' => [
            'id' => $documentSequence->id,
            'key' => $documentSequence->key,
            'prefix' => $documentSequence->prefix,
            'current' => $documentSequence->current,
        ]]);
    }
}
