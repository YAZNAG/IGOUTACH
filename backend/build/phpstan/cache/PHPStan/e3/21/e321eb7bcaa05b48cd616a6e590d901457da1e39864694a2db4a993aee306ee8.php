<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Purchasing\Models\SupplierContact.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Purchasing\Models\SupplierContact
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-83072f1a0fd1ceb81798751457ca7a7c7d43852adf8cf872af7ddb72af0e69c9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Purchasing\\Models\\SupplierContact',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Purchasing/Models/SupplierContact.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Purchasing\\Models',
    'name' => 'App\\Domain\\Purchasing\\Models\\SupplierContact',
    'shortName' => 'SupplierContact',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Contact d\'un fournisseur (nom, fonction, téléphone, e-mail).
 *
 * @property int $id
 * @property int $supplier_id
 * @property string $name
 * @property string|null $role
 * @property string|null $phone
 * @property string|null $email
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 20,
    'endLine' => 31,
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
        'declaringClassName' => 'App\\Domain\\Purchasing\\Models\\SupplierContact',
        'implementingClassName' => 'App\\Domain\\Purchasing\\Models\\SupplierContact',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'supplier_id\', \'name\', \'role\', \'phone\', \'email\']',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 22,
            'startTokenPos' => 45,
            'startFilePos' => 488,
            'endTokenPos' => 59,
            'endFilePos' => 536,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 76,
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
      'supplier' => 
      array (
        'name' => 'supplier',
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
 * @return BelongsTo<Supplier, $this>
 */',
        'startLine' => 27,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Purchasing\\Models',
        'declaringClassName' => 'App\\Domain\\Purchasing\\Models\\SupplierContact',
        'implementingClassName' => 'App\\Domain\\Purchasing\\Models\\SupplierContact',
        'currentClassName' => 'App\\Domain\\Purchasing\\Models\\SupplierContact',
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