<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Warehouses\Models\WarehouseType.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Warehouses\Models\WarehouseType
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-d38407173b9a3e7f3dc8da2d069078aa3fde0dc9f057b11e435efb443e5d9586',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Warehouses\\Models\\WarehouseType',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Warehouses/Models/WarehouseType.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Warehouses\\Models',
    'name' => 'App\\Domain\\Warehouses\\Models\\WarehouseType',
    'shortName' => 'WarehouseType',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $allows_sales
 * @property bool $allows_purchase_receipt
 * @property bool $requires_transfer_approval
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 58,
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
        'declaringClassName' => 'App\\Domain\\Warehouses\\Models\\WarehouseType',
        'implementingClassName' => 'App\\Domain\\Warehouses\\Models\\WarehouseType',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'code\', \'name\', \'allows_sales\', \'allows_purchase_receipt\', \'requires_transfer_approval\']',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 32,
            'startTokenPos' => 65,
            'startFilePos' => 653,
            'endTokenPos' => 82,
            'endFilePos' => 788,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 32,
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
        'startLine' => 37,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Domain\\Warehouses\\Models',
        'declaringClassName' => 'App\\Domain\\Warehouses\\Models\\WarehouseType',
        'implementingClassName' => 'App\\Domain\\Warehouses\\Models\\WarehouseType',
        'currentClassName' => 'App\\Domain\\Warehouses\\Models\\WarehouseType',
        'aliasName' => NULL,
      ),
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
        'startLine' => 42,
        'endLine' => 49,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Domain\\Warehouses\\Models',
        'declaringClassName' => 'App\\Domain\\Warehouses\\Models\\WarehouseType',
        'implementingClassName' => 'App\\Domain\\Warehouses\\Models\\WarehouseType',
        'currentClassName' => 'App\\Domain\\Warehouses\\Models\\WarehouseType',
        'aliasName' => NULL,
      ),
      'warehouses' => 
      array (
        'name' => 'warehouses',
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
 * @return HasMany<Warehouse, $this>
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
        'namespace' => 'App\\Domain\\Warehouses\\Models',
        'declaringClassName' => 'App\\Domain\\Warehouses\\Models\\WarehouseType',
        'implementingClassName' => 'App\\Domain\\Warehouses\\Models\\WarehouseType',
        'currentClassName' => 'App\\Domain\\Warehouses\\Models\\WarehouseType',
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