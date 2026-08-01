<?php declare(strict_types = 1);

// osfsl-C:/OPTIZAWORKS/igoutech/backend/vendor/composer/../laravel/framework/src/Illuminate/Foundation/Console/BroadcastingInstallCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Foundation\Console\BroadcastingInstallCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-0801ee5b92c314af5c14b788e8c1cb821fceb7b98fe2e175745ddb963c5a9092-8.3.22-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Foundation\\Console\\BroadcastingInstallCommand',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/vendor/composer/../laravel/framework/src/Illuminate/Foundation/Console/BroadcastingInstallCommand.php',
      ),
    ),
    'namespace' => 'Illuminate\\Foundation\\Console',
    'name' => 'Illuminate\\Foundation\\Console\\BroadcastingInstallCommand',
    'shortName' => 'BroadcastingInstallCommand',
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
            'code' => '\'install:broadcasting\'',
            'attributes' => 
            array (
              'startLine' => 15,
              'endLine' => 15,
              'startTokenPos' => 59,
              'startFilePos' => 392,
              'endTokenPos' => 59,
              'endFilePos' => 413,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 15,
    'endLine' => 215,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Console\\Command',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Foundation\\Console\\InteractsWithComposerPackages',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'signature' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\BroadcastingInstallCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\BroadcastingInstallCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'install:broadcasting
                    {--composer=global : Absolute path to the Composer binary which should be used to install packages}
                    {--force : Overwrite any existing broadcasting routes file}
                    {--without-reverb : Do not prompt to install Laravel Reverb}
                    {--without-node : Do not prompt to install Node dependencies}\'',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 29,
            'startTokenPos' => 86,
            'startFilePos' => 631,
            'endTokenPos' => 86,
            'endFilePos' => 1015,
          ),
        ),
        'docComment' => '/**
 * The name and signature of the console command.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 83,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\BroadcastingInstallCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\BroadcastingInstallCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Create a broadcasting channel routes file\'',
          'attributes' => 
          array (
            'startLine' => 36,
            'endLine' => 36,
            'startTokenPos' => 97,
            'startFilePos' => 1130,
            'endTokenPos' => 97,
            'endFilePos' => 1172,
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
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 73,
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
        'startLine' => 43,
        'endLine' => 82,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\BroadcastingInstallCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\BroadcastingInstallCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\BroadcastingInstallCommand',
        'aliasName' => NULL,
      ),
      'uncommentChannelsRoutesFile' => 
      array (
        'name' => 'uncommentChannelsRoutesFile',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Uncomment the "channels" routes file in the application bootstrap file.
 *
 * @return void
 */',
        'startLine' => 89,
        'endLine' => 110,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\BroadcastingInstallCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\BroadcastingInstallCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\BroadcastingInstallCommand',
        'aliasName' => NULL,
      ),
      'enableBroadcastServiceProvider' => 
      array (
        'name' => 'enableBroadcastServiceProvider',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Uncomment the "BroadcastServiceProvider" in the application configuration.
 *
 * @return void
 */',
        'startLine' => 117,
        'endLine' => 135,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\BroadcastingInstallCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\BroadcastingInstallCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\BroadcastingInstallCommand',
        'aliasName' => NULL,
      ),
      'installReverb' => 
      array (
        'name' => 'installReverb',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Install Laravel Reverb into the application if desired.
 *
 * @return void
 */',
        'startLine' => 142,
        'endLine' => 165,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\BroadcastingInstallCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\BroadcastingInstallCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\BroadcastingInstallCommand',
        'aliasName' => NULL,
      ),
      'installNodeDependencies' => 
      array (
        'name' => 'installNodeDependencies',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Install and build Node dependencies.
 *
 * @return void
 */',
        'startLine' => 172,
        'endLine' => 214,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Foundation\\Console',
        'declaringClassName' => 'Illuminate\\Foundation\\Console\\BroadcastingInstallCommand',
        'implementingClassName' => 'Illuminate\\Foundation\\Console\\BroadcastingInstallCommand',
        'currentClassName' => 'Illuminate\\Foundation\\Console\\BroadcastingInstallCommand',
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