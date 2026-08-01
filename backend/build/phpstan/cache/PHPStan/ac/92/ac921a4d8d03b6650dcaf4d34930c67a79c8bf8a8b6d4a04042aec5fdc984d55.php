<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\database\seeders\RolePermissionSeeder.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Database\Seeders\RolePermissionSeeder
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-9a5c8a483c544e299a51e4b9e3db00967c54ee6cb7f607d6271ca1f7faaf52e6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Database\\Seeders\\RolePermissionSeeder',
        'filename' => 'C:/OPTIZAWORKS/igoutech/database/seeders/RolePermissionSeeder.php',
      ),
    ),
    'namespace' => 'Database\\Seeders',
    'name' => 'Database\\Seeders\\RolePermissionSeeder',
    'shortName' => 'RolePermissionSeeder',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 11,
    'endLine' => 54,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Seeder',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'MATRIX' => 
      array (
        'declaringClassName' => 'Database\\Seeders\\RolePermissionSeeder',
        'implementingClassName' => 'Database\\Seeders\\RolePermissionSeeder',
        'name' => 'MATRIX',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'manager\' => [\'stock.view\', \'purchase.create\', \'receipt.create\', \'transfer.create\', \'transfer.receive\', \'sale.create\', \'expense.create\', \'inventory.create\'], \'seller\' => [\'stock.view\', \'sale.create\', \'expense.create\']]',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 35,
            'startTokenPos' => 52,
            'startFilePos' => 419,
            'endTokenPos' => 105,
            'endFilePos' => 814,
          ),
        ),
        'docComment' => '/**
 * Matrice rôles → permissions (brief §9).
 * \'admin\' reçoit toutes les permissions existantes.
 *
 * @var array<string, list<string>>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'run' => 
      array (
        'name' => 'run',
        'parameters' => 
        array (
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
        'startLine' => 37,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Database\\Seeders',
        'declaringClassName' => 'Database\\Seeders\\RolePermissionSeeder',
        'implementingClassName' => 'Database\\Seeders\\RolePermissionSeeder',
        'currentClassName' => 'Database\\Seeders\\RolePermissionSeeder',
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