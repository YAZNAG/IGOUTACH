<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Stock\Services\StockOverviewService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Stock\Services\StockOverviewService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-f3bbbe2c2561108a5844769bd919b140bdde74b3c0e5309fddf8f767141fb880',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Stock\\Services\\StockOverviewService',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Stock/Services/StockOverviewService.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Stock\\Services',
    'name' => 'App\\Domain\\Stock\\Services\\StockOverviewService',
    'shortName' => 'StockOverviewService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Lectures agrégées pour la vue globale (direction). Consultation seule.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 71,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
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
    ),
    'immediateMethods' => 
    array (
      'summary' => 
      array (
        'name' => 'summary',
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
 * Indicateurs consolidés, tous lieux confondus.
 *
 * @return array{warehouses: int, products: int, total_units: int, distinct_in_stock: int}
 */',
        'startLine' => 21,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Stock\\Services',
        'declaringClassName' => 'App\\Domain\\Stock\\Services\\StockOverviewService',
        'implementingClassName' => 'App\\Domain\\Stock\\Services\\StockOverviewService',
        'currentClassName' => 'App\\Domain\\Stock\\Services\\StockOverviewService',
        'aliasName' => NULL,
      ),
      'consolidatedStock' => 
      array (
        'name' => 'consolidatedStock',
        'parameters' => 
        array (
          'limit' => 
          array (
            'name' => 'limit',
            'default' => 
            array (
              'code' => '100',
              'attributes' => 
              array (
                'startLine' => 37,
                'endLine' => 37,
                'startTokenPos' => 175,
                'startFilePos' => 1196,
                'endTokenPos' => 175,
                'endFilePos' => 1198,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 37,
            'endLine' => 37,
            'startColumn' => 39,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
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
 * Stock consolidé par article : SUM(quantity) GROUP BY product_id.
 *
 * @return list<array{product_id: int, sku: string, name: string, total_quantity: int}>
 */',
        'startLine' => 37,
        'endLine' => 70,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Stock\\Services',
        'declaringClassName' => 'App\\Domain\\Stock\\Services\\StockOverviewService',
        'implementingClassName' => 'App\\Domain\\Stock\\Services\\StockOverviewService',
        'currentClassName' => 'App\\Domain\\Stock\\Services\\StockOverviewService',
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