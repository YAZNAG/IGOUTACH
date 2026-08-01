<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Access\Models\AuditLog.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Domain\Access\Models\AuditLog
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.3.22-d2d9b759aa3571a505b98e386277768b997a0595834cd95dcd29493924d0ceae',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Domain\\Access\\Models\\AuditLog',
        'filename' => 'C:/OPTIZAWORKS/igoutech/backend/app/Domain/Access/Models/AuditLog.php',
      ),
    ),
    'namespace' => 'App\\Domain\\Access\\Models',
    'name' => 'App\\Domain\\Access\\Models\\AuditLog',
    'shortName' => 'AuditLog',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Entrée du journal d\'audit (append-only : ni update, ni delete).
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $module
 * @property string|null $entity_type
 * @property int|null $entity_id
 * @property string|null $description
 * @property array<string, mixed>|null $changes
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 * @property-read User|null $user
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 28,
    'endLine' => 63,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'UPDATED_AT' => 
      array (
        'declaringClassName' => 'App\\Domain\\Access\\Models\\AuditLog',
        'implementingClassName' => 'App\\Domain\\Access\\Models\\AuditLog',
        'name' => 'UPDATED_AT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 31,
            'endLine' => 31,
            'startTokenPos' => 59,
            'startFilePos' => 838,
            'endTokenPos' => 59,
            'endFilePos' => 841,
          ),
        ),
        'docComment' => '/** Journal append-only : pas de colonne updated_at. */',
        'attributes' => 
        array (
        ),
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
    ),
    'immediateProperties' => 
    array (
      'fillable' => 
      array (
        'declaringClassName' => 'App\\Domain\\Access\\Models\\AuditLog',
        'implementingClassName' => 'App\\Domain\\Access\\Models\\AuditLog',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'user_id\', \'action\', \'module\', \'entity_type\', \'entity_id\', \'description\', \'changes\', \'ip_address\', \'user_agent\']',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 43,
            'startTokenPos' => 68,
            'startFilePos' => 871,
            'endTokenPos' => 97,
            'endFilePos' => 1062,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 43,
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
      'user' => 
      array (
        'name' => 'user',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return BelongsTo<User, $this>
 */',
        'startLine' => 48,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Domain\\Access\\Models',
        'declaringClassName' => 'App\\Domain\\Access\\Models\\AuditLog',
        'implementingClassName' => 'App\\Domain\\Access\\Models\\AuditLog',
        'currentClassName' => 'App\\Domain\\Access\\Models\\AuditLog',
        'aliasName' => NULL,
      ),
      'casts' => 
      array (
        'name' => 'casts',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return array<string, string>
 */',
        'startLine' => 56,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Domain\\Access\\Models',
        'declaringClassName' => 'App\\Domain\\Access\\Models\\AuditLog',
        'implementingClassName' => 'App\\Domain\\Access\\Models\\AuditLog',
        'currentClassName' => 'App\\Domain\\Access\\Models\\AuditLog',
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