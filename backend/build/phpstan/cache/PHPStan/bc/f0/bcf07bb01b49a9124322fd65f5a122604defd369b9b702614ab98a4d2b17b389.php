<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Sales\Models\Sale.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Sales\Models\Sale
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-78a7a29b04d08001c0b948c2c5f5626c4eedcdba1d5a25e79514facffa1138e5',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Sales\\Models\\Sale',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Sales/Models/Sale.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Sales\\Models',
    'name' => 'App\\Domain\\Sales\\Models\\Sale',
    'shortName' => 'Sale',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Document de vente : devis (sans effet stock) ou facture
 * (sortie de stock + créance client à la confirmation).
 *
 * @property int $id
 * @property string $reference
 * @property string $type
 * @property string $status
 * @property int $customer_id
 * @property int $warehouse_id
 * @property int|null $user_id
 * @property string $subtotal
 * @property string $discount_percent
 * @property string $total
 * @property string $paid_amount
 * @property string $payment_status
 * @property Carbon|null $confirmed_at
 * @property string|null $note
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 34,
    'endLine' => 107,
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
      'TYPE_QUOTE' => 
      array (
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\Sale',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\Sale',
        'name' => 'TYPE_QUOTE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'quote\'',
          'attributes' => 
          array (
            'startLine' => 36,
            'endLine' => 36,
            'startTokenPos' => 72,
            'startFilePos' => 978,
            'endTokenPos' => 72,
            'endFilePos' => 984,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 38,
      ),
      'TYPE_INVOICE' => 
      array (
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\Sale',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\Sale',
        'name' => 'TYPE_INVOICE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'invoice\'',
          'attributes' => 
          array (
            'startLine' => 38,
            'endLine' => 38,
            'startTokenPos' => 83,
            'startFilePos' => 1020,
            'endTokenPos' => 83,
            'endFilePos' => 1028,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 42,
      ),
      'STATUS_DRAFT' => 
      array (
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\Sale',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\Sale',
        'name' => 'STATUS_DRAFT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'draft\'',
          'attributes' => 
          array (
            'startLine' => 40,
            'endLine' => 40,
            'startTokenPos' => 94,
            'startFilePos' => 1064,
            'endTokenPos' => 94,
            'endFilePos' => 1070,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 40,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 40,
      ),
      'STATUS_CONFIRMED' => 
      array (
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\Sale',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\Sale',
        'name' => 'STATUS_CONFIRMED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'confirmed\'',
          'attributes' => 
          array (
            'startLine' => 42,
            'endLine' => 42,
            'startTokenPos' => 105,
            'startFilePos' => 1110,
            'endTokenPos' => 105,
            'endFilePos' => 1120,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 48,
      ),
      'STATUS_CANCELLED' => 
      array (
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\Sale',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\Sale',
        'name' => 'STATUS_CANCELLED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'cancelled\'',
          'attributes' => 
          array (
            'startLine' => 44,
            'endLine' => 44,
            'startTokenPos' => 116,
            'startFilePos' => 1160,
            'endTokenPos' => 116,
            'endFilePos' => 1170,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 44,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 48,
      ),
    ),
    'immediateProperties' => 
    array (
      'fillable' => 
      array (
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\Sale',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\Sale',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'reference\', \'type\', \'status\', \'customer_id\', \'warehouse_id\', \'user_id\', \'subtotal\', \'discount_percent\', \'total\', \'paid_amount\', \'payment_status\', \'confirmed_at\', \'note\']',
          'attributes' => 
          array (
            'startLine' => 46,
            'endLine' => 60,
            'startTokenPos' => 125,
            'startFilePos' => 1200,
            'endTokenPos' => 166,
            'endFilePos' => 1481,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 46,
        'endLine' => 60,
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
        'startLine' => 65,
        'endLine' => 74,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Domain\\Sales\\Models',
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\Sale',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\Sale',
        'currentClassName' => 'App\\Domain\\Sales\\Models\\Sale',
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
        'startLine' => 79,
        'endLine' => 82,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Sales\\Models',
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\Sale',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\Sale',
        'currentClassName' => 'App\\Domain\\Sales\\Models\\Sale',
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
        'startLine' => 87,
        'endLine' => 90,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Sales\\Models',
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\Sale',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\Sale',
        'currentClassName' => 'App\\Domain\\Sales\\Models\\Sale',
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
        'startLine' => 95,
        'endLine' => 98,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Sales\\Models',
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\Sale',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\Sale',
        'currentClassName' => 'App\\Domain\\Sales\\Models\\Sale',
        'aliasName' => NULL,
      ),
      'lines' => 
      array (
        'name' => 'lines',
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
 * @return HasMany<SaleLine, $this>
 */',
        'startLine' => 103,
        'endLine' => 106,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Sales\\Models',
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\Sale',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\Sale',
        'currentClassName' => 'App\\Domain\\Sales\\Models\\Sale',
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