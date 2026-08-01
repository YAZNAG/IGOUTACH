<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\database\seeders\PermissionSeeder.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Database\Seeders\PermissionSeeder
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-28125c7e2b40c20587a85c3231efdf010c6286e218f3fa27a29fbb95a0f5345f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Database\\Seeders\\PermissionSeeder',
        'filename' => 'C:/OPTIZAWORKS/igoutech/database/seeders/PermissionSeeder.php',
      ),
    ),
    'namespace' => 'Database\\Seeders',
    'name' => 'Database\\Seeders\\PermissionSeeder',
    'shortName' => 'PermissionSeeder',
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
    'endLine' => 76,
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
      'PERMISSIONS' => 
      array (
        'declaringClassName' => 'Database\\Seeders\\PermissionSeeder',
        'implementingClassName' => 'Database\\Seeders\\PermissionSeeder',
        'name' => 'PERMISSIONS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[
    // Stock
    [\'name\' => \'stock.view\', \'module\' => \'stock\', \'display_name\' => \'Consulter le stock\'],
    [\'name\' => \'stock.view_global\', \'module\' => \'stock\', \'display_name\' => \'Consulter le stock consolidé (tous lieux)\'],
    [\'name\' => \'stock.adjust\', \'module\' => \'stock\', \'display_name\' => \'Ajuster le stock\'],
    // Transferts
    [\'name\' => \'transfer.create\', \'module\' => \'stock\', \'display_name\' => \'Créer un transfert\'],
    [\'name\' => \'transfer.receive\', \'module\' => \'stock\', \'display_name\' => \'Réceptionner un transfert\'],
    // Inventaires
    [\'name\' => \'inventory.create\', \'module\' => \'stock\', \'display_name\' => \'Créer un inventaire\'],
    [\'name\' => \'inventory.approve\', \'module\' => \'stock\', \'display_name\' => \'Valider un inventaire\'],
    // Catalogue
    [\'name\' => \'product.view\', \'module\' => \'catalog\', \'display_name\' => \'Consulter les articles\'],
    [\'name\' => \'product.create\', \'module\' => \'catalog\', \'display_name\' => \'Créer un article\'],
    [\'name\' => \'product.update\', \'module\' => \'catalog\', \'display_name\' => \'Modifier un article\'],
    [\'name\' => \'product.view_cost_price\', \'module\' => \'catalog\', \'display_name\' => "Voir les prix d\'achat"],
    // Achats
    [\'name\' => \'purchase.create\', \'module\' => \'purchases\', \'display_name\' => \'Créer un bon de commande\'],
    [\'name\' => \'receipt.create\', \'module\' => \'purchases\', \'display_name\' => \'Créer un bon de réception\'],
    // Ventes
    [\'name\' => \'sale.create\', \'module\' => \'sales\', \'display_name\' => \'Créer une vente\'],
    [\'name\' => \'sale.cancel\', \'module\' => \'sales\', \'display_name\' => \'Annuler une vente\'],
    [\'name\' => \'sale.discount_over_limit\', \'module\' => \'sales\', \'display_name\' => \'Remise au-delà de la limite\'],
    // Clients
    [\'name\' => \'customer.create\', \'module\' => \'customers\', \'display_name\' => \'Créer un client\'],
    [\'name\' => \'customer.set_credit_limit\', \'module\' => \'customers\', \'display_name\' => \'Définir un plafond de crédit\'],
    // Charges
    [\'name\' => \'expense.create\', \'module\' => \'expenses\', \'display_name\' => \'Créer une charge\'],
    [\'name\' => \'expense.approve\', \'module\' => \'expenses\', \'display_name\' => \'Valider une charge\'],
    // Accès
    [\'name\' => \'user.create\', \'module\' => \'access\', \'display_name\' => \'Créer un utilisateur\'],
    [\'name\' => \'user.assign_role\', \'module\' => \'access\', \'display_name\' => \'Assigner un rôle\'],
    // Rapports & audit
    [\'name\' => \'report.consolidated\', \'module\' => \'reports\', \'display_name\' => \'Rapports consolidés\'],
    [\'name\' => \'audit.view\', \'module\' => \'reports\', \'display_name\' => "Consulter le journal d\'audit"],
]',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 62,
            'startTokenPos' => 47,
            'startFilePos' => 452,
            'endTokenPos' => 621,
            'endFilePos' => 3201,
          ),
        ),
        'docComment' => '/**
 * Catalogue des permissions (convention : module.action).
 * Ajouter une permission = ajouter une ligne ici, jamais un ENUM.
 *
 * @var array<int, array{name: string, module: string, display_name: string}>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 62,
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
        'startLine' => 64,
        'endLine' => 75,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Database\\Seeders',
        'declaringClassName' => 'Database\\Seeders\\PermissionSeeder',
        'implementingClassName' => 'Database\\Seeders\\PermissionSeeder',
        'currentClassName' => 'Database\\Seeders\\PermissionSeeder',
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