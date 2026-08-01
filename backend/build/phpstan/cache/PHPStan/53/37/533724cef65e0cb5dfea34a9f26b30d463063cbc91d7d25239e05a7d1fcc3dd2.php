<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Pricing\Contracts\MarginCalculatorInterface.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Pricing\Contracts\MarginCalculatorInterface
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-fc45302ad1796735662df4e26fb24affb628c603f4e69ab986c7c92c7bef81b3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Pricing\\Contracts\\MarginCalculatorInterface',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Pricing/Contracts/MarginCalculatorInterface.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Pricing\\Contracts',
    'name' => 'App\\Domain\\Pricing\\Contracts\\MarginCalculatorInterface',
    'shortName' => 'MarginCalculatorInterface',
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
    'endLine' => 19,
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
      'marginPercent' => 
      array (
        'name' => 'marginPercent',
        'parameters' => 
        array (
          'salePrice' => 
          array (
            'name' => 'salePrice',
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
            'startLine' => 13,
            'endLine' => 13,
            'startColumn' => 35,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'unitCost' => 
          array (
            'name' => 'unitCost',
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
            'startLine' => 13,
            'endLine' => 13,
            'startColumn' => 53,
            'endColumn' => 67,
            'parameterIndex' => 1,
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
 * Marge en pourcentage du prix de vente par rapport au coût unitaire.
 * Retourne 0 si le prix est nul.
 */',
        'startLine' => 13,
        'endLine' => 13,
        'startColumn' => 5,
        'endColumn' => 76,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Pricing\\Contracts',
        'declaringClassName' => 'App\\Domain\\Pricing\\Contracts\\MarginCalculatorInterface',
        'implementingClassName' => 'App\\Domain\\Pricing\\Contracts\\MarginCalculatorInterface',
        'currentClassName' => 'App\\Domain\\Pricing\\Contracts\\MarginCalculatorInterface',
        'aliasName' => NULL,
      ),
      'floorPrice' => 
      array (
        'name' => 'floorPrice',
        'parameters' => 
        array (
          'unitCost' => 
          array (
            'name' => 'unitCost',
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
            'startLine' => 18,
            'endLine' => 18,
            'startColumn' => 32,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'minMarginPercent' => 
          array (
            'name' => 'minMarginPercent',
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
            'startLine' => 18,
            'endLine' => 18,
            'startColumn' => 49,
            'endColumn' => 71,
            'parameterIndex' => 1,
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
 * Prix plancher = coût × (1 + marge minimale / 100).
 */',
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 80,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Pricing\\Contracts',
        'declaringClassName' => 'App\\Domain\\Pricing\\Contracts\\MarginCalculatorInterface',
        'implementingClassName' => 'App\\Domain\\Pricing\\Contracts\\MarginCalculatorInterface',
        'currentClassName' => 'App\\Domain\\Pricing\\Contracts\\MarginCalculatorInterface',
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