<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Customers\Models\CustomerLedgerEntry.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Customers\Models\CustomerLedgerEntry
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-6d5328e54ab772d60835d361025b187ebb9057a3ce297a5d3d22b9f8325ec45a',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Customers\\Models\\CustomerLedgerEntry',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Customers/Models/CustomerLedgerEntry.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Customers\\Models',
    'name' => 'App\\Domain\\Customers\\Models\\CustomerLedgerEntry',
    'shortName' => 'CustomerLedgerEntry',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Écriture du grand-livre client : maintient l\'encours par événements
 * (jamais de mise à jour directe du solde sans écriture).
 *
 * @property int $id
 * @property int $customer_id
 * @property string $type
 * @property string $amount
 * @property string $balance_after
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $note
 * @property int|null $user_id
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 25,
    'endLine' => 72,
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
      'TYPE_INVOICE' => 
      array (
        'declaringClassName' => 'App\\Domain\\Customers\\Models\\CustomerLedgerEntry',
        'implementingClassName' => 'App\\Domain\\Customers\\Models\\CustomerLedgerEntry',
        'name' => 'TYPE_INVOICE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'invoice\'',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 52,
            'startFilePos' => 694,
            'endTokenPos' => 52,
            'endFilePos' => 702,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 42,
      ),
      'TYPE_PAYMENT' => 
      array (
        'declaringClassName' => 'App\\Domain\\Customers\\Models\\CustomerLedgerEntry',
        'implementingClassName' => 'App\\Domain\\Customers\\Models\\CustomerLedgerEntry',
        'name' => 'TYPE_PAYMENT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'payment\'',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 29,
            'startTokenPos' => 63,
            'startFilePos' => 738,
            'endTokenPos' => 63,
            'endFilePos' => 746,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 42,
      ),
      'TYPE_RETURN' => 
      array (
        'declaringClassName' => 'App\\Domain\\Customers\\Models\\CustomerLedgerEntry',
        'implementingClassName' => 'App\\Domain\\Customers\\Models\\CustomerLedgerEntry',
        'name' => 'TYPE_RETURN',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'return\'',
          'attributes' => 
          array (
            'startLine' => 31,
            'endLine' => 31,
            'startTokenPos' => 74,
            'startFilePos' => 781,
            'endTokenPos' => 74,
            'endFilePos' => 788,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 40,
      ),
      'TYPE_ADJUSTMENT' => 
      array (
        'declaringClassName' => 'App\\Domain\\Customers\\Models\\CustomerLedgerEntry',
        'implementingClassName' => 'App\\Domain\\Customers\\Models\\CustomerLedgerEntry',
        'name' => 'TYPE_ADJUSTMENT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'adjustment\'',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 85,
            'startFilePos' => 827,
            'endTokenPos' => 85,
            'endFilePos' => 838,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 48,
      ),
    ),
    'immediateProperties' => 
    array (
      'fillable' => 
      array (
        'declaringClassName' => 'App\\Domain\\Customers\\Models\\CustomerLedgerEntry',
        'implementingClassName' => 'App\\Domain\\Customers\\Models\\CustomerLedgerEntry',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'customer_id\', \'type\', \'amount\', \'balance_after\', \'reference_type\', \'reference_id\', \'note\', \'user_id\']',
          'attributes' => 
          array (
            'startLine' => 35,
            'endLine' => 44,
            'startTokenPos' => 94,
            'startFilePos' => 868,
            'endTokenPos' => 120,
            'endFilePos' => 1041,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 35,
        'endLine' => 44,
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
        'startLine' => 49,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Domain\\Customers\\Models',
        'declaringClassName' => 'App\\Domain\\Customers\\Models\\CustomerLedgerEntry',
        'implementingClassName' => 'App\\Domain\\Customers\\Models\\CustomerLedgerEntry',
        'currentClassName' => 'App\\Domain\\Customers\\Models\\CustomerLedgerEntry',
        'aliasName' => NULL,
      ),
      'customer' => 
      array (
        'name' => 'customer',
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
 * @return BelongsTo<Customer, $this>
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
        'namespace' => 'App\\Domain\\Customers\\Models',
        'declaringClassName' => 'App\\Domain\\Customers\\Models\\CustomerLedgerEntry',
        'implementingClassName' => 'App\\Domain\\Customers\\Models\\CustomerLedgerEntry',
        'currentClassName' => 'App\\Domain\\Customers\\Models\\CustomerLedgerEntry',
        'aliasName' => NULL,
      ),
      'user' => 
      array (
        'name' => 'user',
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
        'startLine' => 68,
        'endLine' => 71,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Customers\\Models',
        'declaringClassName' => 'App\\Domain\\Customers\\Models\\CustomerLedgerEntry',
        'implementingClassName' => 'App\\Domain\\Customers\\Models\\CustomerLedgerEntry',
        'currentClassName' => 'App\\Domain\\Customers\\Models\\CustomerLedgerEntry',
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