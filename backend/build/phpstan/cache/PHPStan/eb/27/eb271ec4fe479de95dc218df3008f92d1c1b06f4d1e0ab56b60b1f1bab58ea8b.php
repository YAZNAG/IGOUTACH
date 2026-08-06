<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Customers\Models\Customer.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Customers\Models\Customer
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-170e7582d9fa0f4abc7c494c6f81284f131a003940e360e10495558de4fc4501',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Customers\\Models\\Customer',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Customers/Models/Customer.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Customers\\Models',
    'name' => 'App\\Domain\\Customers\\Models\\Customer',
    'shortName' => 'Customer',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $is_company
 * @property string|null $contact_name
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property string|null $city
 * @property string|null $ice
 * @property int|null $price_type_id
 * @property int|null $seller_id
 * @property int|null $warehouse_id
 * @property float $credit_limit
 * @property float $balance
 * @property bool $is_blocked
 * @property string|null $notes
 * @property bool $is_active
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 37,
    'endLine' => 129,
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
        'declaringClassName' => 'App\\Domain\\Customers\\Models\\Customer',
        'implementingClassName' => 'App\\Domain\\Customers\\Models\\Customer',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'code\', \'name\', \'is_company\', \'contact_name\', \'phone\', \'email\', \'address\', \'city\', \'ice\', \'price_type_id\', \'seller_id\', \'warehouse_id\', \'credit_limit\', \'balance\', \'is_blocked\', \'notes\', \'is_active\', \'created_by\']',
          'attributes' => 
          array (
            'startLine' => 42,
            'endLine' => 61,
            'startTokenPos' => 88,
            'startFilePos' => 1169,
            'endTokenPos' => 144,
            'endFilePos' => 1532,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 61,
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
        'startLine' => 63,
        'endLine' => 72,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Domain\\Customers\\Models',
        'declaringClassName' => 'App\\Domain\\Customers\\Models\\Customer',
        'implementingClassName' => 'App\\Domain\\Customers\\Models\\Customer',
        'currentClassName' => 'App\\Domain\\Customers\\Models\\Customer',
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
        'startLine' => 77,
        'endLine' => 80,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Domain\\Customers\\Models',
        'declaringClassName' => 'App\\Domain\\Customers\\Models\\Customer',
        'implementingClassName' => 'App\\Domain\\Customers\\Models\\Customer',
        'currentClassName' => 'App\\Domain\\Customers\\Models\\Customer',
        'aliasName' => NULL,
      ),
      'availableCredit' => 
      array (
        'name' => 'availableCredit',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'float',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Crédit disponible = plafond − encours.
 */',
        'startLine' => 85,
        'endLine' => 88,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Customers\\Models',
        'declaringClassName' => 'App\\Domain\\Customers\\Models\\Customer',
        'implementingClassName' => 'App\\Domain\\Customers\\Models\\Customer',
        'currentClassName' => 'App\\Domain\\Customers\\Models\\Customer',
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
 * Type de prix appliqué par défaut (détail / demi-gros / gros).
 *
 * @return BelongsTo<PriceType, $this>
 */',
        'startLine' => 95,
        'endLine' => 98,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Customers\\Models',
        'declaringClassName' => 'App\\Domain\\Customers\\Models\\Customer',
        'implementingClassName' => 'App\\Domain\\Customers\\Models\\Customer',
        'currentClassName' => 'App\\Domain\\Customers\\Models\\Customer',
        'aliasName' => NULL,
      ),
      'seller' => 
      array (
        'name' => 'seller',
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
 * Vendeur référent.
 *
 * @return BelongsTo<User, $this>
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
        'namespace' => 'App\\Domain\\Customers\\Models',
        'declaringClassName' => 'App\\Domain\\Customers\\Models\\Customer',
        'implementingClassName' => 'App\\Domain\\Customers\\Models\\Customer',
        'currentClassName' => 'App\\Domain\\Customers\\Models\\Customer',
        'aliasName' => NULL,
      ),
      'warehouse' => 
      array (
        'name' => 'warehouse',
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
 * Lieu de rattachement.
 *
 * @return BelongsTo<Warehouse, $this>
 */',
        'startLine' => 115,
        'endLine' => 118,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Customers\\Models',
        'declaringClassName' => 'App\\Domain\\Customers\\Models\\Customer',
        'implementingClassName' => 'App\\Domain\\Customers\\Models\\Customer',
        'currentClassName' => 'App\\Domain\\Customers\\Models\\Customer',
        'aliasName' => NULL,
      ),
      'createdBy' => 
      array (
        'name' => 'createdBy',
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
 * Utilisateur ayant créé le client (portée de visibilité).
 *
 * @return BelongsTo<User, $this>
 */',
        'startLine' => 125,
        'endLine' => 128,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Customers\\Models',
        'declaringClassName' => 'App\\Domain\\Customers\\Models\\Customer',
        'implementingClassName' => 'App\\Domain\\Customers\\Models\\Customer',
        'currentClassName' => 'App\\Domain\\Customers\\Models\\Customer',
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