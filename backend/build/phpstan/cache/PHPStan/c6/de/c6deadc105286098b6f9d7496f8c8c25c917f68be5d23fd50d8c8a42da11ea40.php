<?php declare(strict_types = 1);

// osfsl-C:/OPTIZAWORKS/igoutech/backend/vendor/composer/../laravel/framework/src/Illuminate/Auth/Passwords/PasswordBroker.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Auth\Passwords\PasswordBroker
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-99707348cda1ba917461d8d55f0282f87ded31d9576685a3ee50d0d38ac91590-8.3.22-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/vendor/composer/../laravel/framework/src/Illuminate/Auth/Passwords/PasswordBroker.php',
      ),
    ),
    'namespace' => 'Illuminate\\Auth\\Passwords',
    'name' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
    'shortName' => 'PasswordBroker',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 243,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Illuminate\\Contracts\\Auth\\PasswordBroker',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'tokens' => 
      array (
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'name' => 'tokens',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The password token repository.
 *
 * @var \\Illuminate\\Auth\\Passwords\\TokenRepositoryInterface
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 22,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'users' => 
      array (
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'name' => 'users',
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
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 21,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'events' => 
      array (
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'name' => 'events',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The event dispatcher instance.
 *
 * @var \\Illuminate\\Contracts\\Events\\Dispatcher
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 22,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'timebox' => 
      array (
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'name' => 'timebox',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The timebox instance.
 *
 * @var \\Illuminate\\Support\\Timebox
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 43,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 23,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'timeboxDuration' => 
      array (
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'name' => 'timeboxDuration',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The number of microseconds that the timebox should wait for.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 5,
        'endColumn' => 31,
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
          'tokens' => 
          array (
            'name' => 'tokens',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Auth\\Passwords\\TokenRepositoryInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
              0 => 
              array (
                'name' => 'SensitiveParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                ),
              ),
            ),
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 9,
            'endColumn' => 63,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'users' => 
          array (
            'name' => 'users',
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
            'startLine' => 64,
            'endLine' => 64,
            'startColumn' => 9,
            'endColumn' => 27,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'dispatcher' => 
          array (
            'name' => 'dispatcher',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 65,
                'endLine' => 65,
                'startTokenPos' => 135,
                'startFilePos' => 1731,
                'endTokenPos' => 135,
                'endFilePos' => 1734,
              ),
            ),
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
                      'name' => 'Illuminate\\Contracts\\Events\\Dispatcher',
                      'isIdentifier' => false,
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
            'startLine' => 65,
            'endLine' => 65,
            'startColumn' => 9,
            'endColumn' => 38,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'timebox' => 
          array (
            'name' => 'timebox',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 66,
                'endLine' => 66,
                'startTokenPos' => 145,
                'startFilePos' => 1765,
                'endTokenPos' => 145,
                'endFilePos' => 1768,
              ),
            ),
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
                      'name' => 'Illuminate\\Support\\Timebox',
                      'isIdentifier' => false,
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
            'startLine' => 66,
            'endLine' => 66,
            'startColumn' => 9,
            'endColumn' => 32,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'timeboxDuration' => 
          array (
            'name' => 'timeboxDuration',
            'default' => 
            array (
              'code' => '200000',
              'attributes' => 
              array (
                'startLine' => 67,
                'endLine' => 67,
                'startTokenPos' => 154,
                'startFilePos' => 1802,
                'endTokenPos' => 154,
                'endFilePos' => 1807,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 9,
            'endColumn' => 37,
            'parameterIndex' => 4,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a new password broker instance.
 *
 * @param  \\Illuminate\\Auth\\Passwords\\TokenRepositoryInterface  $tokens
 * @param  \\Illuminate\\Contracts\\Auth\\UserProvider  $users
 * @param  \\Illuminate\\Contracts\\Events\\Dispatcher|null  $dispatcher
 * @param  \\Illuminate\\Support\\Timebox|null  $timebox
 * @param  int  $timeboxDuration
 * @return void
 */',
        'startLine' => 62,
        'endLine' => 74,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth\\Passwords',
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'currentClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'aliasName' => NULL,
      ),
      'sendResetLink' => 
      array (
        'name' => 'sendResetLink',
        'parameters' => 
        array (
          'credentials' => 
          array (
            'name' => 'credentials',
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
              0 => 
              array (
                'name' => 'SensitiveParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                ),
              ),
            ),
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 35,
            'endColumn' => 75,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'callback' => 
          array (
            'name' => 'callback',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 83,
                'endLine' => 83,
                'startTokenPos' => 239,
                'startFilePos' => 2290,
                'endTokenPos' => 239,
                'endFilePos' => 2293,
              ),
            ),
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
                      'name' => 'Closure',
                      'isIdentifier' => false,
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
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 78,
            'endColumn' => 102,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Send a password reset link to a user.
 *
 * @param  array  $credentials
 * @param  \\Closure|null  $callback
 * @return string
 */',
        'startLine' => 83,
        'endLine' => 114,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth\\Passwords',
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'currentClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'aliasName' => NULL,
      ),
      'reset' => 
      array (
        'name' => 'reset',
        'parameters' => 
        array (
          'credentials' => 
          array (
            'name' => 'credentials',
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
              0 => 
              array (
                'name' => 'SensitiveParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                ),
              ),
            ),
            'startLine' => 123,
            'endLine' => 123,
            'startColumn' => 27,
            'endColumn' => 67,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'callback' => 
          array (
            'name' => 'callback',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Closure',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 123,
            'endLine' => 123,
            'startColumn' => 70,
            'endColumn' => 86,
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
 * Reset the password for the given token.
 *
 * @param  array  $credentials
 * @param  \\Closure  $callback
 * @return mixed
 */',
        'startLine' => 123,
        'endLine' => 148,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth\\Passwords',
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'currentClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'aliasName' => NULL,
      ),
      'validateReset' => 
      array (
        'name' => 'validateReset',
        'parameters' => 
        array (
          'credentials' => 
          array (
            'name' => 'credentials',
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
              0 => 
              array (
                'name' => 'SensitiveParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                ),
              ),
            ),
            'startLine' => 156,
            'endLine' => 156,
            'startColumn' => 38,
            'endColumn' => 78,
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
 * Validate a password reset for the given credentials.
 *
 * @param  array  $credentials
 * @return \\Illuminate\\Contracts\\Auth\\CanResetPassword|string
 */',
        'startLine' => 156,
        'endLine' => 167,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Auth\\Passwords',
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'currentClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'aliasName' => NULL,
      ),
      'getUser' => 
      array (
        'name' => 'getUser',
        'parameters' => 
        array (
          'credentials' => 
          array (
            'name' => 'credentials',
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
              0 => 
              array (
                'name' => 'SensitiveParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                ),
              ),
            ),
            'startLine' => 177,
            'endLine' => 177,
            'startColumn' => 29,
            'endColumn' => 69,
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
 * Get the user for the given credentials.
 *
 * @param  array  $credentials
 * @return \\Illuminate\\Contracts\\Auth\\CanResetPassword|null
 *
 * @throws \\UnexpectedValueException
 */',
        'startLine' => 177,
        'endLine' => 188,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth\\Passwords',
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'currentClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'aliasName' => NULL,
      ),
      'createToken' => 
      array (
        'name' => 'createToken',
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
                'name' => 'Illuminate\\Contracts\\Auth\\CanResetPassword',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 196,
            'endLine' => 196,
            'startColumn' => 33,
            'endColumn' => 62,
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
 * Create a new password reset token for the given user.
 *
 * @param  \\Illuminate\\Contracts\\Auth\\CanResetPassword  $user
 * @return string
 */',
        'startLine' => 196,
        'endLine' => 199,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth\\Passwords',
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'currentClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'aliasName' => NULL,
      ),
      'deleteToken' => 
      array (
        'name' => 'deleteToken',
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
                'name' => 'Illuminate\\Contracts\\Auth\\CanResetPassword',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 207,
            'endLine' => 207,
            'startColumn' => 33,
            'endColumn' => 62,
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
 * Delete password reset tokens of the given user.
 *
 * @param  \\Illuminate\\Contracts\\Auth\\CanResetPassword  $user
 * @return void
 */',
        'startLine' => 207,
        'endLine' => 210,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth\\Passwords',
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'currentClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'aliasName' => NULL,
      ),
      'tokenExists' => 
      array (
        'name' => 'tokenExists',
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
                'name' => 'Illuminate\\Contracts\\Auth\\CanResetPassword',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 219,
            'endLine' => 219,
            'startColumn' => 33,
            'endColumn' => 62,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'token' => 
          array (
            'name' => 'token',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
              0 => 
              array (
                'name' => 'SensitiveParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                ),
              ),
            ),
            'startLine' => 219,
            'endLine' => 219,
            'startColumn' => 65,
            'endColumn' => 93,
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
 * Validate the given password reset token.
 *
 * @param  \\Illuminate\\Contracts\\Auth\\CanResetPassword  $user
 * @param  string  $token
 * @return bool
 */',
        'startLine' => 219,
        'endLine' => 222,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth\\Passwords',
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'currentClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'aliasName' => NULL,
      ),
      'getRepository' => 
      array (
        'name' => 'getRepository',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the password reset token repository implementation.
 *
 * @return \\Illuminate\\Auth\\Passwords\\TokenRepositoryInterface
 */',
        'startLine' => 229,
        'endLine' => 232,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth\\Passwords',
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'currentClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'aliasName' => NULL,
      ),
      'getTimebox' => 
      array (
        'name' => 'getTimebox',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the timebox instance used by the guard.
 *
 * @return \\Illuminate\\Support\\Timebox
 */',
        'startLine' => 239,
        'endLine' => 242,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Auth\\Passwords',
        'declaringClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'implementingClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
        'currentClassName' => 'Illuminate\\Auth\\Passwords\\PasswordBroker',
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