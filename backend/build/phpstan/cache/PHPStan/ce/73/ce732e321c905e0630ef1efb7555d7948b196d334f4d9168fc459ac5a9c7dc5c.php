<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Settings\SettingsCatalog.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Settings\SettingsCatalog
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-a548322b3c59faf156a41da889581a7ec7bf012b7a3b5f1f2717394e6bb94163',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Settings\\SettingsCatalog',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Settings/SettingsCatalog.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Settings',
    'name' => 'App\\Domain\\Settings\\SettingsCatalog',
    'shortName' => 'SettingsCatalog',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Catalogue des paramètres généraux : clé => {group, type, default}.
 * Ajouter un paramètre = ajouter une ligne ici (jamais un ENUM en dur ailleurs).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 11,
    'endLine' => 69,
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
      'DEFINITIONS' => 
      array (
        'declaringClassName' => 'App\\Domain\\Settings\\SettingsCatalog',
        'implementingClassName' => 'App\\Domain\\Settings\\SettingsCatalog',
        'name' => 'DEFINITIONS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[
    // Société
    \'company_name\' => [\'group\' => \'company\', \'type\' => \'string\', \'default\' => \'IGOUTECH\'],
    \'company_ice\' => [\'group\' => \'company\', \'type\' => \'string\', \'default\' => \'\'],
    \'company_rc\' => [\'group\' => \'company\', \'type\' => \'string\', \'default\' => \'\'],
    \'company_if\' => [\'group\' => \'company\', \'type\' => \'string\', \'default\' => \'\'],
    \'company_patente\' => [\'group\' => \'company\', \'type\' => \'string\', \'default\' => \'\'],
    \'company_address\' => [\'group\' => \'company\', \'type\' => \'string\', \'default\' => \'\'],
    \'company_city\' => [\'group\' => \'company\', \'type\' => \'string\', \'default\' => \'\'],
    \'company_phone\' => [\'group\' => \'company\', \'type\' => \'string\', \'default\' => \'\'],
    \'company_email\' => [\'group\' => \'company\', \'type\' => \'string\', \'default\' => \'\'],
    // Règles de gestion
    \'stock_valuation_method\' => [\'group\' => \'rules\', \'type\' => \'string\', \'default\' => \'cmup\'],
    \'allow_negative_stock\' => [\'group\' => \'rules\', \'type\' => \'bool\', \'default\' => \'0\'],
    \'max_discount_percent\' => [\'group\' => \'rules\', \'type\' => \'int\', \'default\' => \'0\'],
    // Modèles d\'impression / en-têtes
    \'print_header\' => [\'group\' => \'print\', \'type\' => \'string\', \'default\' => \'\'],
    \'print_footer\' => [\'group\' => \'print\', \'type\' => \'string\', \'default\' => \'Merci de votre confiance.\'],
    \'print_show_logo\' => [\'group\' => \'print\', \'type\' => \'bool\', \'default\' => \'1\'],
    // Général
    \'locale\' => [\'group\' => \'general\', \'type\' => \'string\', \'default\' => \'fr\'],
    \'currency\' => [\'group\' => \'general\', \'type\' => \'string\', \'default\' => \'MAD\'],
]',
          'attributes' => 
          array (
            'startLine' => 16,
            'endLine' => 41,
            'startTokenPos' => 35,
            'startFilePos' => 386,
            'endTokenPos' => 504,
            'endFilePos' => 2039,
          ),
        ),
        'docComment' => '/**
 * @var array<string, array{group: string, type: string, default: string}>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 16,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'GROUPS' => 
      array (
        'declaringClassName' => 'App\\Domain\\Settings\\SettingsCatalog',
        'implementingClassName' => 'App\\Domain\\Settings\\SettingsCatalog',
        'name' => 'GROUPS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'company\', \'rules\', \'print\', \'general\']',
          'attributes' => 
          array (
            'startLine' => 48,
            'endLine' => 48,
            'startTokenPos' => 517,
            'startFilePos' => 2169,
            'endTokenPos' => 528,
            'endFilePos' => 2208,
          ),
        ),
        'docComment' => '/**
 * Groupes exposés (dans l\'ordre d\'affichage).
 *
 * @var list<string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 48,
        'endLine' => 48,
        'startColumn' => 5,
        'endColumn' => 67,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'isKnown' => 
      array (
        'name' => 'isKnown',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 50,
            'endLine' => 50,
            'startColumn' => 36,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 50,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Domain\\Settings',
        'declaringClassName' => 'App\\Domain\\Settings\\SettingsCatalog',
        'implementingClassName' => 'App\\Domain\\Settings\\SettingsCatalog',
        'currentClassName' => 'App\\Domain\\Settings\\SettingsCatalog',
        'aliasName' => NULL,
      ),
      'cast' => 
      array (
        'name' => 'cast',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 58,
            'endLine' => 58,
            'startColumn' => 33,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'raw' => 
          array (
            'name' => 'raw',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 58,
            'endLine' => 58,
            'startColumn' => 46,
            'endColumn' => 57,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'string',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'int',
                  'isIdentifier' => true,
                ),
              ),
              2 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'bool',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Convertit une valeur brute (string en base) vers son type déclaré.
 */',
        'startLine' => 58,
        'endLine' => 68,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Domain\\Settings',
        'declaringClassName' => 'App\\Domain\\Settings\\SettingsCatalog',
        'implementingClassName' => 'App\\Domain\\Settings\\SettingsCatalog',
        'currentClassName' => 'App\\Domain\\Settings\\SettingsCatalog',
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