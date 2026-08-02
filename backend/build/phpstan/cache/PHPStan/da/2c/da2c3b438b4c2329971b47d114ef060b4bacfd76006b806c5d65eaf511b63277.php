<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Purchasing\Models\Supplier.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Purchasing\Models\Supplier
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-aa7993b205c536f4a095971468a3777c9c1a98a5d91cc69aace53e6819c9774e',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Purchasing\\Models\\Supplier',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Purchasing/Models/Supplier.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Purchasing\\Models',
    'name' => 'App\\Domain\\Purchasing\\Models\\Supplier',
    'shortName' => 'Supplier',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $contact_name
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property string|null $city
 * @property string|null $ice
 * @property string|null $rc
 * @property int $payment_terms_days
 * @property string|null $notes
 * @property bool $is_active
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 31,
    'endLine' => 86,
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
        'declaringClassName' => 'App\\Domain\\Purchasing\\Models\\Supplier',
        'implementingClassName' => 'App\\Domain\\Purchasing\\Models\\Supplier',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'code\', \'name\', \'contact_name\', \'phone\', \'email\', \'address\', \'city\', \'ice\', \'rc\', \'payment_terms_days\', \'notes\', \'is_active\']',
          'attributes' => 
          array (
            'startLine' => 56,
            'endLine' => 69,
            'startTokenPos' => 163,
            'startFilePos' => 1531,
            'endTokenPos' => 201,
            'endFilePos' => 1759,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 56,
        'endLine' => 69,
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
      'contacts' => 
      array (
        'name' => 'contacts',
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
 * @return HasMany<SupplierContact, $this>
 */',
        'startLine' => 39,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Purchasing\\Models',
        'declaringClassName' => 'App\\Domain\\Purchasing\\Models\\Supplier',
        'implementingClassName' => 'App\\Domain\\Purchasing\\Models\\Supplier',
        'currentClassName' => 'App\\Domain\\Purchasing\\Models\\Supplier',
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
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Articles référencés chez ce fournisseur.
 *
 * @return BelongsToMany<Product, $this>
 */',
        'startLine' => 49,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Purchasing\\Models',
        'declaringClassName' => 'App\\Domain\\Purchasing\\Models\\Supplier',
        'implementingClassName' => 'App\\Domain\\Purchasing\\Models\\Supplier',
        'currentClassName' => 'App\\Domain\\Purchasing\\Models\\Supplier',
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
        'startLine' => 71,
        'endLine' => 77,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Domain\\Purchasing\\Models',
        'declaringClassName' => 'App\\Domain\\Purchasing\\Models\\Supplier',
        'implementingClassName' => 'App\\Domain\\Purchasing\\Models\\Supplier',
        'currentClassName' => 'App\\Domain\\Purchasing\\Models\\Supplier',
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
        'startLine' => 82,
        'endLine' => 85,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Domain\\Purchasing\\Models',
        'declaringClassName' => 'App\\Domain\\Purchasing\\Models\\Supplier',
        'implementingClassName' => 'App\\Domain\\Purchasing\\Models\\Supplier',
        'currentClassName' => 'App\\Domain\\Purchasing\\Models\\Supplier',
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