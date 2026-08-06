<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Stock\Models\Stock.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Stock\Models\Stock
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-fbd9690832af39528fc54d8598263af458b25fe21e75c39a0bbb724d5739b9ec',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Stock\\Models\\Stock',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Stock/Models/Stock.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Stock\\Models',
    'name' => 'App\\Domain\\Stock\\Models\\Stock',
    'shortName' => 'Stock',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * État actuel du stock d\'un article dans un lieu.
 *
 * @property int $id
 * @property int $warehouse_id
 * @property int $product_id
 * @property int $quantity
 * @property int $reserved_quantity
 * @property string $average_cost
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 25,
    'endLine' => 64,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
      1 => 'App\\Support\\Concerns\\BelongsToWarehouse',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'fillable' => 
      array (
        'declaringClassName' => 'App\\Domain\\Stock\\Models\\Stock',
        'implementingClassName' => 'App\\Domain\\Stock\\Models\\Stock',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'warehouse_id\', \'product_id\', \'quantity\', \'reserved_quantity\', \'average_cost\']',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 38,
            'startTokenPos' => 80,
            'startFilePos' => 778,
            'endTokenPos' => 97,
            'endFilePos' => 903,
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
        'docComment' => NULL,
        'startLine' => 40,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Domain\\Stock\\Models',
        'declaringClassName' => 'App\\Domain\\Stock\\Models\\Stock',
        'implementingClassName' => 'App\\Domain\\Stock\\Models\\Stock',
        'currentClassName' => 'App\\Domain\\Stock\\Models\\Stock',
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
        'startLine' => 52,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Stock\\Models',
        'declaringClassName' => 'App\\Domain\\Stock\\Models\\Stock',
        'implementingClassName' => 'App\\Domain\\Stock\\Models\\Stock',
        'currentClassName' => 'App\\Domain\\Stock\\Models\\Stock',
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
        'startLine' => 60,
        'endLine' => 63,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Domain\\Stock\\Models',
        'declaringClassName' => 'App\\Domain\\Stock\\Models\\Stock',
        'implementingClassName' => 'App\\Domain\\Stock\\Models\\Stock',
        'currentClassName' => 'App\\Domain\\Stock\\Models\\Stock',
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