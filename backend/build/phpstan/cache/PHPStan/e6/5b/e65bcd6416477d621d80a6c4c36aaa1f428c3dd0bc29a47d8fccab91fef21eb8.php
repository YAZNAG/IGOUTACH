<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Catalog\Models\ProductAttribute.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Catalog\Models\ProductAttribute
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-9b39690d2286a1bb6080dc1fecc275419ecb3bbdd228c10bad1595982fd14a24',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Catalog\\Models\\ProductAttribute',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Catalog/Models/ProductAttribute.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Catalog\\Models',
    'name' => 'App\\Domain\\Catalog\\Models\\ProductAttribute',
    'shortName' => 'ProductAttribute',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Ligne de fiche technique d\'un article (nom → valeur).
 *
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property string $value
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
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\ProductAttribute',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\ProductAttribute',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'product_id\', \'name\', \'value\', \'position\']',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 45,
            'startFilePos' => 437,
            'endTokenPos' => 56,
            'endFilePos' => 479,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 70,
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
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\ProductAttribute',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\ProductAttribute',
        'currentClassName' => 'App\\Domain\\Catalog\\Models\\ProductAttribute',
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
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\ProductAttribute',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\ProductAttribute',
        'currentClassName' => 'App\\Domain\\Catalog\\Models\\ProductAttribute',
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