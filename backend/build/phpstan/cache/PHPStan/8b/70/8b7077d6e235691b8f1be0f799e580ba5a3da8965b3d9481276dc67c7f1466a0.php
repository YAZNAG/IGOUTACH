<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Stock\Models\Transfer.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Stock\Models\Transfer
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-b7f89bcea2f5f2fd236ad6448dd9203a1832ed135a6b26bc7d7c8348a5db8d0c',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Stock\\Models\\Transfer',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Stock/Models/Transfer.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Stock\\Models',
    'name' => 'App\\Domain\\Stock\\Models\\Transfer',
    'shortName' => 'Transfer',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @property int $id
 * @property string $reference
 * @property int $from_warehouse_id
 * @property int $to_warehouse_id
 * @property int $transfer_status_id
 * @property Carbon|null $sent_at
 * @property Carbon|null $received_at
 * @property string|null $note
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 23,
    'endLine' => 76,
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
        'declaringClassName' => 'App\\Domain\\Stock\\Models\\Transfer',
        'implementingClassName' => 'App\\Domain\\Stock\\Models\\Transfer',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'reference\', \'from_warehouse_id\', \'to_warehouse_id\', \'transfer_status_id\', \'created_by\', \'received_by\', \'sent_at\', \'received_at\', \'note\']',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 35,
            'startTokenPos' => 58,
            'startFilePos' => 618,
            'endTokenPos' => 87,
            'endFilePos' => 834,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 25,
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
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Domain\\Stock\\Models',
        'declaringClassName' => 'App\\Domain\\Stock\\Models\\Transfer',
        'implementingClassName' => 'App\\Domain\\Stock\\Models\\Transfer',
        'currentClassName' => 'App\\Domain\\Stock\\Models\\Transfer',
        'aliasName' => NULL,
      ),
      'fromWarehouse' => 
      array (
        'name' => 'fromWarehouse',
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
        'startLine' => 48,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Stock\\Models',
        'declaringClassName' => 'App\\Domain\\Stock\\Models\\Transfer',
        'implementingClassName' => 'App\\Domain\\Stock\\Models\\Transfer',
        'currentClassName' => 'App\\Domain\\Stock\\Models\\Transfer',
        'aliasName' => NULL,
      ),
      'toWarehouse' => 
      array (
        'name' => 'toWarehouse',
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
        'startLine' => 56,
        'endLine' => 59,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Stock\\Models',
        'declaringClassName' => 'App\\Domain\\Stock\\Models\\Transfer',
        'implementingClassName' => 'App\\Domain\\Stock\\Models\\Transfer',
        'currentClassName' => 'App\\Domain\\Stock\\Models\\Transfer',
        'aliasName' => NULL,
      ),
      'status' => 
      array (
        'name' => 'status',
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
 * @return BelongsTo<TransferStatus, $this>
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
        'namespace' => 'App\\Domain\\Stock\\Models',
        'declaringClassName' => 'App\\Domain\\Stock\\Models\\Transfer',
        'implementingClassName' => 'App\\Domain\\Stock\\Models\\Transfer',
        'currentClassName' => 'App\\Domain\\Stock\\Models\\Transfer',
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
 * @return HasMany<TransferLine, $this>
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
        'namespace' => 'App\\Domain\\Stock\\Models',
        'declaringClassName' => 'App\\Domain\\Stock\\Models\\Transfer',
        'implementingClassName' => 'App\\Domain\\Stock\\Models\\Transfer',
        'currentClassName' => 'App\\Domain\\Stock\\Models\\Transfer',
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