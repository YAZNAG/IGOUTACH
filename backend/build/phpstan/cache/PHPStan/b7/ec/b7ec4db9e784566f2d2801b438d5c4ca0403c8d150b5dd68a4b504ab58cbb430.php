<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Sales\Models\SaleLine.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Sales\Models\SaleLine
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-32f38b520c623e2d7813ae9467e11695912e947dd97728bd892f8852849678ba',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Sales\\Models\\SaleLine',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Sales/Models/SaleLine.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Sales\\Models',
    'name' => 'App\\Domain\\Sales\\Models\\SaleLine',
    'shortName' => 'SaleLine',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Ligne de vente (prix résolu au moment de la saisie).
 *
 * @property int $id
 * @property int $sale_id
 * @property int $product_id
 * @property int $quantity
 * @property string $unit_price
 * @property string|null $price_type_code
 * @property string $line_total
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
    'endLine' => 60,
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
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\SaleLine',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\SaleLine',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'sale_id\', \'product_id\', \'quantity\', \'unit_price\', \'price_type_code\', \'line_total\']',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 31,
            'startTokenPos' => 50,
            'startFilePos' => 543,
            'endTokenPos' => 70,
            'endFilePos' => 681,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
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
        'docComment' => '/**
 * @return array<string, string>
 */',
        'startLine' => 36,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Domain\\Sales\\Models',
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\SaleLine',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\SaleLine',
        'currentClassName' => 'App\\Domain\\Sales\\Models\\SaleLine',
        'aliasName' => NULL,
      ),
      'sale' => 
      array (
        'name' => 'sale',
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
 * @return BelongsTo<Sale, $this>
 */',
        'startLine' => 48,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Sales\\Models',
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\SaleLine',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\SaleLine',
        'currentClassName' => 'App\\Domain\\Sales\\Models\\SaleLine',
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
        'startLine' => 56,
        'endLine' => 59,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Sales\\Models',
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\SaleLine',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\SaleLine',
        'currentClassName' => 'App\\Domain\\Sales\\Models\\SaleLine',
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