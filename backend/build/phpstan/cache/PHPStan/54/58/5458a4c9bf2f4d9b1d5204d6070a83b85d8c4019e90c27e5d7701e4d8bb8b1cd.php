<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Catalog\Models\Brand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Catalog\Models\Brand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-ea9bd7110dc756473fe545f1228209af3074a9871cf18343c00ed0e70d0d0a46',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Catalog\\Models\\Brand',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Catalog/Models/Brand.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Catalog\\Models',
    'name' => 'App\\Domain\\Catalog\\Models\\Brand',
    'shortName' => 'Brand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @property int $id
 * @property string|null $code
 * @property string $name
 * @property string|null $logo_path
 * @property string|null $website
 * @property int $position
 * @property bool $is_active
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 23,
    'endLine' => 59,
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
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\Brand',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\Brand',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'code\', \'name\', \'logo_path\', \'website\', \'position\', \'is_active\']',
          'attributes' => 
          array (
            'startLine' => 28,
            'endLine' => 35,
            'startTokenPos' => 73,
            'startFilePos' => 695,
            'endTokenPos' => 93,
            'endFilePos' => 814,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 35,
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
        'startLine' => 37,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Domain\\Catalog\\Models',
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\Brand',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\Brand',
        'currentClassName' => 'App\\Domain\\Catalog\\Models\\Brand',
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
        'startLine' => 47,
        'endLine' => 50,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Domain\\Catalog\\Models',
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\Brand',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\Brand',
        'currentClassName' => 'App\\Domain\\Catalog\\Models\\Brand',
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
        'startLine' => 55,
        'endLine' => 58,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Catalog\\Models',
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\Brand',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\Brand',
        'currentClassName' => 'App\\Domain\\Catalog\\Models\\Brand',
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