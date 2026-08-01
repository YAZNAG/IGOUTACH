<?php declare(strict_types = 1);

// osfsl-C:/OPTIZAWORKS/igoutech/backend/vendor/composer/../laravel/framework/src/Illuminate/Database/Console/Migrations/StatusCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Database\Console\Migrations\StatusCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-31152d8111d9410dc368f9e5d90da8f8f9d7a87023e931f3392795751645f0c2-8.3.22-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/vendor/composer/../laravel/framework/src/Illuminate/Database/Console/Migrations/StatusCommand.php',
      ),
    ),
    'namespace' => 'Illuminate\\Database\\Console\\Migrations',
    'name' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
    'shortName' => 'StatusCommand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
      0 => 
      array (
        'name' => 'Symfony\\Component\\Console\\Attribute\\AsCommand',
        'isRepeated' => false,
        'arguments' => 
        array (
          'name' => 
          array (
            'code' => '\'migrate:status\'',
            'attributes' => 
            array (
              'startLine' => 11,
              'endLine' => 11,
              'startTokenPos' => 38,
              'startFilePos' => 292,
              'endTokenPos' => 38,
              'endFilePos' => 307,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 11,
    'endLine' => 143,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Console\\Migrations\\BaseCommand',
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
      'name' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'name' => 'name',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'migrate:status\'',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 60,
            'startFilePos' => 450,
            'endTokenPos' => 60,
            'endFilePos' => 465,
          ),
        ),
        'docComment' => '/**
 * The console command name.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 39,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Show the status of each migration\'',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 26,
            'startTokenPos' => 71,
            'startFilePos' => 580,
            'endTokenPos' => 71,
            'endFilePos' => 614,
          ),
        ),
        'docComment' => '/**
 * The console command description.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 65,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'migrator' => 
      array (
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'name' => 'migrator',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The migrator instance.
 *
 * @var \\Illuminate\\Database\\Migrations\\Migrator
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 24,
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
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'migrator' => 
          array (
            'name' => 'migrator',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Database\\Migrations\\Migrator',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 41,
            'endLine' => 41,
            'startColumn' => 33,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a new migration rollback command instance.
 *
 * @param  \\Illuminate\\Database\\Migrations\\Migrator  $migrator
 * @return void
 */',
        'startLine' => 41,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Console\\Migrations',
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'aliasName' => NULL,
      ),
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Execute the console command.
 *
 * @return int|null
 */',
        'startLine' => 53,
        'endLine' => 92,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Database\\Console\\Migrations',
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'aliasName' => NULL,
      ),
      'getStatusFor' => 
      array (
        'name' => 'getStatusFor',
        'parameters' => 
        array (
          'ran' => 
          array (
            'name' => 'ran',
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
            'startLine' => 101,
            'endLine' => 101,
            'startColumn' => 37,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'batches' => 
          array (
            'name' => 'batches',
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
            'startLine' => 101,
            'endLine' => 101,
            'startColumn' => 49,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the status for the given run migrations.
 *
 * @param  array  $ran
 * @param  array  $batches
 * @return \\Illuminate\\Support\\Collection
 */',
        'startLine' => 101,
        'endLine' => 117,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Console\\Migrations',
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'aliasName' => NULL,
      ),
      'getAllMigrationFiles' => 
      array (
        'name' => 'getAllMigrationFiles',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get an array of all of the migration files.
 *
 * @return array
 */',
        'startLine' => 124,
        'endLine' => 127,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Console\\Migrations',
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'aliasName' => NULL,
      ),
      'getOptions' => 
      array (
        'name' => 'getOptions',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the console command options.
 *
 * @return array
 */',
        'startLine' => 134,
        'endLine' => 142,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Database\\Console\\Migrations',
        'declaringClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'implementingClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
        'currentClassName' => 'Illuminate\\Database\\Console\\Migrations\\StatusCommand',
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