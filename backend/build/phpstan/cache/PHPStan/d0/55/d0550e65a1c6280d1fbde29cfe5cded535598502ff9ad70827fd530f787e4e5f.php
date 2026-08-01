<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\app\Domain\Warehouses\Models\Warehouse.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Warehouses\Models\Warehouse
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-1b8d2f5469532b997e3b6f4af613b3d14fbf7c75b1278ca1266d5aa5b0a5bd36',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
        'filename' => 'C:/OPTIZAWORKS/igoutech/app/Domain/Warehouses/Models/Warehouse.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Warehouses\\Models',
    'name' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
    'shortName' => 'Warehouse',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $warehouse_type_id
 * @property int|null $manager_id
 * @property int|null $parent_id
 * @property bool $is_active
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 25,
    'endLine' => 90,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
      1 => 'Illuminate\\Database\\Eloquent\\SoftDeletes',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'fillable' => 
      array (
        'declaringClassName' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
        'implementingClassName' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'code\', \'name\', \'warehouse_type_id\', \'manager_id\', \'parent_id\', \'address\', \'city\', \'phone\', \'is_active\']',
          'attributes' => 
          array (
            'startLine' => 38,
            'endLine' => 48,
            'startTokenPos' => 111,
            'startFilePos' => 936,
            'endTokenPos' => 140,
            'endFilePos' => 1119,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 38,
        'endLine' => 48,
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
        'startLine' => 33,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Domain\\Warehouses\\Models',
        'declaringClassName' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
        'implementingClassName' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
        'currentClassName' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
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
        'startLine' => 50,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Domain\\Warehouses\\Models',
        'declaringClassName' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
        'implementingClassName' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
        'currentClassName' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
        'aliasName' => NULL,
      ),
      'type' => 
      array (
        'name' => 'type',
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
 * @return BelongsTo<WarehouseType, $this>
 */',
        'startLine' => 60,
        'endLine' => 63,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Warehouses\\Models',
        'declaringClassName' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
        'implementingClassName' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
        'currentClassName' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
        'aliasName' => NULL,
      ),
      'manager' => 
      array (
        'name' => 'manager',
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
 * @return BelongsTo<User, $this>
 */',
        'startLine' => 68,
        'endLine' => 71,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Warehouses\\Models',
        'declaringClassName' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
        'implementingClassName' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
        'currentClassName' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
        'aliasName' => NULL,
      ),
      'parent' => 
      array (
        'name' => 'parent',
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
 * Dépôt de rattachement (pour un véhicule).
 *
 * @return BelongsTo<Warehouse, $this>
 */',
        'startLine' => 78,
        'endLine' => 81,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Warehouses\\Models',
        'declaringClassName' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
        'implementingClassName' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
        'currentClassName' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
        'aliasName' => NULL,
      ),
      'children' => 
      array (
        'name' => 'children',
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
        'startLine' => 86,
        'endLine' => 89,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Warehouses\\Models',
        'declaringClassName' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
        'implementingClassName' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
        'currentClassName' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
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