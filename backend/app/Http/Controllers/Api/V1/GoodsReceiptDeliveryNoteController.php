<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Purchasing\Models\GoodsReceipt;
use App\Http\Controllers\Controller;
use App\Http\Resources\GoodsReceiptDetailResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bon de livraison du fournisseur joint à un bon de réception.
 *
 * Le document arrive en PDF ou en photo (la plupart du temps une photo prise
 * au téléphone au moment de la livraison). Il est gardé sur le disque privé
 * et servi par une route authentifiée : ce n'est pas une image de catalogue.
 */
final class GoodsReceiptDeliveryNoteController extends Controller
{
    private const DISQUE = 'local';

    private const DOSSIER = 'bons-livraison-fournisseurs';

    /**
     * Joint (ou remplace) le bon de livraison.
     * POST /goods-receipts/{goodsReceipt}/delivery-note  (multipart, champ « file »)
     */
    public function store(Request $request, GoodsReceipt $goodsReceipt): JsonResponse
    {
        $request->validate([
            // Les photos de téléphone sont souvent lourdes : 15 Mo laissent
            // passer un cliché plein format sans forcer à le recompresser.
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,heic,heif', 'max:15360'],
        ]);

        $fichier = $request->file('file');
        $extension = strtolower($fichier->getClientOriginalExtension() ?: $fichier->extension() ?: 'bin');
        $chemin = $fichier->storeAs(
            self::DOSSIER.'/'.$goodsReceipt->id,
            'bon-livraison-'.now()->format('Ymd-His').'.'.$extension,
            self::DISQUE,
        );

        if ($chemin === false) {
            return response()->json(['message' => 'Le fichier n’a pas pu être enregistré.'], 500);
        }

        // Un seul bon par réception : l'ancien part quand on en joint un autre.
        $ancien = $goodsReceipt->supplier_note_path;

        $goodsReceipt->forceFill([
            'supplier_note_path' => $chemin,
            'supplier_note_name' => mb_substr($fichier->getClientOriginalName(), 0, 191),
            'supplier_note_mime' => $fichier->getMimeType() ?: $fichier->getClientMimeType(),
        ])->save();

        if ($ancien !== null && $ancien !== $chemin) {
            Storage::disk(self::DISQUE)->delete($ancien);
        }

        $goodsReceipt->load(['purchaseOrder', 'supplier', 'warehouse', 'createdBy']);

        return response()->json(new GoodsReceiptDetailResource($goodsReceipt), 201);
    }

    /**
     * Sert le bon de livraison joint, tel qu'il a été déposé.
     * GET /goods-receipts/{goodsReceipt}/delivery-note
     */
    public function show(GoodsReceipt $goodsReceipt): Response
    {
        if (! $goodsReceipt->aUnBonDeLivraison() || ! Storage::disk(self::DISQUE)->exists((string) $goodsReceipt->supplier_note_path)) {
            abort(404, 'Aucun bon de livraison n’est joint à cette réception.');
        }

        $nom = $goodsReceipt->supplier_note_name ?: basename((string) $goodsReceipt->supplier_note_path);

        return Storage::disk(self::DISQUE)->response(
            (string) $goodsReceipt->supplier_note_path,
            $nom,
            ['Content-Type' => $goodsReceipt->supplier_note_mime ?: 'application/octet-stream'],
        );
    }

    /**
     * Retire le bon de livraison joint.
     * DELETE /goods-receipts/{goodsReceipt}/delivery-note
     */
    public function destroy(GoodsReceipt $goodsReceipt): JsonResponse
    {
        if ($goodsReceipt->aUnBonDeLivraison()) {
            Storage::disk(self::DISQUE)->delete((string) $goodsReceipt->supplier_note_path);
            $goodsReceipt->forceFill([
                'supplier_note_path' => null,
                'supplier_note_name' => null,
                'supplier_note_mime' => null,
            ])->save();
        }

        return response()->json(null, 204);
    }
}
