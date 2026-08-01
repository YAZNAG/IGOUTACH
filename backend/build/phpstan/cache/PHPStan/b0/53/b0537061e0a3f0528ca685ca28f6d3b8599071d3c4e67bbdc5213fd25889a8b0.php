<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\app\Domain\Catalog\Models\Category.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Catalog\Models\Category
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-ae25ab1aa79e262e5ff0656e4d2df122361d0f4945f6b5a5e0b6ad9ad820979b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Catalog\\Models\\Category',
        'filename' => 'C:/OPTIZAWORKS/igoutech/app/Domain/Catalog/Models/Category.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Catalog\\Models',
    'name' => 'App\\Domain\\Catalog\\Models\\Category',
    'shortName' => 'Category',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @property int $id
 * @property string $name
 * @property int|null $parent_id
 * @property bool $requires_serial
 * @property bool $is_active
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
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
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'fillable' => 
      array (
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\Category',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\Category',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'name\', \'parent_id\', \'requires_serial\', \'is_active\']',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 31,
            'startTokenPos' => 70,
            'startFilePos' => 639,
            'endTokenPos' => 84,
            'endFilePos' => 730,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 31,
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
        'startLine' => 33,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Domain\\Catalog\\Models',
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\Category',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\Category',
        'currentClassName' => 'App\\Domain\\Catalog\\Models\\Category',
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
        'startLine' => 44,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Domain\\Catalog\\Models',
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\Category',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\Category',
        'currentClassName' => 'App\\Domain\\Catalog\\Models\\Category',
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
 * @return BelongsTo<Category, $this>
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
        'namespace' => 'App\\Domain\\Catalog\\Models',
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\Category',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\Category',
        'currentClassName' => 'App\\Domain\\Catalog\\Models\\Category',
        'aliasName' => NULL,
      ),
      'products' => 
      array (
        'name' => 'products',
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
 * @return HasMany<Product, $this>
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
        'namespace' => 'App\\Domain\\Catalog\\Models',
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\Category',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\Category',
        'currentClassName' => 'App\\Domain\\Catalog\\Models\\Category',
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