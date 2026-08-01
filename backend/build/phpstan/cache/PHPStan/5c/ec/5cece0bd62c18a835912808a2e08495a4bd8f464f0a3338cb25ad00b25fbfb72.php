<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Catalog\Actions\DeleteUnitAction.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Catalog\Actions\DeleteUnitAction
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-531ac6d193faaaf7cb86feea61c2f29d9f35e428a40e3af48df08e472e17dabd',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Catalog\\Actions\\DeleteUnitAction',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Catalog/Actions/DeleteUnitAction.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Catalog\\Actions',
    'name' => 'App\\Domain\\Catalog\\Actions\\DeleteUnitAction',
    'shortName' => 'DeleteUnitAction',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * « Suppression » d\'une unité = désactivation logique.
 * Bloquée si l\'unité est encore rattachée à des articles.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 26,
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
      'execute' => 
      array (
        'name' => 'execute',
        'parameters' => 
        array (
          'unit' => 
          array (
            'name' => 'unit',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Domain\\Catalog\\Models\\Unit',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 16,
            'endLine' => 16,
            'startColumn' => 29,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 16,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Catalog\\Actions',
        'declaringClassName' => 'App\\Domain\\Catalog\\Actions\\DeleteUnitAction',
        'implementingClassName' => 'App\\Domain\\Catalog\\Actions\\DeleteUnitAction',
        'currentClassName' => 'App\\Domain\\Catalog\\Actions\\DeleteUnitAction',
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