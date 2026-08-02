<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Stock\Models\InventoryLine.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Stock\Models\InventoryLine
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-ddc72c5d3a5f0d6e4113e8165a8fd8f4faef6916162b6685f18451a5e930e526',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Stock\\Models\\InventoryLine',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Stock/Models/InventoryLine.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Stock\\Models',
    'name' => 'App\\Domain\\Stock\\Models\\InventoryLine',
    'shortName' => 'InventoryLine',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @property int $id
 * @property int $inventory_id
 * @property int $product_id
 * @property int $counted_quantity
 * @property int $system_quantity
 * @property int $difference
 * @property string|null $reason
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 20,
    'endLine' => 55,
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
        'declaringClassName' => 'App\\Domain\\Stock\\Models\\InventoryLine',
        'implementingClassName' => 'App\\Domain\\Stock\\Models\\InventoryLine',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'inventory_id\', \'product_id\', \'counted_quantity\', \'system_quantity\', \'difference\', \'reason\']',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 29,
            'startTokenPos' => 48,
            'startFilePos' => 485,
            'endTokenPos' => 68,
            'endFilePos' => 632,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
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
        'docComment' => NULL,
        'startLine' => 31,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Domain\\Stock\\Models',
        'declaringClassName' => 'App\\Domain\\Stock\\Models\\InventoryLine',
        'implementingClassName' => 'App\\Domain\\Stock\\Models\\InventoryLine',
        'currentClassName' => 'App\\Domain\\Stock\\Models\\InventoryLine',
        'aliasName' => NULL,
      ),
      'inventory' => 
      array (
        'name' => 'inventory',
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
 * @return BelongsTo<Inventory, $this>
 */',
        'startLine' => 43,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Stock\\Models',
        'declaringClassName' => 'App\\Domain\\Stock\\Models\\InventoryLine',
        'implementingClassName' => 'App\\Domain\\Stock\\Models\\InventoryLine',
        'currentClassName' => 'App\\Domain\\Stock\\Models\\InventoryLine',
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
        'startLine' => 51,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Stock\\Models',
        'declaringClassName' => 'App\\Domain\\Stock\\Models\\InventoryLine',
        'implementingClassName' => 'App\\Domain\\Stock\\Models\\InventoryLine',
        'currentClassName' => 'App\\Domain\\Stock\\Models\\InventoryLine',
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