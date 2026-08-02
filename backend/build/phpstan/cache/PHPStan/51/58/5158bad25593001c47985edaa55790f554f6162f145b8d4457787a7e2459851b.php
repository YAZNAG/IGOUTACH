<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Expenses\Models\ExpenseCategory.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Expenses\Models\ExpenseCategory
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-e00f445f37972dbb5da2bb2646bdc7210d35bb3d5425345de9e7ea690cab18ee',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Expenses\\Models\\ExpenseCategory',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Expenses/Models/ExpenseCategory.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Expenses\\Models',
    'name' => 'App\\Domain\\Expenses\\Models\\ExpenseCategory',
    'shortName' => 'ExpenseCategory',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Catégorie de charge (loyer, carburant, fournitures…).
 *
 * @property int $id
 * @property string $name
 * @property bool $is_active
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 36,
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
        'declaringClassName' => 'App\\Domain\\Expenses\\Models\\ExpenseCategory',
        'implementingClassName' => 'App\\Domain\\Expenses\\Models\\ExpenseCategory',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'name\', \'is_active\']',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 45,
            'startFilePos' => 382,
            'endTokenPos' => 50,
            'endFilePos' => 402,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 48,
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
        'startLine' => 24,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Domain\\Expenses\\Models',
        'declaringClassName' => 'App\\Domain\\Expenses\\Models\\ExpenseCategory',
        'implementingClassName' => 'App\\Domain\\Expenses\\Models\\ExpenseCategory',
        'currentClassName' => 'App\\Domain\\Expenses\\Models\\ExpenseCategory',
        'aliasName' => NULL,
      ),
      'expenses' => 
      array (
        'name' => 'expenses',
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
 * @return HasMany<Expense, $this>
 */',
        'startLine' => 32,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Expenses\\Models',
        'declaringClassName' => 'App\\Domain\\Expenses\\Models\\ExpenseCategory',
        'implementingClassName' => 'App\\Domain\\Expenses\\Models\\ExpenseCategory',
        'currentClassName' => 'App\\Domain\\Expenses\\Models\\ExpenseCategory',
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