<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Exceptions\ProductInUseException;
use App\Domain\Catalog\Models\Product;
use App\Domain\Stock\Models\InventoryLine;
use App\Domain\Stock\Models\Stock;
use App\Domain\Stock\Models\StockMovement;
use App\Domain\Stock\Models\TransferLine;

final class DeleteProductAction
{
    /**
     * Supprime (soft delete) un article uniquement s'il n'est engagé nulle part :
     * aucun mouvement de stock, aucun transfert, aucun inventaire, aucun stock non nul.
     *
     * @throws ProductInUseException
     */
    public function execute(Product $product): void
    {
        $id = $product->id;

        if (StockMovement::withoutGlobalScopes()->where('product_id', $id)->exists()) {
            throw ProductInUseException::make('des mouvements de stock existent pour cet article');
        }

        if (Stock::withoutGlobalScopes()->where('product_id', $id)->where('quantity', '!=', 0)->exists()) {
            throw ProductInUseException::make('un stock non nul existe encore dans un lieu');
        }

        if (TransferLine::where('product_id', $id)->exists()) {
            throw ProductInUseException::make('cet article figure dans un transfert');
        }

        if (InventoryLine::where('product_id', $id)->exists()) {
            throw ProductInUseException::make('cet article figure dans un inventaire');
        }

        $product->delete();
    }
}
