<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Warehouses\Models\Warehouse.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Warehouses\Models\Warehouse
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-74d0d7903ade9d73fd46a0e960ee16af8c332c831d637b2776a4ba7e7d4cd860',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Warehouses/Models/Warehouse.php',
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
    'startLine' => 26,
    'endLine' => 114,
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
            'startLine' => 39,
            'endLine' => 49,
            'startTokenPos' => 116,
            'startFilePos' => 971,
            'endTokenPos' => 145,
            'endFilePos' => 1154,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 39,
        'endLine' => 49,
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
        'startLine' => 34,
        'endLine' => 37,
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
        'startLine' => 51,
        'endLine' => 56,
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
        'startLine' => 61,
        'endLine' => 64,
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
        'startLine' => 69,
        'endLine' => 72,
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
        'startLine' => 79,
        'endLine' => 82,
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
        'startLine' => 87,
        'endLine' => 90,
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
      'users' => 
      array (
        'name' => 'users',
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
 * Utilisateurs rattachés à ce lieu.
 *
 * @return HasMany<User, $this>
 */',
        'startLine' => 97,
        'endLine' => 100,
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
      'stocks' => 
      array (
        'name' => 'stocks',
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
 * @return HasMany<Stock, $this>
 */',
        'startLine' => 105,
        'endLine' => 108,
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
      'isVehicle' => 
      array (
        'name' => 'isVehicle',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 110,
        'endLine' => 113,
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