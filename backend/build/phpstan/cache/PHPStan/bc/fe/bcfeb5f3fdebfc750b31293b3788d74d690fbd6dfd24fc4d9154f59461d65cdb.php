<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Settings\Models\PaymentMethod.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Settings\Models\PaymentMethod
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-2bdb2127da914e317f8d3e5175eefb94865d4d8b0074c89c7e56e09876a7d7a6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Settings\\Models\\PaymentMethod',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Settings/Models/PaymentMethod.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Settings\\Models',
    'name' => 'App\\Domain\\Settings\\Models\\PaymentMethod',
    'shortName' => 'PaymentMethod',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Mode de paiement paramétrable.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $type
 * @property bool $is_active
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
    'endLine' => 39,
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
      'TYPES' => 
      array (
        'declaringClassName' => 'App\\Domain\\Settings\\Models\\PaymentMethod',
        'implementingClassName' => 'App\\Domain\\Settings\\Models\\PaymentMethod',
        'name' => 'TYPES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'cash\', \'cheque\', \'transfer\', \'card\', \'other\']',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 49,
            'startFilePos' => 529,
            'endTokenPos' => 63,
            'endFilePos' => 575,
          ),
        ),
        'docComment' => '/** Types de règlement supportés. */',
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 73,
      ),
    ),
    'immediateProperties' => 
    array (
      'fillable' => 
      array (
        'declaringClassName' => 'App\\Domain\\Settings\\Models\\PaymentMethod',
        'implementingClassName' => 'App\\Domain\\Settings\\Models\\PaymentMethod',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'code\', \'name\', \'type\', \'is_active\', \'position\']',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 72,
            'startFilePos' => 605,
            'endTokenPos' => 86,
            'endFilePos' => 653,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
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
        'startLine' => 32,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Domain\\Settings\\Models',
        'declaringClassName' => 'App\\Domain\\Settings\\Models\\PaymentMethod',
        'implementingClassName' => 'App\\Domain\\Settings\\Models\\PaymentMethod',
        'currentClassName' => 'App\\Domain\\Settings\\Models\\PaymentMethod',
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