<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Catalog\Actions\DeleteCategoryAction.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Catalog\Actions\DeleteCategoryAction
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-9577b2f74a9ffd9d0fb0e15500f7276e1efb43681c0bda1c71475db075998d68',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Catalog\\Actions\\DeleteCategoryAction',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Catalog/Actions/DeleteCategoryAction.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Catalog\\Actions',
    'name' => 'App\\Domain\\Catalog\\Actions\\DeleteCategoryAction',
    'shortName' => 'DeleteCategoryAction',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 10,
    'endLine' => 27,
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
          'category' => 
          array (
            'name' => 'category',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Domain\\Catalog\\Models\\Category',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 29,
            'endColumn' => 46,
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
 * Supprime une catégorie uniquement si elle ne contient aucun article.
 *
 * @throws CategoryInUseException
 */',
        'startLine' => 17,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Catalog\\Actions',
        'declaringClassName' => 'App\\Domain\\Catalog\\Actions\\DeleteCategoryAction',
        'implementingClassName' => 'App\\Domain\\Catalog\\Actions\\DeleteCategoryAction',
        'currentClassName' => 'App\\Domain\\Catalog\\Actions\\DeleteCategoryAction',
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