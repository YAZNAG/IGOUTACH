<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Purchasing\Models\PurchaseOrder.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Purchasing\Models\PurchaseOrder
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-016846f0bc970b6c40a9e9afee0b65c5efa9285c556ee24ec86fb512c2a19d27',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Purchasing/Models/PurchaseOrder.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Purchasing\\Models',
    'name' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
    'shortName' => 'PurchaseOrder',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Bon de commande fournisseur.
 *
 * @property int $id
 * @property string $reference
 * @property int $supplier_id
 * @property int $warehouse_id
 * @property string $status
 * @property Carbon|null $expected_at
 * @property int|null $created_by
 * @property string|null $note
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 25,
    'endLine' => 78,
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
      'STATUS_DRAFT' => 
      array (
        'declaringClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
        'implementingClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
        'name' => 'STATUS_DRAFT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'draft\'',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 62,
            'startFilePos' => 657,
            'endTokenPos' => 62,
            'endFilePos' => 663,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 40,
      ),
      'STATUS_ORDERED' => 
      array (
        'declaringClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
        'implementingClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
        'name' => 'STATUS_ORDERED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'ordered\'',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 29,
            'startTokenPos' => 73,
            'startFilePos' => 701,
            'endTokenPos' => 73,
            'endFilePos' => 709,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 44,
      ),
      'STATUS_PARTIAL' => 
      array (
        'declaringClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
        'implementingClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
        'name' => 'STATUS_PARTIAL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'partial\'',
          'attributes' => 
          array (
            'startLine' => 31,
            'endLine' => 31,
            'startTokenPos' => 84,
            'startFilePos' => 747,
            'endTokenPos' => 84,
            'endFilePos' => 755,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 44,
      ),
      'STATUS_RECEIVED' => 
      array (
        'declaringClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
        'implementingClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
        'name' => 'STATUS_RECEIVED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'received\'',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 95,
            'startFilePos' => 794,
            'endTokenPos' => 95,
            'endFilePos' => 803,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 46,
      ),
      'STATUS_CANCELLED' => 
      array (
        'declaringClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
        'implementingClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
        'name' => 'STATUS_CANCELLED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'cancelled\'',
          'attributes' => 
          array (
            'startLine' => 35,
            'endLine' => 35,
            'startTokenPos' => 106,
            'startFilePos' => 843,
            'endTokenPos' => 106,
            'endFilePos' => 853,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 48,
      ),
    ),
    'immediateProperties' => 
    array (
      'fillable' => 
      array (
        'declaringClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
        'implementingClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'reference\', \'supplier_id\', \'warehouse_id\', \'status\', \'expected_at\', \'created_by\', \'note\']',
          'attributes' => 
          array (
            'startLine' => 37,
            'endLine' => 45,
            'startTokenPos' => 115,
            'startFilePos' => 883,
            'endTokenPos' => 138,
            'endFilePos' => 1036,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 45,
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
        'startLine' => 50,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Domain\\Purchasing\\Models',
        'declaringClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
        'implementingClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
        'currentClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
        'aliasName' => NULL,
      ),
      'supplier' => 
      array (
        'name' => 'supplier',
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
 * @return BelongsTo<Supplier, $this>
 */',
        'startLine' => 58,
        'endLine' => 61,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Purchasing\\Models',
        'declaringClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
        'implementingClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
        'currentClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
        'aliasName' => NULL,
      ),
      'warehouse' => 
      array (
        'name' => 'warehouse',
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
 * @return BelongsTo<Warehouse, $this>
 */',
        'startLine' => 66,
        'endLine' => 69,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Purchasing\\Models',
        'declaringClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
        'implementingClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
        'currentClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
        'aliasName' => NULL,
      ),
      'lines' => 
      array (
        'name' => 'lines',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return HasMany<PurchaseOrderLine, $this>
 */',
        'startLine' => 74,
        'endLine' => 77,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Purchasing\\Models',
        'declaringClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
        'implementingClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
        'currentClassName' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
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