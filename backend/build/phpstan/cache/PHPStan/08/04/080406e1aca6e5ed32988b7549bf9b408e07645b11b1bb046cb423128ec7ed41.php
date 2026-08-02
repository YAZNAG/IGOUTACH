<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Catalog\Models\Product.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Catalog\Models\Product
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-2ad3086480700fd2717d8aa14720c0b9f53f61021aea3f2c10e39deb11c7b324',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Catalog\\Models\\Product',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Catalog/Models/Product.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Catalog\\Models',
    'name' => 'App\\Domain\\Catalog\\Models\\Product',
    'shortName' => 'Product',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @property int $id
 * @property string $sku
 * @property string|null $barcode
 * @property string $name
 * @property int $category_id
 * @property int|null $brand_id
 * @property int $unit_id
 * @property string $cost_price
 * @property string $sale_price
 * @property string $tax_rate
 * @property bool $is_serialized
 * @property int|null $min_stock
 * @property bool $is_active
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 32,
    'endLine' => 135,
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
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'sku\', \'barcode\', \'name\', \'description\', \'category_id\', \'brand_id\', \'unit_id\', \'cost_price\', \'sale_price\', \'tax_rate\', \'is_serialized\', \'min_stock\', \'is_active\']',
          'attributes' => 
          array (
            'startLine' => 37,
            'endLine' => 51,
            'startTokenPos' => 88,
            'startFilePos' => 1035,
            'endTokenPos' => 129,
            'endFilePos' => 1307,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 51,
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
        'startLine' => 53,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Domain\\Catalog\\Models',
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'currentClassName' => 'App\\Domain\\Catalog\\Models\\Product',
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
        'startLine' => 67,
        'endLine' => 70,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Domain\\Catalog\\Models',
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'currentClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'aliasName' => NULL,
      ),
      'category' => 
      array (
        'name' => 'category',
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
        'startLine' => 75,
        'endLine' => 78,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Catalog\\Models',
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'currentClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'aliasName' => NULL,
      ),
      'brand' => 
      array (
        'name' => 'brand',
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
 * @return BelongsTo<Brand, $this>
 */',
        'startLine' => 83,
        'endLine' => 86,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Catalog\\Models',
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'currentClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'aliasName' => NULL,
      ),
      'unit' => 
      array (
        'name' => 'unit',
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
 * @return BelongsTo<Unit, $this>
 */',
        'startLine' => 91,
        'endLine' => 94,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Catalog\\Models',
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'currentClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'aliasName' => NULL,
      ),
      'serials' => 
      array (
        'name' => 'serials',
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
 * @return HasMany<ProductSerial, $this>
 */',
        'startLine' => 99,
        'endLine' => 102,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Catalog\\Models',
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'currentClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'aliasName' => NULL,
      ),
      'attributes' => 
      array (
        'name' => 'attributes',
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
 * Fiche technique (attributs nom → valeur).
 *
 * @return HasMany<ProductAttribute, $this>
 */',
        'startLine' => 109,
        'endLine' => 112,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Catalog\\Models',
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'currentClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'aliasName' => NULL,
      ),
      'images' => 
      array (
        'name' => 'images',
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
 * Médias (image principale + secondaires).
 *
 * @return HasMany<ProductImage, $this>
 */',
        'startLine' => 119,
        'endLine' => 122,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Catalog\\Models',
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'currentClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'aliasName' => NULL,
      ),
      'suppliers' => 
      array (
        'name' => 'suppliers',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Fournisseurs référencés pour cet article.
 *
 * @return BelongsToMany<Supplier, $this>
 */',
        'startLine' => 129,
        'endLine' => 134,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Catalog\\Models',
        'declaringClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'implementingClassName' => 'App\\Domain\\Catalog\\Models\\Product',
        'currentClassName' => 'App\\Domain\\Catalog\\Models\\Product',
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