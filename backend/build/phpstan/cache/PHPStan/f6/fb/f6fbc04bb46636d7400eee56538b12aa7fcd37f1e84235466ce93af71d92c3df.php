<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\database\seeders\PermissionSeeder.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Database\Seeders\PermissionSeeder
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-2ce337fc73980404ee1a5b3e6eab4d4a07855787736dfb75ef7351f63976df58',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Database\\Seeders\\PermissionSeeder',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/database/seeders/PermissionSeeder.php',
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
    'endLine' => 144,
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
    [\'name\' => \'stock.issue\', \'module\' => \'stock\', \'display_name\' => \'Créer un bon de sortie\'],
    [\'name\' => \'stock.entry\', \'module\' => \'stock\', \'display_name\' => \'Créer un bon d\\\'entrée\'],
    // Transferts
    [\'name\' => \'transfer.create\', \'module\' => \'stock\', \'display_name\' => \'Créer un transfert\'],
    [\'name\' => \'transfer.receive\', \'module\' => \'stock\', \'display_name\' => \'Réceptionner un transfert\'],
    // Inventaires
    [\'name\' => \'inventory.create\', \'module\' => \'stock\', \'display_name\' => \'Créer un inventaire\'],
    [\'name\' => \'inventory.approve\', \'module\' => \'stock\', \'display_name\' => \'Valider un inventaire\'],
    // Lieux
    [\'name\' => \'warehouse.view\', \'module\' => \'warehouses\', \'display_name\' => \'Consulter les lieux\'],
    [\'name\' => \'warehouse.create\', \'module\' => \'warehouses\', \'display_name\' => \'Créer un lieu\'],
    [\'name\' => \'warehouse.update\', \'module\' => \'warehouses\', \'display_name\' => \'Modifier un lieu\'],
    [\'name\' => \'warehouse.manage\', \'module\' => \'warehouses\', \'display_name\' => \'Gérer les lieux (activer/désactiver)\'],
    [\'name\' => \'warehouse.assign_users\', \'module\' => \'warehouses\', \'display_name\' => \'Rattacher des utilisateurs à un lieu\'],
    // Catalogue — catégories
    [\'name\' => \'category.view\', \'module\' => \'catalog\', \'display_name\' => \'Consulter les catégories\'],
    [\'name\' => \'category.create\', \'module\' => \'catalog\', \'display_name\' => \'Créer une catégorie\'],
    [\'name\' => \'category.update\', \'module\' => \'catalog\', \'display_name\' => \'Modifier une catégorie\'],
    [\'name\' => \'category.delete\', \'module\' => \'catalog\', \'display_name\' => \'Supprimer une catégorie\'],
    // Catalogue — articles
    [\'name\' => \'product.view\', \'module\' => \'catalog\', \'display_name\' => \'Consulter les articles\'],
    [\'name\' => \'product.create\', \'module\' => \'catalog\', \'display_name\' => \'Créer un article\'],
    [\'name\' => \'product.update\', \'module\' => \'catalog\', \'display_name\' => \'Modifier un article\'],
    [\'name\' => \'product.delete\', \'module\' => \'catalog\', \'display_name\' => \'Supprimer un article\'],
    [\'name\' => \'product.import\', \'module\' => \'catalog\', \'display_name\' => \'Importer des articles\'],
    [\'name\' => \'product.view_cost_price\', \'module\' => \'catalog\', \'display_name\' => "Voir les prix d\'achat"],
    [\'name\' => \'product.set_price\', \'module\' => \'catalog\', \'display_name\' => \'Paramétrer les tarifs de vente\'],
    // Catalogue — unités & marques
    [\'name\' => \'unit.view\', \'module\' => \'catalog\', \'display_name\' => \'Consulter les unités\'],
    [\'name\' => \'unit.manage\', \'module\' => \'catalog\', \'display_name\' => \'Gérer les unités\'],
    [\'name\' => \'tax_rate.view\', \'module\' => \'catalog\', \'display_name\' => \'Consulter les taux de TVA\'],
    [\'name\' => \'tax_rate.manage\', \'module\' => \'catalog\', \'display_name\' => \'Gérer les taux de TVA\'],
    [\'name\' => \'brand.view\', \'module\' => \'catalog\', \'display_name\' => \'Consulter les marques\'],
    [\'name\' => \'brand.manage\', \'module\' => \'catalog\', \'display_name\' => \'Gérer les marques\'],
    [\'name\' => \'product.attributes_manage\', \'module\' => \'catalog\', \'display_name\' => \'Gérer la fiche technique\'],
    [\'name\' => \'product.media_manage\', \'module\' => \'catalog\', \'display_name\' => \'Gérer les médias des articles\'],
    [\'name\' => \'product.labels_print\', \'module\' => \'catalog\', \'display_name\' => \'Imprimer les étiquettes\'],
    [\'name\' => \'serial.view\', \'module\' => \'catalog\', \'display_name\' => \'Consulter les numéros de série\'],
    // Tarifs (3 niveaux)
    [\'name\' => \'price.view\', \'module\' => \'pricing\', \'display_name\' => \'Consulter les tarifs\'],
    [\'name\' => \'price.manage\', \'module\' => \'pricing\', \'display_name\' => \'Gérer les tarifs (3 niveaux)\'],
    [\'name\' => \'price.bulk_update\', \'module\' => \'pricing\', \'display_name\' => \'Mise à jour des tarifs en masse\'],
    [\'name\' => \'sale.sell_below_floor\', \'module\' => \'sales\', \'display_name\' => \'Vendre sous le prix plancher\'],
    // Achats — fournisseurs
    [\'name\' => \'supplier.view\', \'module\' => \'purchases\', \'display_name\' => \'Consulter les fournisseurs\'],
    [\'name\' => \'supplier.create\', \'module\' => \'purchases\', \'display_name\' => \'Créer un fournisseur\'],
    [\'name\' => \'supplier.update\', \'module\' => \'purchases\', \'display_name\' => \'Modifier un fournisseur\'],
    [\'name\' => \'supplier.delete\', \'module\' => \'purchases\', \'display_name\' => \'Supprimer un fournisseur\'],
    // Achats
    [\'name\' => \'purchase.create\', \'module\' => \'purchases\', \'display_name\' => \'Créer un bon de commande\'],
    [\'name\' => \'receipt.create\', \'module\' => \'purchases\', \'display_name\' => \'Créer un bon de réception\'],
    // Ventes
    [\'name\' => \'sale.create\', \'module\' => \'sales\', \'display_name\' => \'Créer une vente\'],
    [\'name\' => \'sale.cancel\', \'module\' => \'sales\', \'display_name\' => \'Annuler une vente\'],
    [\'name\' => \'sale.discount_over_limit\', \'module\' => \'sales\', \'display_name\' => \'Remise au-delà de la limite\'],
    // Clients
    [\'name\' => \'customer.view\', \'module\' => \'customers\', \'display_name\' => \'Consulter les clients\'],
    [\'name\' => \'customer.create\', \'module\' => \'customers\', \'display_name\' => \'Créer un client\'],
    [\'name\' => \'customer.update\', \'module\' => \'customers\', \'display_name\' => \'Modifier un client\'],
    [\'name\' => \'customer.delete\', \'module\' => \'customers\', \'display_name\' => \'Supprimer un client\'],
    [\'name\' => \'customer.set_credit_limit\', \'module\' => \'customers\', \'display_name\' => \'Définir un plafond de crédit / débloquer\'],
    // Règlements & caisse
    [\'name\' => \'payment.view\', \'module\' => \'payments\', \'display_name\' => \'Consulter les encaissements\'],
    [\'name\' => \'payment.create\', \'module\' => \'payments\', \'display_name\' => \'Enregistrer un encaissement\'],
    [\'name\' => \'cash.manage\', \'module\' => \'payments\', \'display_name\' => \'Gérer la caisse (ouverture / clôture)\'],
    // Charges
    [\'name\' => \'expense.create\', \'module\' => \'expenses\', \'display_name\' => \'Créer une charge\'],
    [\'name\' => \'expense.approve\', \'module\' => \'expenses\', \'display_name\' => \'Valider une charge\'],
    // Accès — utilisateurs
    [\'name\' => \'user.view\', \'module\' => \'access\', \'display_name\' => \'Consulter les utilisateurs\'],
    [\'name\' => \'user.create\', \'module\' => \'access\', \'display_name\' => \'Créer un utilisateur\'],
    [\'name\' => \'user.update\', \'module\' => \'access\', \'display_name\' => \'Modifier un utilisateur\'],
    [\'name\' => \'user.deactivate\', \'module\' => \'access\', \'display_name\' => \'Désactiver un utilisateur\'],
    [\'name\' => \'user.assign_role\', \'module\' => \'access\', \'display_name\' => \'Assigner un rôle\'],
    [\'name\' => \'user.manage_permissions\', \'module\' => \'access\', \'display_name\' => \'Gérer les dérogations de permissions\'],
    [\'name\' => \'user.manage_sessions\', \'module\' => \'access\', \'display_name\' => \'Gérer les sessions des utilisateurs\'],
    // Accès — rôles & permissions
    [\'name\' => \'role.view\', \'module\' => \'access\', \'display_name\' => \'Consulter les rôles\'],
    [\'name\' => \'role.manage\', \'module\' => \'access\', \'display_name\' => \'Gérer les rôles\'],
    [\'name\' => \'permission.view\', \'module\' => \'access\', \'display_name\' => \'Consulter les permissions\'],
    // Rapports & audit
    [\'name\' => \'report.consolidated\', \'module\' => \'reports\', \'display_name\' => \'Rapports consolidés\'],
    [\'name\' => \'audit.view\', \'module\' => \'reports\', \'display_name\' => "Consulter le journal d\'audit"],
    [\'name\' => \'audit.export\', \'module\' => \'reports\', \'display_name\' => "Exporter le journal d\'audit"],
    // Paramètres généraux
    [\'name\' => \'settings.view\', \'module\' => \'settings\', \'display_name\' => \'Consulter les paramètres\'],
    [\'name\' => \'settings.manage\', \'module\' => \'settings\', \'display_name\' => \'Gérer les paramètres généraux\'],
    [\'name\' => \'payment_method.view\', \'module\' => \'settings\', \'display_name\' => \'Consulter les modes de paiement\'],
    [\'name\' => \'payment_method.manage\', \'module\' => \'settings\', \'display_name\' => \'Gérer les modes de paiement\'],
    [\'name\' => \'system.backup\', \'module\' => \'settings\', \'display_name\' => \'Sauvegarder la base de données\'],
]',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 130,
            'startTokenPos' => 47,
            'startFilePos' => 452,
            'endTokenPos' => 1833,
            'endFilePos' => 9206,
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
        'endLine' => 130,
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
        'startLine' => 132,
        'endLine' => 143,
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