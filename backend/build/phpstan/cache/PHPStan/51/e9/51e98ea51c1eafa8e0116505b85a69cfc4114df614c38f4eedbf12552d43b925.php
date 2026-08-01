<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\app\Domain\Stock\Contracts\StockValuationInterface.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Stock\Contracts\StockValuationInterface
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-bd00dcd24dbc2c0356d433613bbb3e458962103a2138b73265ca582cf7572deb',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Stock\\Contracts\\StockValuationInterface',
        'filename' => 'C:/OPTIZAWORKS/igoutech/app/Domain/Stock/Contracts/StockValuationInterface.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Stock\\Contracts',
    'name' => 'App\\Domain\\Stock\\Contracts\\StockValuationInterface',
    'shortName' => 'StockValuationInterface',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 7,
    'endLine' => 23,
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
      'newUnitCost' => 
      array (
        'name' => 'newUnitCost',
        'parameters' => 
        array (
          'currentQty' => 
          array (
            'name' => 'currentQty',
            'default' => NULL,
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
            'startLine' => 18,
            'endLine' => 18,
            'startColumn' => 9,
            'endColumn' => 23,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'currentCost' => 
          array (
            'name' => 'currentCost',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'float',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 19,
            'endLine' => 19,
            'startColumn' => 9,
            'endColumn' => 26,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'incomingQty' => 
          array (
            'name' => 'incomingQty',
            'default' => NULL,
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
            'startLine' => 20,
            'endLine' => 20,
            'startColumn' => 9,
            'endColumn' => 24,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'incomingCost' => 
          array (
            'name' => 'incomingCost',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'float',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 9,
            'endColumn' => 27,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'float',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Calcule le nouveau coût unitaire de stock après une entrée.
 *
 * @param  int  $currentQty  quantité en stock avant l\'entrée
 * @param  float  $currentCost  coût unitaire courant
 * @param  int  $incomingQty  quantité entrante (> 0)
 * @param  float  $incomingCost  coût unitaire de l\'entrée
 */',
        'startLine' => 17,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 13,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Stock\\Contracts',
        'declaringClassName' => 'App\\Domain\\Stock\\Contracts\\StockValuationInterface',
        'implementingClassName' => 'App\\Domain\\Stock\\Contracts\\StockValuationInterface',
        'currentClassName' => 'App\\Domain\\Stock\\Contracts\\StockValuationInterface',
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