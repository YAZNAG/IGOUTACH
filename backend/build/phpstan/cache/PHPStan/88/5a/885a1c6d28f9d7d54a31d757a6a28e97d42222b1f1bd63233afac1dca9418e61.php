<?php declare(strict_types = 1);

// osfsl-C:/OPTIZAWORKS/igoutech/backend/vendor/composer/../laravel/framework/src/Illuminate/Auth/Passwords/PasswordBrokerManager.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Auth\Passwords\PasswordBrokerManager
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-c06df7eb88abded5a5138fbdd6b173daba2a73ad4d767a78d76804a7173081cb-8.3.22-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/vendor/composer/../laravel/framework/src/Illuminate/Auth/Passwords/PasswordBrokerManager.php',
      ),
    ),
    'namespace' => 'Illuminate\\Auth\\Passwords',
    'name' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
    'shortName' => 'PasswordBrokerManager',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @mixin \\Illuminate\\Contracts\\Auth\\PasswordBroker
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 11,
    'endLine' => 156,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Illuminate\\Contracts\\Auth\\PasswordBrokerFactory',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'app' => 
      array (
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'name' => 'app',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The application instance.
 *
 * @var \\Illuminate\\Contracts\\Foundation\\Application
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 19,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'brokers' => 
      array (
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'name' => 'brokers',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 48,
            'startFilePos' => 504,
            'endTokenPos' => 49,
            'endFilePos' => 505,
          ),
        ),
        'docComment' => '/**
 * The array of created "drivers".
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 28,
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
          'app' => 
          array (
            'name' => 'app',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 33,
            'endLine' => 33,
            'startColumn' => 33,
            'endColumn' => 36,
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
 * Create a new PasswordBroker manager instance.
 *
 * @param  \\Illuminate\\Contracts\\Foundation\\Application  $app
 * @return void
 */',
        'startLine' => 33,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth\\Passwords',
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'currentClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'aliasName' => NULL,
      ),
      'broker' => 
      array (
        'name' => 'broker',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 44,
                'endLine' => 44,
                'startTokenPos' => 88,
                'startFilePos' => 953,
                'endTokenPos' => 88,
                'endFilePos' => 956,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 44,
            'endLine' => 44,
            'startColumn' => 28,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Attempt to get the broker from the local cache.
 *
 * @param  string|null  $name
 * @return \\Illuminate\\Contracts\\Auth\\PasswordBroker
 */',
        'startLine' => 44,
        'endLine' => 49,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth\\Passwords',
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'currentClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'aliasName' => NULL,
      ),
      'resolve' => 
      array (
        'name' => 'resolve',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 59,
            'endLine' => 59,
            'startColumn' => 32,
            'endColumn' => 36,
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
 * Resolve the given broker.
 *
 * @param  string  $name
 * @return \\Illuminate\\Contracts\\Auth\\PasswordBroker
 *
 * @throws \\InvalidArgumentException
 */',
        'startLine' => 59,
        'endLine' => 76,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Auth\\Passwords',
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'currentClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'aliasName' => NULL,
      ),
      'createTokenRepository' => 
      array (
        'name' => 'createTokenRepository',
        'parameters' => 
        array (
          'config' => 
          array (
            'name' => 'config',
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
            'startLine' => 84,
            'endLine' => 84,
            'startColumn' => 46,
            'endColumn' => 58,
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
 * Create a token repository instance based on the given configuration.
 *
 * @param  array  $config
 * @return \\Illuminate\\Auth\\Passwords\\TokenRepositoryInterface
 */',
        'startLine' => 84,
        'endLine' => 111,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Auth\\Passwords',
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'currentClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'aliasName' => NULL,
      ),
      'getConfig' => 
      array (
        'name' => 'getConfig',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 119,
            'endLine' => 119,
            'startColumn' => 34,
            'endColumn' => 38,
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
 * Get the password broker configuration.
 *
 * @param  string  $name
 * @return array|null
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
        'namespace' => 'Illuminate\\Auth\\Passwords',
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'currentClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'aliasName' => NULL,
      ),
      'getDefaultDriver' => 
      array (
        'name' => 'getDefaultDriver',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the default password broker name.
 *
 * @return string
 */',
        'startLine' => 129,
        'endLine' => 132,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth\\Passwords',
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'currentClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'aliasName' => NULL,
      ),
      'setDefaultDriver' => 
      array (
        'name' => 'setDefaultDriver',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 140,
            'endLine' => 140,
            'startColumn' => 38,
            'endColumn' => 42,
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
 * Set the default password broker name.
 *
 * @param  string  $name
 * @return void
 */',
        'startLine' => 140,
        'endLine' => 143,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth\\Passwords',
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'currentClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'aliasName' => NULL,
      ),
      '__call' => 
      array (
        'name' => '__call',
        'parameters' => 
        array (
          'method' => 
          array (
            'name' => 'method',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 152,
            'endLine' => 152,
            'startColumn' => 28,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'parameters' => 
          array (
            'name' => 'parameters',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 152,
            'endLine' => 152,
            'startColumn' => 37,
            'endColumn' => 47,
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
 * Dynamically call the default driver instance.
 *
 * @param  string  $method
 * @param  array  $parameters
 * @return mixed
 */',
        'startLine' => 152,
        'endLine' => 155,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth\\Passwords',
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
        'currentClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBrokerManager',
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