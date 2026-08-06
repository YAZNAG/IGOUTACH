<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Purchasing\Actions\ReceivePurchaseOrderLineAction.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Purchasing\Actions\ReceivePurchaseOrderLineAction
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-ef49493e9209c9cb5ec17ea0f07da2469b110828338330fcb95a277940a0ffe4',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Purchasing\\Actions\\ReceivePurchaseOrderLineAction',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Purchasing/Actions/ReceivePurchaseOrderLineAction.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Purchasing\\Actions',
    'name' => 'App\\Domain\\Purchasing\\Actions\\ReceivePurchaseOrderLineAction',
    'shortName' => 'ReceivePurchaseOrderLineAction',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Met à jour la received_quantity d\'une ligne et synchronise le statut du bon.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 70,
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
          'order' => 
          array (
            'name' => 'order',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
                'isIdentifier' => false,
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
            'startColumn' => 29,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'quantitiesByLineId' => 
          array (
            'name' => 'quantitiesByLineId',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
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
            'startColumn' => 51,
            'endColumn' => 75,
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
            'name' => 'App\\Domain\\Purchasing\\Models\\PurchaseOrder',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  array<int, int>  $quantitiesByLineId  Map de line_id => received_quantity
 */',
        'startLine' => 21,
        'endLine' => 69,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Purchasing\\Actions',
        'declaringClassName' => 'App\\Domain\\Purchasing\\Actions\\ReceivePurchaseOrderLineAction',
        'implementingClassName' => 'App\\Domain\\Purchasing\\Actions\\ReceivePurchaseOrderLineAction',
        'currentClassName' => 'App\\Domain\\Purchasing\\Actions\\ReceivePurchaseOrderLineAction',
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