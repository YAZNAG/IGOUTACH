<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Sales\Models\CashSession.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Sales\Models\CashSession
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-b08e5d6e67b7de406d8b8f27a6a0622c8447885b2e7ccd2ffdd1ab25851cbfa0',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Sales\\Models\\CashSession',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Sales/Models/CashSession.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Sales\\Models',
    'name' => 'App\\Domain\\Sales\\Models\\CashSession',
    'shortName' => 'CashSession',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Session de caisse : ouverture avec fonds, clôture avec écart.
 *
 * @property int $id
 * @property int $warehouse_id
 * @property int $opened_by
 * @property Carbon $opened_at
 * @property string $opening_amount
 * @property Carbon|null $closed_at
 * @property string|null $closing_amount
 * @property string|null $expected_amount
 * @property string|null $difference
 * @property string $status
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 28,
    'endLine' => 84,
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
      'STATUS_OPEN' => 
      array (
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\CashSession',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\CashSession',
        'name' => 'STATUS_OPEN',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'open\'',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 30,
            'startTokenPos' => 67,
            'startFilePos' => 792,
            'endTokenPos' => 67,
            'endFilePos' => 797,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 38,
      ),
      'STATUS_CLOSED' => 
      array (
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\CashSession',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\CashSession',
        'name' => 'STATUS_CLOSED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'closed\'',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 32,
            'startTokenPos' => 78,
            'startFilePos' => 834,
            'endTokenPos' => 78,
            'endFilePos' => 841,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 42,
      ),
    ),
    'immediateProperties' => 
    array (
      'fillable' => 
      array (
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\CashSession',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\CashSession',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'warehouse_id\', \'opened_by\', \'opened_at\', \'opening_amount\', \'closed_at\', \'closing_amount\', \'expected_amount\', \'difference\', \'status\']',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 44,
            'startTokenPos' => 87,
            'startFilePos' => 871,
            'endTokenPos' => 116,
            'endFilePos' => 1083,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
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
        'endLine' => 59,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Domain\\Sales\\Models',
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\CashSession',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\CashSession',
        'currentClassName' => 'App\\Domain\\Sales\\Models\\CashSession',
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
 * @return BelongsTo<Warehouse, $this>
 */',
        'startLine' => 64,
        'endLine' => 67,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Sales\\Models',
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\CashSession',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\CashSession',
        'currentClassName' => 'App\\Domain\\Sales\\Models\\CashSession',
        'aliasName' => NULL,
      ),
      'opener' => 
      array (
        'name' => 'opener',
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
        'startLine' => 72,
        'endLine' => 75,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Sales\\Models',
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\CashSession',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\CashSession',
        'currentClassName' => 'App\\Domain\\Sales\\Models\\CashSession',
        'aliasName' => NULL,
      ),
      'payments' => 
      array (
        'name' => 'payments',
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
 * @return HasMany<Payment, $this>
 */',
        'startLine' => 80,
        'endLine' => 83,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Sales\\Models',
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\CashSession',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\CashSession',
        'currentClassName' => 'App\\Domain\\Sales\\Models\\CashSession',
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