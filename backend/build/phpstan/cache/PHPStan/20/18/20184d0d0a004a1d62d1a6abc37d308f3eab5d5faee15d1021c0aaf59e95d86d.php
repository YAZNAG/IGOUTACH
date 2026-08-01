<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\app\Domain\Stock\Models\StockMovement.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Stock\Models\StockMovement
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-2e1d7c22dbc260b1de60913acaa75c16f018c4662bb968e28f1b3a9610c741e7',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Stock\\Models\\StockMovement',
        'filename' => 'C:/OPTIZAWORKS/igoutech/app/Domain/Stock/Models/StockMovement.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Stock\\Models',
    'name' => 'App\\Domain\\Stock\\Models\\StockMovement',
    'shortName' => 'StockMovement',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Historique immuable des mouvements de stock (append-only).
 * Aucun UPDATE ni DELETE : une correction se fait par un mouvement inverse.
 *
 * @property int $id
 * @property int $warehouse_id
 * @property int $product_id
 * @property int $movement_type_id
 * @property int $quantity
 * @property string $unit_cost
 * @property int $balance_after
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 24,
    'endLine' => 68,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'App\\Support\\Concerns\\BelongsToWarehouse',
    ),
    'immediateConstants' => 
    array (
      'UPDATED_AT' => 
      array (
        'declaringClassName' => 'App\\Domain\\Stock\\Models\\StockMovement',
        'implementingClassName' => 'App\\Domain\\Stock\\Models\\StockMovement',
        'name' => 'UPDATED_AT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 28,
            'endLine' => 28,
            'startTokenPos' => 60,
            'startFilePos' => 699,
            'endTokenPos' => 60,
            'endFilePos' => 702,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
    ),
    'immediateProperties' => 
    array (
      'fillable' => 
      array (
        'declaringClassName' => 'App\\Domain\\Stock\\Models\\StockMovement',
        'implementingClassName' => 'App\\Domain\\Stock\\Models\\StockMovement',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'warehouse_id\', \'product_id\', \'movement_type_id\', \'quantity\', \'unit_cost\', \'balance_after\', \'reference_type\', \'reference_id\', \'user_id\', \'note\']',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 41,
            'startTokenPos' => 69,
            'startFilePos' => 732,
            'endTokenPos' => 101,
            'endFilePos' => 963,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 41,
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
        'startLine' => 43,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Domain\\Stock\\Models',
        'declaringClassName' => 'App\\Domain\\Stock\\Models\\StockMovement',
        'implementingClassName' => 'App\\Domain\\Stock\\Models\\StockMovement',
        'currentClassName' => 'App\\Domain\\Stock\\Models\\StockMovement',
        'aliasName' => NULL,
      ),
      'movementType' => 
      array (
        'name' => 'movementType',
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
 * @return BelongsTo<MovementType, $this>
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
        'declaringClassName' => 'App\\Domain\\Stock\\Models\\StockMovement',
        'implementingClassName' => 'App\\Domain\\Stock\\Models\\StockMovement',
        'currentClassName' => 'App\\Domain\\Stock\\Models\\StockMovement',
        'aliasName' => NULL,
      ),
      'product' => 
      array (
        'name' => 'product',
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
 * @return BelongsTo<Product, $this>
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
        'declaringClassName' => 'App\\Domain\\Stock\\Models\\StockMovement',
        'implementingClassName' => 'App\\Domain\\Stock\\Models\\StockMovement',
        'currentClassName' => 'App\\Domain\\Stock\\Models\\StockMovement',
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