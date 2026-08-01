<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\app\Domain\Stock\Models\Transfer.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Stock\Models\Transfer
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-4df0f135d9767c868e50385fd02a3608f6099dde6cfd8d04ad22e6a2c2460597',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Stock\\Models\\Transfer',
        'filename' => 'C:/OPTIZAWORKS/igoutech/app/Domain/Stock/Models/Transfer.php',
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
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
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
            'startLine' => 21,
            'endLine' => 31,
            'startTokenPos' => 53,
            'startFilePos' => 484,
            'endTokenPos' => 82,
            'endFilePos' => 700,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
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
        'docComment' => NULL,
        'startLine' => 33,
        'endLine' => 39,
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
        'startLine' => 44,
        'endLine' => 47,
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
        'startLine' => 52,
        'endLine' => 55,
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
        'startLine' => 60,
        'endLine' => 63,
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
        'startLine' => 68,
        'endLine' => 71,
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