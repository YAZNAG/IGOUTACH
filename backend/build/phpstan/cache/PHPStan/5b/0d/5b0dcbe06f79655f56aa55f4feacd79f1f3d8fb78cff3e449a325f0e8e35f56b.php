<?php declare(strict_types = 1);

// odsl-C:/OPTIZAWORKS/igoutech/backend/vendor/composer/../laravel/framework/src/Illuminate/Foundation/helpers.php-PHPStan\BetterReflection\Reflection\ReflectionFunction-abort_unless
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-c7ab6eec6e269703a41e227ae0c03fc6308138b1fdbcfb27b1678840e87ad0c1',
   'data' => 
  array (
    'name' => 'abort_unless',
    'parameters' => 
    array (
      'boolean' => 
      array (
        'name' => 'boolean',
        'default' => NULL,
        'type' => NULL,
        'isVariadic' => false,
        'byRef' => false,
        'isPromoted' => false,
        'attributes' => 
        array (
        ),
        'startLine' => 85,
        'endLine' => 85,
        'startColumn' => 27,
        'endColumn' => 34,
        'parameterIndex' => 0,
        'isOptional' => false,
      ),
      'code' => 
      array (
        'name' => 'code',
        'default' => NULL,
        'type' => NULL,
        'isVariadic' => false,
        'byRef' => false,
        'isPromoted' => false,
        'attributes' => 
        array (
        ),
        'startLine' => 85,
        'endLine' => 85,
        'startColumn' => 37,
        'endColumn' => 41,
        'parameterIndex' => 1,
        'isOptional' => false,
      ),
      'message' => 
      array (
        'name' => 'message',
        'default' => 
        array (
          'code' => '\'\'',
          'attributes' => 
          array (
            'startLine' => 85,
            'endLine' => 85,
            'startTokenPos' => 351,
            'startFilePos' => 3201,
            'endTokenPos' => 351,
            'endFilePos' => 3202,
          ),
        ),
        'type' => NULL,
        'isVariadic' => false,
        'byRef' => false,
        'isPromoted' => false,
        'attributes' => 
        array (
        ),
        'startLine' => 85,
        'endLine' => 85,
        'startColumn' => 44,
        'endColumn' => 56,
        'parameterIndex' => 2,
        'isOptional' => true,
      ),
      'headers' => 
      array (
        'name' => 'headers',
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 85,
            'endLine' => 85,
            'startTokenPos' => 360,
            'startFilePos' => 3222,
            'endTokenPos' => 361,
            'endFilePos' => 3223,
          ),
        ),
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
        'startLine' => 85,
        'endLine' => 85,
        'startColumn' => 59,
        'endColumn' => 77,
        'parameterIndex' => 3,
        'isOptional' => true,
      ),
    ),
    'returnsReference' => false,
    'returnType' => NULL,
    'attributes' => 
    array (
    ),
    'docComment' => '/**
 * Throw an HttpException with the given data unless the given condition is true.
 *
 * @param  bool  $boolean
 * @param  \\Symfony\\Component\\HttpFoundation\\Response|\\Illuminate\\Contracts\\Support\\Responsable|int  $code
 * @param  string  $message
 * @param  array  $headers
 * @return void
 *
 * @throws \\Symfony\\Component\\HttpKernel\\Exception\\HttpException
 * @throws \\Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException
 */',
    'startLine' => 85,
    'endLine' => 90,
    'startColumn' => 5,
    'endColumn' => 5,
    'couldThrow' => false,
    'isClosure' => false,
    'isGenerator' => false,
    'isVariadic' => false,
    'isStatic' => false,
    'namespace' => NULL,
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'abort_unless',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/vendor/composer/../laravel/framework/src/Illuminate/Foundation/helpers.php',
      ),
    ),
  ),
));