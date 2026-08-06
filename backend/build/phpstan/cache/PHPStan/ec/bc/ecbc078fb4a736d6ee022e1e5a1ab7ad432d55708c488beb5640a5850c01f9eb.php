<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Purchasing\Models\PurchaseOrderLine.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Purchasing\Models\PurchaseOrderLine
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-0e59c659538443a428bd13d700318db7d603e5b9a623312278e41c5c79d38ce9',
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
 * Ligne de bon de commande (reliquat = quantity − received_quantity).
 *
 * @property int $id
 * @property int $purchase_order_id
 * @property int $product_id
 * @property int $quantity
 * @property int $received_quantity
 * @property int $position
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 27,
    'endLine' => 83,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
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
          'code' => '[\'purchase_order_id\', \'product_id\', \'quantity\', \'received_quantity\', \'position\']',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 38,
            'startTokenPos' => 77,
            'startFilePos' => 865,
            'endTokenPos' => 94,
            'endFilePos' => 991,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 38,
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
        'startLine' => 43,
        'endLine' => 50,
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
        'startLine' => 55,
        'endLine' => 58,
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
        'startLine' => 63,
        'endLine' => 66,
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
        'docComment' => '/**
 * Quantité restante à recevoir.
 */',
        'startLine' => 71,
        'endLine' => 74,
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
      'newFactory' => 
      array (
        'name' => 'newFactory',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Factory<self>
 */',
        'startLine' => 79,
        'endLine' => 82,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
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