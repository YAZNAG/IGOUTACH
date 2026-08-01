<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Pricing\Models\ProductPrice.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Pricing\Models\ProductPrice
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-b97c7a67ae418b0d451187a013faeb19cf0019a1a661053b18f1a3c9ffd3dda4',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Pricing\\Models\\ProductPrice',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Pricing/Models/ProductPrice.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Pricing\\Models',
    'name' => 'App\\Domain\\Pricing\\Models\\ProductPrice',
    'shortName' => 'ProductPrice',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Prix d\'un article pour un niveau, historisé (append-only).
 *
 * @property int $id
 * @property int $product_id
 * @property int $price_type_id
 * @property string $amount
 * @property int|null $min_quantity
 * @property string $min_margin_percent
 * @property Carbon $valid_from
 * @property Carbon|null $valid_to
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 24,
    'endLine' => 63,
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
        'declaringClassName' => 'App\\Domain\\Pricing\\Models\\ProductPrice',
        'implementingClassName' => 'App\\Domain\\Pricing\\Models\\ProductPrice',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'product_id\', \'price_type_id\', \'amount\', \'min_quantity\', \'min_margin_percent\', \'valid_from\', \'valid_to\', \'created_by\']',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 35,
            'startTokenPos' => 53,
            'startFilePos' => 624,
            'endTokenPos' => 79,
            'endFilePos' => 813,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 26,
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
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Domain\\Pricing\\Models',
        'declaringClassName' => 'App\\Domain\\Pricing\\Models\\ProductPrice',
        'implementingClassName' => 'App\\Domain\\Pricing\\Models\\ProductPrice',
        'currentClassName' => 'App\\Domain\\Pricing\\Models\\ProductPrice',
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
        'namespace' => 'App\\Domain\\Pricing\\Models',
        'declaringClassName' => 'App\\Domain\\Pricing\\Models\\ProductPrice',
        'implementingClassName' => 'App\\Domain\\Pricing\\Models\\ProductPrice',
        'currentClassName' => 'App\\Domain\\Pricing\\Models\\ProductPrice',
        'aliasName' => NULL,
      ),
      'priceType' => 
      array (
        'name' => 'priceType',
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
 * @return BelongsTo<PriceType, $this>
 */',
        'startLine' => 59,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Pricing\\Models',
        'declaringClassName' => 'App\\Domain\\Pricing\\Models\\ProductPrice',
        'implementingClassName' => 'App\\Domain\\Pricing\\Models\\ProductPrice',
        'currentClassName' => 'App\\Domain\\Pricing\\Models\\ProductPrice',
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