<?php declare(strict_types = 1);

// osfsl-C:/OPTIZAWORKS/igoutech/backend/vendor/composer/../laravel/framework/src/Illuminate/Validation/Rules/Password.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Validation\Rules\Password
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-46bf2f93c74d9f627ca343186694dca2d8d7e514bb8495eaf370c36db4336e8b-8.3.22-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Validation\\Rules\\Password',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/vendor/composer/../laravel/framework/src/Illuminate/Validation/Rules/Password.php',
      ),
    ),
    'namespace' => 'Illuminate\\Validation\\Rules',
    'name' => 'Illuminate\\Validation\\Rules\\Password',
    'shortName' => 'Password',
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
    'endLine' => 383,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Illuminate\\Contracts\\Validation\\Rule',
      1 => 'Illuminate\\Contracts\\Validation\\DataAwareRule',
      2 => 'Illuminate\\Contracts\\Validation\\ValidatorAwareRule',
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Support\\Traits\\Conditionable',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'validator' => 
      array (
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'name' => 'validator',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The validator performing the validation.
 *
 * @var \\Illuminate\\Contracts\\Validation\\Validator
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 25,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'data' => 
      array (
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'name' => 'data',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The data under validation.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 20,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'min' => 
      array (
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'name' => 'min',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '8',
          'attributes' => 
          array (
            'startLine' => 38,
            'endLine' => 38,
            'startTokenPos' => 95,
            'startFilePos' => 880,
            'endTokenPos' => 95,
            'endFilePos' => 880,
          ),
        ),
        'docComment' => '/**
 * The minimum size of the password.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 23,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'max' => 
      array (
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'name' => 'max',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The maximum size of the password.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 45,
        'endLine' => 45,
        'startColumn' => 5,
        'endColumn' => 19,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'mixedCase' => 
      array (
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'name' => 'mixedCase',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 52,
            'endLine' => 52,
            'startTokenPos' => 113,
            'startFilePos' => 1133,
            'endTokenPos' => 113,
            'endFilePos' => 1137,
          ),
        ),
        'docComment' => '/**
 * If the password requires at least one uppercase and one lowercase letter.
 *
 * @var bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 52,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 33,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'letters' => 
      array (
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'name' => 'letters',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 59,
            'endLine' => 59,
            'startTokenPos' => 124,
            'startFilePos' => 1259,
            'endTokenPos' => 124,
            'endFilePos' => 1263,
          ),
        ),
        'docComment' => '/**
 * If the password requires at least one letter.
 *
 * @var bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 5,
        'endColumn' => 31,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'numbers' => 
      array (
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'name' => 'numbers',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 66,
            'endLine' => 66,
            'startTokenPos' => 135,
            'startFilePos' => 1385,
            'endTokenPos' => 135,
            'endFilePos' => 1389,
          ),
        ),
        'docComment' => '/**
 * If the password requires at least one number.
 *
 * @var bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 5,
        'endColumn' => 31,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'symbols' => 
      array (
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'name' => 'symbols',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 73,
            'endLine' => 73,
            'startTokenPos' => 146,
            'startFilePos' => 1511,
            'endTokenPos' => 146,
            'endFilePos' => 1515,
          ),
        ),
        'docComment' => '/**
 * If the password requires at least one symbol.
 *
 * @var bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 5,
        'endColumn' => 31,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'uncompromised' => 
      array (
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'name' => 'uncompromised',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 80,
            'endLine' => 80,
            'startTokenPos' => 157,
            'startFilePos' => 1661,
            'endTokenPos' => 157,
            'endFilePos' => 1665,
          ),
        ),
        'docComment' => '/**
 * If the password should not have been compromised in data leaks.
 *
 * @var bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 80,
        'endLine' => 80,
        'startColumn' => 5,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'compromisedThreshold' => 
      array (
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'name' => 'compromisedThreshold',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 87,
            'endLine' => 87,
            'startTokenPos' => 168,
            'startFilePos' => 1846,
            'endTokenPos' => 168,
            'endFilePos' => 1846,
          ),
        ),
        'docComment' => '/**
 * The number of times a password can appear in data leaks before being considered compromised.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 87,
        'endLine' => 87,
        'startColumn' => 5,
        'endColumn' => 40,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'customRules' => 
      array (
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'name' => 'customRules',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 94,
            'endLine' => 94,
            'startTokenPos' => 179,
            'startFilePos' => 2019,
            'endTokenPos' => 180,
            'endFilePos' => 2020,
          ),
        ),
        'docComment' => '/**
 * Additional validation rules that should be merged into the default rules during validation.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 94,
        'endLine' => 94,
        'startColumn' => 5,
        'endColumn' => 32,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'messages' => 
      array (
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'name' => 'messages',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 101,
            'endLine' => 101,
            'startTokenPos' => 191,
            'startFilePos' => 2128,
            'endTokenPos' => 192,
            'endFilePos' => 2129,
          ),
        ),
        'docComment' => '/**
 * The failure messages, if any.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 101,
        'endLine' => 101,
        'startColumn' => 5,
        'endColumn' => 29,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'defaultCallback' => 
      array (
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'name' => 'defaultCallback',
        'modifiers' => 17,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The callback that will generate the "default" version of the password rule.
 *
 * @var string|array|callable|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 108,
        'endLine' => 108,
        'startColumn' => 5,
        'endColumn' => 35,
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
          'min' => 
          array (
            'name' => 'min',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 116,
            'endLine' => 116,
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
 * Create a new rule instance.
 *
 * @param  int  $min
 * @return void
 */',
        'startLine' => 116,
        'endLine' => 119,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'aliasName' => NULL,
      ),
      'defaults' => 
      array (
        'name' => 'defaults',
        'parameters' => 
        array (
          'callback' => 
          array (
            'name' => 'callback',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 129,
                'endLine' => 129,
                'startTokenPos' => 250,
                'startFilePos' => 2842,
                'endTokenPos' => 250,
                'endFilePos' => 2845,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 129,
            'endLine' => 129,
            'startColumn' => 37,
            'endColumn' => 52,
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
 * Set the default callback to be used for determining a password\'s default rules.
 *
 * If no arguments are passed, the default password rule configuration will be returned.
 *
 * @param  static|callable|null  $callback
 * @return static|void
 */',
        'startLine' => 129,
        'endLine' => 140,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'aliasName' => NULL,
      ),
      'default' => 
      array (
        'name' => 'default',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the default configuration of the password rule.
 *
 * @return static
 */',
        'startLine' => 147,
        'endLine' => 154,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'aliasName' => NULL,
      ),
      'required' => 
      array (
        'name' => 'required',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the default configuration of the password rule and mark the field as required.
 *
 * @return array
 */',
        'startLine' => 161,
        'endLine' => 164,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'aliasName' => NULL,
      ),
      'sometimes' => 
      array (
        'name' => 'sometimes',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the default configuration of the password rule and mark the field as sometimes being required.
 *
 * @return array
 */',
        'startLine' => 171,
        'endLine' => 174,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'aliasName' => NULL,
      ),
      'setValidator' => 
      array (
        'name' => 'setValidator',
        'parameters' => 
        array (
          'validator' => 
          array (
            'name' => 'validator',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 182,
            'endLine' => 182,
            'startColumn' => 34,
            'endColumn' => 43,
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
 * Set the performing validator.
 *
 * @param  \\Illuminate\\Contracts\\Validation\\Validator  $validator
 * @return $this
 */',
        'startLine' => 182,
        'endLine' => 187,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'aliasName' => NULL,
      ),
      'setData' => 
      array (
        'name' => 'setData',
        'parameters' => 
        array (
          'data' => 
          array (
            'name' => 'data',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 195,
            'endLine' => 195,
            'startColumn' => 29,
            'endColumn' => 33,
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
 * Set the data under validation.
 *
 * @param  array  $data
 * @return $this
 */',
        'startLine' => 195,
        'endLine' => 200,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'aliasName' => NULL,
      ),
      'min' => 
      array (
        'name' => 'min',
        'parameters' => 
        array (
          'size' => 
          array (
            'name' => 'size',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 208,
            'endLine' => 208,
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
 * Set the minimum size of the password.
 *
 * @param  int  $size
 * @return $this
 */',
        'startLine' => 208,
        'endLine' => 211,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'aliasName' => NULL,
      ),
      'max' => 
      array (
        'name' => 'max',
        'parameters' => 
        array (
          'size' => 
          array (
            'name' => 'size',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 219,
            'endLine' => 219,
            'startColumn' => 25,
            'endColumn' => 29,
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
 * Set the maximum size of the password.
 *
 * @param  int  $size
 * @return $this
 */',
        'startLine' => 219,
        'endLine' => 224,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'aliasName' => NULL,
      ),
      'uncompromised' => 
      array (
        'name' => 'uncompromised',
        'parameters' => 
        array (
          'threshold' => 
          array (
            'name' => 'threshold',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 232,
                'endLine' => 232,
                'startTokenPos' => 578,
                'startFilePos' => 5172,
                'endTokenPos' => 578,
                'endFilePos' => 5172,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 232,
            'endLine' => 232,
            'startColumn' => 35,
            'endColumn' => 48,
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
 * Ensures the password has not been compromised in data leaks.
 *
 * @param  int  $threshold
 * @return $this
 */',
        'startLine' => 232,
        'endLine' => 239,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'aliasName' => NULL,
      ),
      'mixedCase' => 
      array (
        'name' => 'mixedCase',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Makes the password require at least one uppercase and one lowercase letter.
 *
 * @return $this
 */',
        'startLine' => 246,
        'endLine' => 251,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'aliasName' => NULL,
      ),
      'letters' => 
      array (
        'name' => 'letters',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Makes the password require at least one letter.
 *
 * @return $this
 */',
        'startLine' => 258,
        'endLine' => 263,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'aliasName' => NULL,
      ),
      'numbers' => 
      array (
        'name' => 'numbers',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Makes the password require at least one number.
 *
 * @return $this
 */',
        'startLine' => 270,
        'endLine' => 275,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'aliasName' => NULL,
      ),
      'symbols' => 
      array (
        'name' => 'symbols',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Makes the password require at least one symbol.
 *
 * @return $this
 */',
        'startLine' => 282,
        'endLine' => 287,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'aliasName' => NULL,
      ),
      'rules' => 
      array (
        'name' => 'rules',
        'parameters' => 
        array (
          'rules' => 
          array (
            'name' => 'rules',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 295,
            'endLine' => 295,
            'startColumn' => 27,
            'endColumn' => 32,
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
 * Specify additional validation rules that should be merged with the default rules during validation.
 *
 * @param  \\Closure|string|array  $rules
 * @return $this
 */',
        'startLine' => 295,
        'endLine' => 300,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'aliasName' => NULL,
      ),
      'passes' => 
      array (
        'name' => 'passes',
        'parameters' => 
        array (
          'attribute' => 
          array (
            'name' => 'attribute',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 309,
            'endLine' => 309,
            'startColumn' => 28,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 309,
            'endLine' => 309,
            'startColumn' => 40,
            'endColumn' => 45,
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
 * Determine if the validation rule passes.
 *
 * @param  string  $attribute
 * @param  mixed  $value
 * @return bool
 */',
        'startLine' => 309,
        'endLine' => 359,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'aliasName' => NULL,
      ),
      'message' => 
      array (
        'name' => 'message',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the validation error message.
 *
 * @return array
 */',
        'startLine' => 366,
        'endLine' => 369,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'aliasName' => NULL,
      ),
      'fail' => 
      array (
        'name' => 'fail',
        'parameters' => 
        array (
          'messages' => 
          array (
            'name' => 'messages',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 377,
            'endLine' => 377,
            'startColumn' => 29,
            'endColumn' => 37,
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
 * Adds the given failures, and return false.
 *
 * @param  array|string  $messages
 * @return bool
 */',
        'startLine' => 377,
        'endLine' => 382,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\Password',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\Password',
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