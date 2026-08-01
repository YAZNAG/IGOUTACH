<?php declare(strict_types = 1);

// osfsl-C:/OPTIZAWORKS/igoutech/backend/vendor/composer/../laravel/framework/src/Illuminate/Cache/Console/ClearCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Cache\Console\ClearCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-5084117a300b34e7ab53ff6ba1d01fd76b864a79e74908ec52a4de2652cb52fd-8.3.22-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/vendor/composer/../laravel/framework/src/Illuminate/Cache/Console/ClearCommand.php',
      ),
    ),
    'namespace' => 'Illuminate\\Cache\\Console',
    'name' => 'Illuminate\\Cache\\Console\\ClearCommand',
    'shortName' => 'ClearCommand',
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
            'code' => '\'cache:clear\'',
            'attributes' => 
            array (
              'startLine' => 12,
              'endLine' => 12,
              'startTokenPos' => 43,
              'startFilePos' => 319,
              'endTokenPos' => 43,
              'endFilePos' => 331,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 12,
    'endLine' => 147,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Console\\Command',
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
        'declaringClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'name' => 'name',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'cache:clear\'',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 65,
            'startFilePos' => 469,
            'endTokenPos' => 65,
            'endFilePos' => 481,
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
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 36,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Flush the application cache\'',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 76,
            'startFilePos' => 596,
            'endTokenPos' => 76,
            'endFilePos' => 624,
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
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 59,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'cache' => 
      array (
        'declaringClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'name' => 'cache',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The cache manager instance.
 *
 * @var \\Illuminate\\Cache\\CacheManager
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 21,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'files' => 
      array (
        'declaringClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'name' => 'files',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The filesystem instance.
 *
 * @var \\Illuminate\\Filesystem\\Filesystem
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 41,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 21,
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
          'cache' => 
          array (
            'name' => 'cache',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Cache\\CacheManager',
                'isIdentifier' => false,
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
            'startColumn' => 33,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'files' => 
          array (
            'name' => 'files',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Filesystem\\Filesystem',
                'isIdentifier' => false,
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
            'startColumn' => 54,
            'endColumn' => 70,
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
 * Create a new cache clear command instance.
 *
 * @param  \\Illuminate\\Cache\\CacheManager  $cache
 * @param  \\Illuminate\\Filesystem\\Filesystem  $files
 * @return void
 */',
        'startLine' => 50,
        'endLine' => 56,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Cache\\Console',
        'declaringClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'currentClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
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
 * @return void
 */',
        'startLine' => 63,
        'endLine' => 82,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Cache\\Console',
        'declaringClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'currentClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'aliasName' => NULL,
      ),
      'flushFacades' => 
      array (
        'name' => 'flushFacades',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Flush the real-time facades stored in the cache directory.
 *
 * @return void
 */',
        'startLine' => 89,
        'endLine' => 100,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Cache\\Console',
        'declaringClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'currentClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'aliasName' => NULL,
      ),
      'cache' => 
      array (
        'name' => 'cache',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the cache instance for the command.
 *
 * @return \\Illuminate\\Cache\\Repository
 */',
        'startLine' => 107,
        'endLine' => 112,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Cache\\Console',
        'declaringClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'currentClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'aliasName' => NULL,
      ),
      'tags' => 
      array (
        'name' => 'tags',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the tags passed to the command.
 *
 * @return array
 */',
        'startLine' => 119,
        'endLine' => 122,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Cache\\Console',
        'declaringClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'currentClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'aliasName' => NULL,
      ),
      'getArguments' => 
      array (
        'name' => 'getArguments',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the console command arguments.
 *
 * @return array
 */',
        'startLine' => 129,
        'endLine' => 134,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Cache\\Console',
        'declaringClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'currentClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
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
        'startLine' => 141,
        'endLine' => 146,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Cache\\Console',
        'declaringClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'implementingClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
        'currentClassName' => 'Illuminate\\Cache\\Console\\ClearCommand',
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