<?php declare(strict_types = 1);

// osfsl-C:/OPTIZAWORKS/igoutech/backend/vendor/composer/../laravel/framework/src/Illuminate/Queue/Console/MonitorCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Queue\Console\MonitorCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-36d8d720cd816a818374a8e7cb3d6a1a90ac91015fbe63e84c22acd41b88d82b-8.3.22-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Queue\\Console\\MonitorCommand',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/vendor/composer/../laravel/framework/src/Illuminate/Queue/Console/MonitorCommand.php',
      ),
    ),
    'namespace' => 'Illuminate\\Queue\\Console',
    'name' => 'Illuminate\\Queue\\Console\\MonitorCommand',
    'shortName' => 'MonitorCommand',
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
            'code' => '\'queue:monitor\'',
            'attributes' => 
            array (
              'startLine' => 12,
              'endLine' => 12,
              'startTokenPos' => 43,
              'startFilePos' => 304,
              'endTokenPos' => 43,
              'endFilePos' => 318,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 12,
    'endLine' => 143,
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
        'declaringClassName' => 'Illuminate\\Queue\\Console\\MonitorCommand',
        'implementingClassName' => 'Illuminate\\Queue\\Console\\MonitorCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'queue:monitor
                       {queues : The names of the queues to monitor}
                       {--max=1000 : The maximum number of jobs that can be on the queue before an event is dispatched}\'',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 22,
            'startTokenPos' => 65,
            'startFilePos' => 463,
            'endTokenPos' => 65,
            'endFilePos' => 666,
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
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 121,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Illuminate\\Queue\\Console\\MonitorCommand',
        'implementingClassName' => 'Illuminate\\Queue\\Console\\MonitorCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Monitor the size of the specified queues\'',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 29,
            'startTokenPos' => 76,
            'startFilePos' => 781,
            'endTokenPos' => 76,
            'endFilePos' => 822,
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
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 72,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'manager' => 
      array (
        'declaringClassName' => 'Illuminate\\Queue\\Console\\MonitorCommand',
        'implementingClassName' => 'Illuminate\\Queue\\Console\\MonitorCommand',
        'name' => 'manager',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The queue manager instance.
 *
 * @var \\Illuminate\\Contracts\\Queue\\Factory
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 23,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'events' => 
      array (
        'declaringClassName' => 'Illuminate\\Queue\\Console\\MonitorCommand',
        'implementingClassName' => 'Illuminate\\Queue\\Console\\MonitorCommand',
        'name' => 'events',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The events dispatcher instance.
 *
 * @var \\Illuminate\\Contracts\\Events\\Dispatcher
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 43,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 22,
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
          'manager' => 
          array (
            'name' => 'manager',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Contracts\\Queue\\Factory',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 33,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'events' => 
          array (
            'name' => 'events',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Contracts\\Events\\Dispatcher',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 51,
            'endColumn' => 68,
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
 * Create a new queue monitor command.
 *
 * @param  \\Illuminate\\Contracts\\Queue\\Factory  $manager
 * @param  \\Illuminate\\Contracts\\Events\\Dispatcher  $events
 * @return void
 */',
        'startLine' => 52,
        'endLine' => 58,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Queue\\Console',
        'declaringClassName' => 'Illuminate\\Queue\\Console\\MonitorCommand',
        'implementingClassName' => 'Illuminate\\Queue\\Console\\MonitorCommand',
        'currentClassName' => 'Illuminate\\Queue\\Console\\MonitorCommand',
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
        'startLine' => 65,
        'endLine' => 72,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Queue\\Console',
        'declaringClassName' => 'Illuminate\\Queue\\Console\\MonitorCommand',
        'implementingClassName' => 'Illuminate\\Queue\\Console\\MonitorCommand',
        'currentClassName' => 'Illuminate\\Queue\\Console\\MonitorCommand',
        'aliasName' => NULL,
      ),
      'parseQueues' => 
      array (
        'name' => 'parseQueues',
        'parameters' => 
        array (
          'queues' => 
          array (
            'name' => 'queues',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 80,
            'endLine' => 80,
            'startColumn' => 36,
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
 * Parse the queues into an array of the connections and queues.
 *
 * @param  string  $queues
 * @return \\Illuminate\\Support\\Collection
 */',
        'startLine' => 80,
        'endLine' => 97,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Queue\\Console',
        'declaringClassName' => 'Illuminate\\Queue\\Console\\MonitorCommand',
        'implementingClassName' => 'Illuminate\\Queue\\Console\\MonitorCommand',
        'currentClassName' => 'Illuminate\\Queue\\Console\\MonitorCommand',
        'aliasName' => NULL,
      ),
      'displaySizes' => 
      array (
        'name' => 'displaySizes',
        'parameters' => 
        array (
          'queues' => 
          array (
            'name' => 'queues',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Support\\Collection',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 105,
            'endLine' => 105,
            'startColumn' => 37,
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
 * Display the queue sizes in the console.
 *
 * @param  \\Illuminate\\Support\\Collection  $queues
 * @return void
 */',
        'startLine' => 105,
        'endLine' => 119,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Queue\\Console',
        'declaringClassName' => 'Illuminate\\Queue\\Console\\MonitorCommand',
        'implementingClassName' => 'Illuminate\\Queue\\Console\\MonitorCommand',
        'currentClassName' => 'Illuminate\\Queue\\Console\\MonitorCommand',
        'aliasName' => NULL,
      ),
      'dispatchEvents' => 
      array (
        'name' => 'dispatchEvents',
        'parameters' => 
        array (
          'queues' => 
          array (
            'name' => 'queues',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Support\\Collection',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 127,
            'endLine' => 127,
            'startColumn' => 39,
            'endColumn' => 56,
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
 * Fire the monitoring events.
 *
 * @param  \\Illuminate\\Support\\Collection  $queues
 * @return void
 */',
        'startLine' => 127,
        'endLine' => 142,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Queue\\Console',
        'declaringClassName' => 'Illuminate\\Queue\\Console\\MonitorCommand',
        'implementingClassName' => 'Illuminate\\Queue\\Console\\MonitorCommand',
        'currentClassName' => 'Illuminate\\Queue\\Console\\MonitorCommand',
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