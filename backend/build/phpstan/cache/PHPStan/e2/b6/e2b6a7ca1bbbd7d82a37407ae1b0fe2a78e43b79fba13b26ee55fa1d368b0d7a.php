<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Sales\Models\Payment.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Sales\Models\Payment
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-e4657240c23ed4d127c4e207f4ddcd1ca63bbbbc70579dee99d4f100e4e6200f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Sales\\Models\\Payment',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Sales/Models/Payment.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Sales\\Models',
    'name' => 'App\\Domain\\Sales\\Models\\Payment',
    'shortName' => 'Payment',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Encaissement client (espèces, chèque, virement, carte).
 *
 * @property int $id
 * @property string $reference
 * @property int $customer_id
 * @property int|null $sale_id
 * @property int|null $payment_method_id
 * @property int|null $cash_session_id
 * @property string $amount
 * @property string|null $cheque_status
 * @property string|null $cheque_reference
 * @property Carbon $received_at
 * @property int|null $user_id
 * @property string|null $note
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 29,
    'endLine' => 95,
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
      'CHEQUE_RECEIVED' => 
      array (
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\Payment',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\Payment',
        'name' => 'CHEQUE_RECEIVED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'received\'',
          'attributes' => 
          array (
            'startLine' => 31,
            'endLine' => 31,
            'startTokenPos' => 62,
            'startFilePos' => 825,
            'endTokenPos' => 62,
            'endFilePos' => 834,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 46,
      ),
      'CHEQUE_DEPOSITED' => 
      array (
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\Payment',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\Payment',
        'name' => 'CHEQUE_DEPOSITED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'deposited\'',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 73,
            'startFilePos' => 874,
            'endTokenPos' => 73,
            'endFilePos' => 884,
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
      'CHEQUE_CLEARED' => 
      array (
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\Payment',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\Payment',
        'name' => 'CHEQUE_CLEARED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'cleared\'',
          'attributes' => 
          array (
            'startLine' => 35,
            'endLine' => 35,
            'startTokenPos' => 84,
            'startFilePos' => 922,
            'endTokenPos' => 84,
            'endFilePos' => 930,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 44,
      ),
      'CHEQUE_BOUNCED' => 
      array (
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\Payment',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\Payment',
        'name' => 'CHEQUE_BOUNCED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'bounced\'',
          'attributes' => 
          array (
            'startLine' => 37,
            'endLine' => 37,
            'startTokenPos' => 95,
            'startFilePos' => 968,
            'endTokenPos' => 95,
            'endFilePos' => 976,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 44,
      ),
    ),
    'immediateProperties' => 
    array (
      'fillable' => 
      array (
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\Payment',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\Payment',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'reference\', \'customer_id\', \'sale_id\', \'payment_method_id\', \'cash_session_id\', \'amount\', \'cheque_status\', \'cheque_reference\', \'received_at\', \'user_id\', \'note\']',
          'attributes' => 
          array (
            'startLine' => 39,
            'endLine' => 51,
            'startTokenPos' => 104,
            'startFilePos' => 1006,
            'endTokenPos' => 139,
            'endFilePos' => 1260,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 39,
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
        'docComment' => '/**
 * @return array<string, string>
 */',
        'startLine' => 56,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Domain\\Sales\\Models',
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\Payment',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\Payment',
        'currentClassName' => 'App\\Domain\\Sales\\Models\\Payment',
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
        'startLine' => 67,
        'endLine' => 70,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Sales\\Models',
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\Payment',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\Payment',
        'currentClassName' => 'App\\Domain\\Sales\\Models\\Payment',
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
        'startLine' => 75,
        'endLine' => 78,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Sales\\Models',
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\Payment',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\Payment',
        'currentClassName' => 'App\\Domain\\Sales\\Models\\Payment',
        'aliasName' => NULL,
      ),
      'method' => 
      array (
        'name' => 'method',
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
 * @return BelongsTo<PaymentMethod, $this>
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
        'namespace' => 'App\\Domain\\Sales\\Models',
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\Payment',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\Payment',
        'currentClassName' => 'App\\Domain\\Sales\\Models\\Payment',
        'aliasName' => NULL,
      ),
      'cashSession' => 
      array (
        'name' => 'cashSession',
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
 * @return BelongsTo<CashSession, $this>
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
        'namespace' => 'App\\Domain\\Sales\\Models',
        'declaringClassName' => 'App\\Domain\\Sales\\Models\\Payment',
        'implementingClassName' => 'App\\Domain\\Sales\\Models\\Payment',
        'currentClassName' => 'App\\Domain\\Sales\\Models\\Payment',
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