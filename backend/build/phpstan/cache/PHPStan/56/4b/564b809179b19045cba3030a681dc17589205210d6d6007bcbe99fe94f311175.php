<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Catalog\Actions\DeleteProductAction.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Catalog\Actions\DeleteProductAction
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-73b5929f69a2de0399462274fd5239c421b006b6b64b7d0d02d9c21ab4f25e7a',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Catalog\\Actions\\DeleteProductAction',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Catalog/Actions/DeleteProductAction.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Catalog\\Actions',
    'name' => 'App\\Domain\\Catalog\\Actions\\DeleteProductAction',
    'shortName' => 'DeleteProductAction',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 44,
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
          'product' => 
          array (
            'name' => 'product',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Domain\\Catalog\\Models\\Product',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 29,
            'endColumn' => 44,
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
        'docComment' => '/**
 * Supprime (soft delete) un article uniquement s\'il n\'est engagé nulle part :
 * aucun mouvement de stock, aucun transfert, aucun inventaire, aucun stock non nul.
 *
 * @throws ProductInUseException
 */',
        'startLine' => 22,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Catalog\\Actions',
        'declaringClassName' => 'App\\Domain\\Catalog\\Actions\\DeleteProductAction',
        'implementingClassName' => 'App\\Domain\\Catalog\\Actions\\DeleteProductAction',
        'currentClassName' => 'App\\Domain\\Catalog\\Actions\\DeleteProductAction',
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