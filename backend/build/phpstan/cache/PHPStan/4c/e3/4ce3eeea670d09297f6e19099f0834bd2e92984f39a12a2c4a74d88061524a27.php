<?php declare(strict_types = 1);

// osfsl-C:/OPTIZAWORKS/igoutech/backend/vendor/composer/../laravel/framework/src/Illuminate/Auth/GuardHelpers.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Auth\GuardHelpers
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-0746f59988750ff8670cd7164608b2af1e92064c984480074a75cfa595434065-8.3.22-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Auth\\GuardHelpers',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/vendor/composer/../laravel/framework/src/Illuminate/Auth/GuardHelpers.php',
      ),
    ),
    'namespace' => 'Illuminate\\Auth',
    'name' => 'Illuminate\\Auth\\GuardHelpers',
    'shortName' => 'GuardHelpers',
    'isInterface' => false,
    'isTrait' => true,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * These methods are typically the same across all guards.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 11,
    'endLine' => 126,
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
    ),
    'immediateProperties' => 
    array (
      'user' => 
      array (
        'declaringClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'implementingClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'name' => 'user',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The currently authenticated user.
 *
 * @var \\Illuminate\\Contracts\\Auth\\Authenticatable|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 20,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'provider' => 
      array (
        'declaringClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'implementingClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'name' => 'provider',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The user provider implementation.
 *
 * @var \\Illuminate\\Contracts\\Auth\\UserProvider
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
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
      'authenticate' => 
      array (
        'name' => 'authenticate',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine if the current user is authenticated. If not, throw an exception.
 *
 * @return \\Illuminate\\Contracts\\Auth\\Authenticatable
 *
 * @throws \\Illuminate\\Auth\\AuthenticationException
 */',
        'startLine' => 34,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'implementingClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'currentClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'aliasName' => NULL,
      ),
      'hasUser' => 
      array (
        'name' => 'hasUser',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine if the guard has a user instance.
 *
 * @return bool
 */',
        'startLine' => 44,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'implementingClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'currentClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'aliasName' => NULL,
      ),
      'check' => 
      array (
        'name' => 'check',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine if the current user is authenticated.
 *
 * @return bool
 */',
        'startLine' => 54,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'implementingClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'currentClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'aliasName' => NULL,
      ),
      'guest' => 
      array (
        'name' => 'guest',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine if the current user is a guest.
 *
 * @return bool
 */',
        'startLine' => 64,
        'endLine' => 67,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'implementingClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'currentClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'aliasName' => NULL,
      ),
      'id' => 
      array (
        'name' => 'id',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the ID for the currently authenticated user.
 *
 * @return int|string|null
 */',
        'startLine' => 74,
        'endLine' => 79,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'implementingClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'currentClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'aliasName' => NULL,
      ),
      'setUser' => 
      array (
        'name' => 'setUser',
        'parameters' => 
        array (
          'user' => 
          array (
            'name' => 'user',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Contracts\\Auth\\Authenticatable',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 29,
            'endColumn' => 57,
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
 * Set the current user.
 *
 * @param  \\Illuminate\\Contracts\\Auth\\Authenticatable  $user
 * @return $this
 */',
        'startLine' => 87,
        'endLine' => 92,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'implementingClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'currentClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'aliasName' => NULL,
      ),
      'forgetUser' => 
      array (
        'name' => 'forgetUser',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Forget the current user.
 *
 * @return $this
 */',
        'startLine' => 99,
        'endLine' => 104,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'implementingClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'currentClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'aliasName' => NULL,
      ),
      'getProvider' => 
      array (
        'name' => 'getProvider',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the user provider used by the guard.
 *
 * @return \\Illuminate\\Contracts\\Auth\\UserProvider
 */',
        'startLine' => 111,
        'endLine' => 114,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'implementingClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'currentClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'aliasName' => NULL,
      ),
      'setProvider' => 
      array (
        'name' => 'setProvider',
        'parameters' => 
        array (
          'provider' => 
          array (
            'name' => 'provider',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Contracts\\Auth\\UserProvider',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 122,
            'endLine' => 122,
            'startColumn' => 33,
            'endColumn' => 54,
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
 * Set the user provider used by the guard.
 *
 * @param  \\Illuminate\\Contracts\\Auth\\UserProvider  $provider
 * @return void
 */',
        'startLine' => 122,
        'endLine' => 125,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth',
        'declaringClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'implementingClassName' => 'Illuminate\\Auth\\GuardHelpers',
        'currentClassName' => 'Illuminate\\Auth\\GuardHelpers',
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