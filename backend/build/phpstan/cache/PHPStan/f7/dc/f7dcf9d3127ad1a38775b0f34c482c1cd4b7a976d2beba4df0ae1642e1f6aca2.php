<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\database\seeders\SettingsSeeder.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Database\Seeders\SettingsSeeder
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-7580c9c9dad7bb2ed7ec8fae2e612d7786ce8dc11c4ed8d7228b445f004b2a88',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Database\\Seeders\\SettingsSeeder',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/database/seeders/SettingsSeeder.php',
      ),
    ),
    'namespace' => 'Database\\Seeders',
    'name' => 'Database\\Seeders\\SettingsSeeder',
    'shortName' => 'SettingsSeeder',
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
    'endLine' => 55,
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
      'PAYMENT_METHODS' => 
      array (
        'declaringClassName' => 'Database\\Seeders\\SettingsSeeder',
        'implementingClassName' => 'Database\\Seeders\\SettingsSeeder',
        'name' => 'PAYMENT_METHODS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[[\'code\' => \'CASH\', \'name\' => \'Espèces\', \'type\' => \'cash\', \'position\' => 1], [\'code\' => \'CHEQUE\', \'name\' => \'Chèque\', \'type\' => \'cheque\', \'position\' => 2], [\'code\' => \'TRANSFER\', \'name\' => \'Virement\', \'type\' => \'transfer\', \'position\' => 3], [\'code\' => \'CARD\', \'name\' => \'Carte bancaire\', \'type\' => \'card\', \'position\' => 4]]',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 23,
            'startTokenPos' => 52,
            'startFilePos' => 417,
            'endTokenPos' => 174,
            'endFilePos' => 780,
          ),
        ),
        'docComment' => '/**
 * Modes de paiement par défaut.
 *
 * @var array<int, array{code: string, name: string, type: string, position: int}>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'SEQUENCES' => 
      array (
        'declaringClassName' => 'Database\\Seeders\\SettingsSeeder',
        'implementingClassName' => 'Database\\Seeders\\SettingsSeeder',
        'name' => 'SEQUENCES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[[\'key\' => \'sale_invoice\', \'prefix\' => \'FAC-\'], [\'key\' => \'delivery_note\', \'prefix\' => \'BL-\'], [\'key\' => \'purchase_order\', \'prefix\' => \'BC-\'], [\'key\' => \'goods_receipt\', \'prefix\' => \'BR-\'], [\'key\' => \'stock_issue\', \'prefix\' => \'BS-\'], [\'key\' => \'transfer\', \'prefix\' => \'TR-\']]',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 37,
            'startTokenPos' => 187,
            'startFilePos' => 943,
            'endTokenPos' => 285,
            'endFilePos' => 1273,
          ),
        ),
        'docComment' => '/**
 * Séquences de numérotation par défaut.
 *
 * @var array<int, array{key: string, prefix: string}>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 37,
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
        'startLine' => 39,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Database\\Seeders',
        'declaringClassName' => 'Database\\Seeders\\SettingsSeeder',
        'implementingClassName' => 'Database\\Seeders\\SettingsSeeder',
        'currentClassName' => 'Database\\Seeders\\SettingsSeeder',
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