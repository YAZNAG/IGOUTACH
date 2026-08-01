<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\app\Domain\Stock\Models\InventoryLine.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Stock\Models\InventoryLine
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-64c7efe86421aa22ff86d3e5eb8167e104f33bc5945f10af6b76634beb253df8',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Stock\\Models\\InventoryLine',
        'filename' => 'C:/OPTIZAWORKS/igoutech/app/Domain/Stock/Models/InventoryLine.php',
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
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 53,
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
          'code' => '[\'inventory_id\', \'product_id\', \'counted_quantity\', \'system_quantity\', \'difference\']',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 27,
            'startTokenPos' => 48,
            'startFilePos' => 452,
            'endTokenPos' => 65,
            'endFilePos' => 581,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 27,
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
        'startLine' => 29,
        'endLine' => 36,
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
        'startLine' => 41,
        'endLine' => 44,
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
        'startLine' => 49,
        'endLine' => 52,
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