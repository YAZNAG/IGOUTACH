<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Catalog\Models\ProductImage.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Catalog\Models\ProductImage
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-a363c7461587eb2c680e9c99aebf2f003975a78c8f4250ba65cdff981e30db09',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Catalog\\Models\\ProductImage',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Catalog/Models/ProductImage.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Catalog\\Models',
    'name' => 'App\\Domain\\Catalog\\Models\\ProductImage',
    'shortName' => 'ProductImage',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Image d\'un article (une principale au plus, secondaires ordonnées).
 *
 * @property int $id
 * @property int $product_id
 * @property string $path
 * @property bool $is_main
 * @property int $position
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 38,
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
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\ProductImage',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\ProductImage',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'product_id\', \'path\', \'is_main\', \'position\']',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 45,
            'startFilePos' => 446,
            'endTokenPos' => 56,
            'endFilePos' => 490,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 72,
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
        'startLine' => 26,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Catalog\\Models',
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\ProductImage',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\ProductImage',
        'currentClassName' => 'App\\Domain\\Catalog\\Models\\ProductImage',
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
        'docComment' => '/**
 * @return array<string, string>
 */',
        'startLine' => 34,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Domain\\Catalog\\Models',
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\ProductImage',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\ProductImage',
        'currentClassName' => 'App\\Domain\\Catalog\\Models\\ProductImage',
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