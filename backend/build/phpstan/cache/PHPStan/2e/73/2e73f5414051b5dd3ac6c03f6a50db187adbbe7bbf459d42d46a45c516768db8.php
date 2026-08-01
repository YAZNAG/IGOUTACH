<?php declare(strict_types = 1);

// osfsl-C:/OPTIZAWORKS/igoutech/backend/vendor/composer/../maatwebsite/excel/src/RegistersCustomConcerns.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Maatwebsite\Excel\RegistersCustomConcerns
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-b19ef79fd81d104b31fe3285a91ff3cd1bb6ede0c8117f381e61b171318da51b-8.3.22-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Maatwebsite\\Excel\\RegistersCustomConcerns',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/vendor/composer/../maatwebsite/excel/src/RegistersCustomConcerns.php',
      ),
    ),
    'namespace' => 'Maatwebsite\\Excel',
    'name' => 'Maatwebsite\\Excel\\RegistersCustomConcerns',
    'shortName' => 'RegistersCustomConcerns',
    'isInterface' => false,
    'isTrait' => true,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 11,
    'endLine' => 39,
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
      'eventMap' => 
      array (
        'declaringClassName' => 'Maatwebsite\\Excel\\RegistersCustomConcerns',
        'implementingClassName' => 'Maatwebsite\\Excel\\RegistersCustomConcerns',
        'name' => 'eventMap',
        'modifiers' => 20,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\\Maatwebsite\\Excel\\Events\\BeforeWriting::class => \\Maatwebsite\\Excel\\Writer::class, \\Maatwebsite\\Excel\\Events\\BeforeExport::class => \\Maatwebsite\\Excel\\Writer::class, \\Maatwebsite\\Excel\\Events\\BeforeSheet::class => \\Maatwebsite\\Excel\\Sheet::class, \\Maatwebsite\\Excel\\Events\\AfterSheet::class => \\Maatwebsite\\Excel\\Sheet::class]',
          'attributes' => 
          array (
            'startLine' => 16,
            'endLine' => 21,
            'startTokenPos' => 48,
            'startFilePos' => 341,
            'endTokenPos' => 94,
            'endFilePos' => 533,
          ),
        ),
        'docComment' => '/**
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 16,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 6,
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
      'extend' => 
      array (
        'name' => 'extend',
        'parameters' => 
        array (
          'concern' => 
          array (
            'name' => 'concern',
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
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 35,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'handler' => 
          array (
            'name' => 'handler',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 52,
            'endColumn' => 68,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'event' => 
          array (
            'name' => 'event',
            'default' => 
            array (
              'code' => '\\Maatwebsite\\Excel\\Events\\BeforeWriting::class',
              'attributes' => 
              array (
                'startLine' => 28,
                'endLine' => 28,
                'startTokenPos' => 123,
                'startFilePos' => 735,
                'endTokenPos' => 125,
                'endFilePos' => 754,
              ),
            ),
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
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 71,
            'endColumn' => 106,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  string  $concern
 * @param  callable  $handler
 * @param  string  $event
 */',
        'startLine' => 28,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Maatwebsite\\Excel',
        'declaringClassName' => 'Maatwebsite\\Excel\\RegistersCustomConcerns',
        'implementingClassName' => 'Maatwebsite\\Excel\\RegistersCustomConcerns',
        'currentClassName' => 'Maatwebsite\\Excel\\RegistersCustomConcerns',
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