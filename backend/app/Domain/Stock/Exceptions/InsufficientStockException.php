<?php

declare(strict_types=1);

namespace App\Domain\Stock\Exceptions;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class InsufficientStockException extends RuntimeException
{
    /**
     * Le message nomme l'article et le lieu.
     *
     * Il portait leurs identifiants — « l'article 334 dans le lieu 4 » —,
     * c'est-à-dire des chiffres que personne ne connaît : le vendeur devait
     * deviner de quelle ligne de son bon on lui parlait. Les identifiants
     * restent dans les données, le message prend les noms.
     */
    public static function for(int $warehouseId, int $productId, int $available, int $requested): self
    {
        return new self(sprintf(
            'Stock insuffisant pour %s dans %s : %d disponible(s), %d demandé(s).',
            self::article($productId),
            self::lieu($warehouseId),
            $available,
            $requested,
        ));
    }

    /**
     * Nom de l'article, sa référence à défaut.
     *
     * La lecture se fait sans Eloquent et sans portée de lieu : le message est
     * construit sur le chemin d'erreur, au milieu d'une transaction, et ne doit
     * rien déclencher d'autre. Si l'article a disparu, l'identifiant reste la
     * seule prise possible — mieux vaut un numéro qu'un blanc.
     */
    private static function article(int $id): string
    {
        $produit = DB::table('products')->where('id', $id)->first(['sku', 'name']);

        $libelle = trim((string) ($produit->name ?? '')) !== ''
            ? (string) $produit->name
            : trim((string) ($produit->sku ?? ''));

        return $libelle !== '' ? '« '.$libelle.' »' : "l'article #".$id;
    }

    private static function lieu(int $id): string
    {
        $lieu = DB::table('warehouses')->where('id', $id)->first(['code', 'name']);

        $libelle = trim((string) ($lieu->code ?? '')) !== ''
            ? (string) $lieu->code
            : trim((string) ($lieu->name ?? ''));

        return $libelle !== '' ? $libelle : 'le lieu #'.$id;
    }
}
