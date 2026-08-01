<?php declare(strict_types = 1);

// osfsl-C:/OPTIZAWORKS/igoutech/backend/vendor/composer/../laravel/framework/src/Illuminate/Foundation/Console/DownCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Foundation\Console\DownCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-7bdc7ec51315b32dc766877d214f176598c81522a842e65a2474429474ccc1df-8.3.22-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/vendor/composer/../laravel/framework/src/Illuminate/Foundation/Console/DownCommand.php',
      ),
    ),
    'namespace' => 'Illuminate\\Foundation\\Console',
    'name' => 'Illuminate\\Foundation\\Console\\DownCommand',
    'shortName' => 'DownCommand',
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
            'code' => '\'down\'',
            'attributes' => 
            array (
              'startLine' => 14,
              'endLine' => 14,
              'startTokenPos' => 53,
              'startFilePos' => 385,
              'endTokenPos' => 53,
              'endFilePos' => 390,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 14,
    'endLine' => 162,
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
      'signature' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'down {--redirect= : The path that users should be redirected to}
                                 {--render= : The view that should be prerendered for display during maintenance mode}
                                 {--retry= : The number of seconds after which the request may be retried}
                                 {--refresh= : The number of seconds after which the browser may refresh}
                                 {--secret= : The secret phrase that may be used to bypass maintenance mode}
                                 {--with-secret : Generate a random secret phrase that may be used to bypass maintenance mode}
                                 {--status=503 : The status code that should be used when returning the maintenance mode response}\'',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 28,
            'startTokenPos' => 75,
            'startFilePos' => 537,
            'endTokenPos' => 75,
            'endFilePos' => 1301,
          ),
        ),
        'docComment' => '/**
 * The console command signature.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 132,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Put the application into maintenance / demo mode\'',
          'attributes' => 
          array (
            'startLine' => 35,
            'endLine' => 35,
            'startTokenPos' => 86,
            'startFilePos' => 1416,
            'endTokenPos' => 86,
            'endFilePos' => 1465,
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
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 80,
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
 * @return int
 */',
        'startLine' => 42,
        'endLine' => 75,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'aliasName' => NULL,
      ),
      'getDownFilePayload' => 
      array (
        'name' => 'getDownFilePayload',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the payload to be placed in the "down" file.
 *
 * @return array
 */',
        'startLine' => 82,
        'endLine' => 93,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'aliasName' => NULL,
      ),
      'excludedPaths' => 
      array (
        'name' => 'excludedPaths',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the paths that should be excluded from maintenance mode.
 *
 * @return array
 */',
        'startLine' => 100,
        'endLine' => 107,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'aliasName' => NULL,
      ),
      'redirectPath' => 
      array (
        'name' => 'redirectPath',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the path that users should be redirected to.
 *
 * @return string
 */',
        'startLine' => 114,
        'endLine' => 121,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'aliasName' => NULL,
      ),
      'prerenderView' => 
      array (
        'name' => 'prerenderView',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Prerender the specified view so that it can be rendered even before loading Composer.
 *
 * @return string
 */',
        'startLine' => 128,
        'endLine' => 135,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'aliasName' => NULL,
      ),
      'getRetryTime' => 
      array (
        'name' => 'getRetryTime',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the number of seconds the client should wait before retrying their request.
 *
 * @return int|null
 */',
        'startLine' => 142,
        'endLine' => 147,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'aliasName' => NULL,
      ),
      'getSecret' => 
      array (
        'name' => 'getSecret',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the secret phrase that may be used to bypass maintenance mode.
 *
 * @return string|null
 */',
        'startLine' => 154,
        'endLine' => 161,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\DownCommand',
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