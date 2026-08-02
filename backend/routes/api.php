<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AlertController;
use App\Http\Controllers\Api\V1\AuditController;
use App\Http\Controllers\Api\V1\BackupController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CashSessionController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DocumentSequenceController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PaymentMethodController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\ProductAttributeController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductImageController;
use App\Http\Controllers\Api\V1\ProductPriceController;
use App\Http\Controllers\Api\V1\ProductSerialController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\SaleController;
use App\Http\Controllers\Api\V1\SessionController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\StockController;
use App\Http\Controllers\Api\V1\SupplierContactController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\SupplierProductController;
use App\Http\Controllers\Api\V1\TaxRateController;
use App\Http\Controllers\Api\V1\TransferController;
use App\Http\Controllers\Api\V1\UnitController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\UserPermissionController;
use App\Http\Controllers\Api\V1\WarehouseController;
use App\Http\Controllers\Api\V1\WarehouseTypeController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Authentification (login / logout)
    require __DIR__.'/auth.php';

    Route::middleware('auth:sanctum')->group(function () {
        // Utilisateur courant + permissions effectives
        Route::get('user', MeController::class)->name('me');

        // Vue globale consolidée (direction)
        Route::get('dashboard', [DashboardController::class, 'index'])
            ->middleware('can:stock.view_global')
            ->name('dashboard');

        // Catalogue — taux de TVA (paramétrage)
        Route::get('tax-rates', [TaxRateController::class, 'index'])->middleware('can:tax_rate.view');
        Route::post('tax-rates', [TaxRateController::class, 'store'])->middleware('can:tax_rate.manage');
        Route::put('tax-rates/{taxRate}', [TaxRateController::class, 'update'])->middleware('can:tax_rate.manage');
        Route::delete('tax-rates/{taxRate}', [TaxRateController::class, 'destroy'])->middleware('can:tax_rate.manage');

        // Catalogue — unités
        Route::get('units', [UnitController::class, 'index'])->middleware('can:unit.view');
        Route::post('units', [UnitController::class, 'store'])->middleware('can:unit.manage');
        Route::put('units/{unit}', [UnitController::class, 'update'])->middleware('can:unit.manage');
        Route::delete('units/{unit}', [UnitController::class, 'destroy'])->middleware('can:unit.manage');

        // Catalogue — marques
        Route::get('brands', [BrandController::class, 'index'])->middleware('can:brand.view');
        Route::post('brands', [BrandController::class, 'store'])->middleware('can:brand.manage');
        Route::put('brands/{brand}', [BrandController::class, 'update'])->middleware('can:brand.manage');
        Route::delete('brands/{brand}', [BrandController::class, 'destroy'])->middleware('can:brand.manage');
        Route::post('brands/{brand}/logo', [BrandController::class, 'logo'])->middleware('can:brand.manage');

        // Catalogue — catégories
        Route::get('categories', [CategoryController::class, 'index'])->middleware('can:category.view');
        Route::get('categories/export', [CategoryController::class, 'export'])->middleware('can:category.view');
        Route::post('categories', [CategoryController::class, 'store'])->middleware('can:category.create');
        Route::post('categories/bulk-delete', [CategoryController::class, 'bulkDestroy'])->middleware('can:category.delete');
        Route::patch('categories/reorder', [CategoryController::class, 'reorder'])->middleware('can:category.update');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->middleware('can:category.update');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->middleware('can:category.delete');

        // Catalogue — articles
        Route::get('products', [ProductController::class, 'index'])->middleware('can:product.view');
        Route::get('products/export', [ProductController::class, 'export'])->middleware('can:product.view');
        Route::post('products/import', [ProductController::class, 'import'])->middleware('can:product.import');
        Route::post('products/bulk-delete', [ProductController::class, 'bulkDestroy'])->middleware('can:product.delete');
        Route::get('products/{product}', [ProductController::class, 'show'])->middleware('can:product.view');
        Route::post('products', [ProductController::class, 'store'])->middleware('can:product.create');
        Route::put('products/{product}', [ProductController::class, 'update'])->middleware('can:product.update');
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->middleware('can:product.delete');
        // Tarifs de vente (prix unique — conservé)
        Route::put('products/{product}/pricing', [ProductController::class, 'updatePricing'])->middleware('can:product.set_price');

        // Catalogue — fiche technique (attributs + modèle par catégorie)
        Route::get('products/{product}/attributes', [ProductAttributeController::class, 'index'])->middleware('can:product.view');
        Route::put('products/{product}/attributes', [ProductAttributeController::class, 'save'])->middleware('can:product.attributes_manage');
        Route::put('categories/{categoryId}/attribute-template', [ProductAttributeController::class, 'saveTemplate'])
            ->whereNumber('categoryId')->middleware('can:product.attributes_manage');

        // Catalogue — médias des articles
        Route::get('products/{product}/images', [ProductImageController::class, 'index'])->middleware('can:product.view');
        Route::post('products/{product}/images', [ProductImageController::class, 'store'])->middleware('can:product.media_manage');
        Route::patch('products/{product}/images/{image}/main', [ProductImageController::class, 'setMain'])->middleware('can:product.media_manage');
        Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy'])->middleware('can:product.media_manage');

        // Catalogue — numéros de série
        Route::get('products/{product}/serials', [ProductSerialController::class, 'index'])->middleware('can:serial.view');
        Route::post('products/{product}/serials', [ProductSerialController::class, 'store'])->middleware('can:product.update');
        Route::delete('products/{product}/serials/{serial}', [ProductSerialController::class, 'destroy'])->middleware('can:product.update');

        // Tarifs 3 niveaux (detail / demi-gros / gros)
        Route::get('price-types', [ProductPriceController::class, 'priceTypes']);
        Route::get('prices', [ProductPriceController::class, 'list'])->middleware('can:price.view');
        Route::post('prices/bulk-update', [ProductPriceController::class, 'bulkUpdate'])->middleware('can:price.bulk_update');
        Route::get('prices/export', [ProductPriceController::class, 'export'])->middleware('can:price.view');
        Route::get('products/{product}/prices', [ProductPriceController::class, 'index'])->middleware('can:price.view');
        Route::put('products/{product}/prices', [ProductPriceController::class, 'update'])->middleware('can:price.manage');
        Route::get('products/{product}/prices/history', [ProductPriceController::class, 'history'])->middleware('can:price.view');
        Route::get('prices/below-floor', [ProductPriceController::class, 'belowFloor'])->middleware('can:price.view');

        // Accès — utilisateurs
        Route::get('users', [UserController::class, 'index'])->middleware('can:user.view');
        Route::post('users', [UserController::class, 'store'])->middleware('can:user.create');
        Route::get('users/{user}', [UserController::class, 'show'])->middleware('can:user.view');
        Route::put('users/{user}', [UserController::class, 'update'])->middleware('can:user.update');
        Route::patch('users/{user}/toggle', [UserController::class, 'toggle'])->middleware('can:user.deactivate');
        Route::put('users/{user}/roles', [UserController::class, 'roles'])->middleware('can:user.assign_role');
        Route::post('users/{user}/resend-invitation', [UserController::class, 'resendInvitation'])->middleware('can:user.update');
        Route::post('users/{user}/force-password-reset', [UserController::class, 'forcePasswordReset'])->middleware('can:user.update');

        // Accès — journal d'audit
        Route::get('audit', [AuditController::class, 'index'])->middleware('can:audit.view');
        Route::get('audit/filters', [AuditController::class, 'filters'])->middleware('can:audit.view');
        Route::get('audit/export', [AuditController::class, 'export'])->middleware('can:audit.export');

        // Accès — sessions actives & déconnexion forcée
        Route::get('sessions', [SessionController::class, 'index'])->middleware('can:user.manage_sessions');
        Route::delete('sessions/{session}', [SessionController::class, 'destroy'])->middleware('can:user.manage_sessions');
        Route::delete('users/{user}/sessions', [SessionController::class, 'destroyForUser'])->middleware('can:user.manage_sessions');

        // Paramètres généraux
        Route::get('settings', [SettingController::class, 'index'])->middleware('can:settings.view');
        Route::put('settings', [SettingController::class, 'update'])->middleware('can:settings.manage');
        Route::get('payment-methods', [PaymentMethodController::class, 'index'])->middleware('can:payment_method.view');
        Route::post('payment-methods', [PaymentMethodController::class, 'store'])->middleware('can:payment_method.manage');
        Route::put('payment-methods/{paymentMethod}', [PaymentMethodController::class, 'update'])->middleware('can:payment_method.manage');
        Route::delete('payment-methods/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->middleware('can:payment_method.manage');
        Route::get('document-sequences', [DocumentSequenceController::class, 'index'])->middleware('can:settings.view');
        Route::put('document-sequences/{documentSequence}', [DocumentSequenceController::class, 'update'])->middleware('can:settings.manage');
        Route::get('backup', [BackupController::class, 'download'])->middleware('can:system.backup');

        // Accès — rôles & permissions
        Route::get('roles', [RoleController::class, 'index'])->middleware('can:role.view');
        Route::post('roles', [RoleController::class, 'store'])->middleware('can:role.manage');
        Route::put('roles/{role}', [RoleController::class, 'update'])->middleware('can:role.manage');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('can:role.manage');
        Route::put('roles/{role}/permissions', [RoleController::class, 'permissions'])->middleware('can:role.manage');
        Route::post('roles/{role}/duplicate', [RoleController::class, 'duplicate'])->middleware('can:role.manage');
        Route::get('permissions', [PermissionController::class, 'index'])->middleware('can:permission.view');

        // Accès — dérogations individuelles de permissions
        Route::get('users/{user}/permissions', [UserPermissionController::class, 'index'])->middleware('can:user.view');
        Route::post('users/{user}/permissions', [UserPermissionController::class, 'store'])->middleware('can:user.manage_permissions');
        Route::delete('users/{user}/permissions/{permission}', [UserPermissionController::class, 'destroy'])->middleware('can:user.manage_permissions');

        // Achats — fournisseurs
        Route::get('suppliers', [SupplierController::class, 'index'])->middleware('can:supplier.view');
        Route::post('suppliers', [SupplierController::class, 'store'])->middleware('can:supplier.create');
        Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->middleware('can:supplier.view');
        Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->middleware('can:supplier.update');
        Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->middleware('can:supplier.delete');

        // Achats — contacts fournisseur
        Route::get('suppliers/{supplier}/contacts', [SupplierContactController::class, 'index'])->middleware('can:supplier.view');
        Route::post('suppliers/{supplier}/contacts', [SupplierContactController::class, 'store'])->middleware('can:supplier.update');
        Route::put('suppliers/{supplier}/contacts/{contact}', [SupplierContactController::class, 'update'])->middleware('can:supplier.update');
        Route::delete('suppliers/{supplier}/contacts/{contact}', [SupplierContactController::class, 'destroy'])->middleware('can:supplier.update');

        // Achats — articles référencés chez le fournisseur + statistiques
        Route::get('suppliers/{supplier}/products', [SupplierProductController::class, 'index'])->middleware('can:supplier.view');
        Route::put('suppliers/{supplier}/products/{product}', [SupplierProductController::class, 'attach'])->middleware('can:supplier.update');
        Route::delete('suppliers/{supplier}/products/{product}', [SupplierProductController::class, 'detach'])->middleware('can:supplier.update');
        Route::get('suppliers/{supplier}/stats', [SupplierProductController::class, 'stats'])->middleware('can:supplier.view');

        // Stock — consultation & bon de sortie
        Route::get('stock', [StockController::class, 'index'])->middleware('can:stock.view');
        Route::get('stock/export', [StockController::class, 'export'])->middleware('can:stock.view');
        Route::get('stock/matrix', [StockController::class, 'matrix'])->middleware('can:stock.view');
        Route::get('stock/matrix/export', [StockController::class, 'matrixExport'])->middleware('can:stock.view');
        Route::get('stock/movements', [StockController::class, 'movements'])->middleware('can:stock.view');
        Route::get('stock/movement-types', [StockController::class, 'movementTypes'])->middleware('can:stock.view');
        Route::post('stock/entry', [StockController::class, 'entry'])->middleware('can:stock.entry');
        Route::post('stock/issue', [StockController::class, 'issue'])->middleware('can:stock.issue');
        Route::post('stock/adjust', [StockController::class, 'adjust'])->middleware('can:stock.adjust');
        Route::post('stock/return', [StockController::class, 'returnIn'])->middleware('can:stock.entry');

        // Transferts inter-lieux (alerte transit > 3 jours incluse)
        Route::get('transfers', [TransferController::class, 'index'])->middleware('can:stock.view');
        Route::post('transfers', [TransferController::class, 'store'])->middleware('can:transfer.create');
        Route::get('transfers/{transfer}', [TransferController::class, 'show'])->middleware('can:stock.view');
        Route::post('transfers/{transfer}/receive', [TransferController::class, 'receive'])->middleware('can:transfer.receive');

        // Achats — bons de commande, réceptions, retours, réappro
        Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->middleware('can:purchase.create');
        Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->middleware('can:purchase.create');
        Route::get('purchase-orders/replenishment', [PurchaseOrderController::class, 'replenishment'])->middleware('can:purchase.create');
        Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->middleware('can:purchase.create');
        Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->middleware('can:receipt.create');
        Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->middleware('can:purchase.create');
        Route::post('supplier-returns', [PurchaseOrderController::class, 'supplierReturn'])->middleware('can:receipt.create');

        // Ventes — devis & factures
        Route::get('sales', [SaleController::class, 'index'])->middleware('can:sale.create');
        Route::post('sales', [SaleController::class, 'store'])->middleware('can:sale.create');
        Route::get('sales/price', [SaleController::class, 'price'])->middleware('can:sale.create');
        Route::get('sales/{sale}', [SaleController::class, 'show'])->middleware('can:sale.create');
        Route::post('sales/{sale}/confirm', [SaleController::class, 'confirm'])->middleware('can:sale.create');
        Route::post('sales/{sale}/cancel', [SaleController::class, 'cancel'])->middleware('can:sale.cancel');

        // Règlements — encaissements, chèques, balance âgée, relevé
        Route::get('payments', [PaymentController::class, 'index'])->middleware('can:payment.view');
        Route::post('payments', [PaymentController::class, 'store'])->middleware('can:payment.create');
        Route::patch('payments/{payment}/cheque', [PaymentController::class, 'chequeStatus'])->middleware('can:payment.create');
        Route::get('customers-aging', [PaymentController::class, 'aging'])->middleware('can:payment.view');
        Route::get('customers/{customer}/statement', [PaymentController::class, 'statement'])->middleware('can:customer.view');

        // Caisse — sessions
        Route::get('cash-sessions', [CashSessionController::class, 'index'])->middleware('can:cash.manage');
        Route::get('cash-sessions/current', [CashSessionController::class, 'current'])->middleware('can:payment.create');
        Route::post('cash-sessions/open', [CashSessionController::class, 'open'])->middleware('can:cash.manage');
        Route::post('cash-sessions/{cashSession}/close', [CashSessionController::class, 'close'])->middleware('can:cash.manage');

        // Charges
        Route::get('expense-categories', [ExpenseController::class, 'categories'])->middleware('can:expense.create');
        Route::post('expense-categories', [ExpenseController::class, 'storeCategory'])->middleware('can:expense.approve');
        Route::get('expenses', [ExpenseController::class, 'index'])->middleware('can:expense.create');
        Route::post('expenses', [ExpenseController::class, 'store'])->middleware('can:expense.create');
        Route::patch('expenses/{expense}/decide', [ExpenseController::class, 'decide'])->middleware('can:expense.approve');

        // Inventaires physiques (par lieu, avec date et régularisation)
        Route::get('inventories', [InventoryController::class, 'index'])->middleware('can:inventory.create');
        Route::post('inventories', [InventoryController::class, 'store'])->middleware('can:inventory.create');
        Route::get('inventories/{inventory}', [InventoryController::class, 'show'])->middleware('can:inventory.create');
        Route::get('inventories/{inventory}/sheet', [InventoryController::class, 'sheet'])->middleware('can:inventory.create');
        Route::put('inventories/{inventory}/lines', [InventoryController::class, 'saveLines'])->middleware('can:inventory.create');
        Route::post('inventories/{inventory}/approve', [InventoryController::class, 'approve'])->middleware('can:inventory.approve');
        Route::post('inventories/{inventory}/cancel', [InventoryController::class, 'cancel'])->middleware('can:inventory.create');

        // Clients & crédits
        Route::get('customers', [CustomerController::class, 'index'])->middleware('can:customer.view');
        Route::post('customers', [CustomerController::class, 'store'])->middleware('can:customer.create');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->middleware('can:customer.view');
        Route::put('customers/{customer}', [CustomerController::class, 'update'])->middleware('can:customer.update');
        Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->middleware('can:customer.delete');
        Route::put('customers/{customer}/credit', [CustomerController::class, 'setCreditLimit'])->middleware('can:customer.set_credit_limit');
        Route::patch('customers/{customer}/block', [CustomerController::class, 'toggleBlock'])->middleware('can:customer.set_credit_limit');

        // Pilotage — alertes consolidées & rapports
        Route::get('alerts', [AlertController::class, 'index'])->middleware('can:report.consolidated');
        Route::get('reports/sales', [ReportController::class, 'sales'])->middleware('can:report.consolidated');
        Route::get('reports/stock-valuation', [ReportController::class, 'stockValuation'])->middleware('can:report.consolidated');
        Route::get('reports/dormant-products', [ReportController::class, 'dormantProducts'])->middleware('can:report.consolidated');
        Route::get('reports/margins', [ReportController::class, 'margins'])->middleware('can:report.consolidated');

        // Lieux — types (référentiel) et lieux
        Route::get('warehouse-types', [WarehouseTypeController::class, 'index'])->middleware('can:warehouse.view');
        Route::get('warehouses', [WarehouseController::class, 'index'])->middleware('can:warehouse.view');
        Route::get('warehouses/{warehouse}', [WarehouseController::class, 'show'])->middleware('can:warehouse.view');
        Route::get('warehouses/{warehouse}/users', [WarehouseController::class, 'users'])->middleware('can:warehouse.view');
        Route::get('warehouses/{warehouse}/summary', [WarehouseController::class, 'summary'])->middleware('can:warehouse.view');
        Route::post('warehouses/{warehouse}/assign-users', [WarehouseController::class, 'assignUsers'])->middleware('can:warehouse.assign_users');
        Route::post('warehouses', [WarehouseController::class, 'store'])->middleware('can:warehouse.create');
        Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update'])->middleware('can:warehouse.update');
        Route::patch('warehouses/{warehouse}/toggle', [WarehouseController::class, 'toggle'])->middleware('can:warehouse.manage');
    });
});
