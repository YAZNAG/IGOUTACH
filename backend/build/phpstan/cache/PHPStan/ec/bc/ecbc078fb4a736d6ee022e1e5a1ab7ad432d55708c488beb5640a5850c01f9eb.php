<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Purchasing\Models\PurchaseOrderLine.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Purchasing\Models\PurchaseOrderLine
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-b9eec9bbe268c35a5eb9a342bdd99dbfefcd696f2532fd8e0ca8969d5ab99799',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrderLine',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Purchasing/Models/PurchaseOrderLine.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Purchasing\\Models',
    'name' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrderLine',
    'shortName' => 'PurchaseOrderLine',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Ligne de bon de commande (reliquat = commandé − reçu).
 *
 * @property int $id
 * @property int $purchase_order_id
 * @property int $product_id
 * @property int $quantity_ordered
 * @property int $quantity_received
 * @property string $unit_price
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 63,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'fillable' => 
      array (
        'declaringClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrderLine',
        'implementingClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrderLine',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'purchase_order_id\', \'product_id\', \'quantity_ordered\', \'quantity_received\', \'unit_price\']',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 29,
            'startTokenPos' => 50,
            'startFilePos' => 542,
            'endTokenPos' => 67,
            'endFilePos' => 678,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 6,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      'casts' => 
      array (
        'name' => 'casts',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return array<string, string>
 */',
        'startLine' => 34,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Domain\\Purchasing\\Models',
        'declaringClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrderLine',
        'implementingClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrderLine',
        'currentClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrderLine',
        'aliasName' => NULL,
      ),
      'purchaseOrder' => 
      array (
        'name' => 'purchaseOrder',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return BelongsTo<PurchaseOrder, $this>
 */',
        'startLine' => 46,
        'endLine' => 49,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Purchasing\\Models',
        'declaringClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrderLine',
        'implementingClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrderLine',
        'currentClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrderLine',
        'aliasName' => NULL,
      ),
      'product' => 
      array (
        'name' => 'product',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return BelongsTo<Product, $this>
 */',
        'startLine' => 54,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Purchasing\\Models',
        'declaringClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrderLine',
        'implementingClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrderLine',
        'currentClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrderLine',
        'aliasName' => NULL,
      ),
      'remaining' => 
      array (
        'name' => 'remaining',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 59,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Purchasing\\Models',
        'declaringClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrderLine',
        'implementingClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrderLine',
        'currentClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrderLine',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));