<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Expenses\Models\Expense.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Expenses\Models\Expense
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-b4b144ce8087bf4b7dcffb106c4db3ad305ee8d416200432305db2c754ecedd2',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Expenses\\Models\\Expense',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Expenses/Models/Expense.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Expenses\\Models',
    'name' => 'App\\Domain\\Expenses\\Models\\Expense',
    'shortName' => 'Expense',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Charge (dépense) par lieu et par utilisateur, validée par le responsable.
 *
 * @property int $id
 * @property int $expense_category_id
 * @property int|null $warehouse_id
 * @property int $user_id
 * @property string $label
 * @property string $amount
 * @property Carbon $expense_date
 * @property string|null $receipt_path
 * @property string $status
 * @property int|null $approved_by
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 27,
    'endLine' => 81,
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
      'STATUS_PENDING' => 
      array (
        'declaringClassName' => 'App\\Domain\\Expenses\\Models\\Expense',
        'implementingClassName' => 'App\\Domain\\Expenses\\Models\\Expense',
        'name' => 'STATUS_PENDING',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'pending\'',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 29,
            'startTokenPos' => 62,
            'startFilePos' => 735,
            'endTokenPos' => 62,
            'endFilePos' => 743,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 44,
      ),
      'STATUS_APPROVED' => 
      array (
        'declaringClassName' => 'App\\Domain\\Expenses\\Models\\Expense',
        'implementingClassName' => 'App\\Domain\\Expenses\\Models\\Expense',
        'name' => 'STATUS_APPROVED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'approved\'',
          'attributes' => 
          array (
            'startLine' => 31,
            'endLine' => 31,
            'startTokenPos' => 73,
            'startFilePos' => 782,
            'endTokenPos' => 73,
            'endFilePos' => 791,
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
      'STATUS_REJECTED' => 
      array (
        'declaringClassName' => 'App\\Domain\\Expenses\\Models\\Expense',
        'implementingClassName' => 'App\\Domain\\Expenses\\Models\\Expense',
        'name' => 'STATUS_REJECTED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'rejected\'',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 84,
            'startFilePos' => 830,
            'endTokenPos' => 84,
            'endFilePos' => 839,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 46,
      ),
    ),
    'immediateProperties' => 
    array (
      'fillable' => 
      array (
        'declaringClassName' => 'App\\Domain\\Expenses\\Models\\Expense',
        'implementingClassName' => 'App\\Domain\\Expenses\\Models\\Expense',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'expense_category_id\', \'warehouse_id\', \'user_id\', \'label\', \'amount\', \'expense_date\', \'receipt_path\', \'status\', \'approved_by\']',
          'attributes' => 
          array (
            'startLine' => 35,
            'endLine' => 45,
            'startTokenPos' => 93,
            'startFilePos' => 869,
            'endTokenPos' => 122,
            'endFilePos' => 1073,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 35,
        'endLine' => 45,
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
        'startLine' => 50,
        'endLine' => 56,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Domain\\Expenses\\Models',
        'declaringClassName' => 'App\\Domain\\Expenses\\Models\\Expense',
        'implementingClassName' => 'App\\Domain\\Expenses\\Models\\Expense',
        'currentClassName' => 'App\\Domain\\Expenses\\Models\\Expense',
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
 * @return BelongsTo<ExpenseCategory, $this>
 */',
        'startLine' => 61,
        'endLine' => 64,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Expenses\\Models',
        'declaringClassName' => 'App\\Domain\\Expenses\\Models\\Expense',
        'implementingClassName' => 'App\\Domain\\Expenses\\Models\\Expense',
        'currentClassName' => 'App\\Domain\\Expenses\\Models\\Expense',
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
        'startLine' => 69,
        'endLine' => 72,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Expenses\\Models',
        'declaringClassName' => 'App\\Domain\\Expenses\\Models\\Expense',
        'implementingClassName' => 'App\\Domain\\Expenses\\Models\\Expense',
        'currentClassName' => 'App\\Domain\\Expenses\\Models\\Expense',
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
        'startLine' => 77,
        'endLine' => 80,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Expenses\\Models',
        'declaringClassName' => 'App\\Domain\\Expenses\\Models\\Expense',
        'implementingClassName' => 'App\\Domain\\Expenses\\Models\\Expense',
        'currentClassName' => 'App\\Domain\\Expenses\\Models\\Expense',
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