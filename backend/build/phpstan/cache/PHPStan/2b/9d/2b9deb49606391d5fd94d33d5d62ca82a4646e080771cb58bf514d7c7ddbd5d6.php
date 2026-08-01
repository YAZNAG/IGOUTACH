<?php declare(strict_types = 1);

// ftm-C:\OPTIZAWORKS\igoutech\backend\app\Domain\Stock\Actions\CreateTransferAction.php
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v5-2.3.2',
   'data' => 
  array (
    0 => 
    array (
      '9339f38abbadb53d2776f5956f65b112' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Domain\\Stock\\Actions',
         'uses' => 
        array (
          'stockwriterinterface' => 'App\\Domain\\Stock\\Contracts\\StockWriterInterface',
          'stockmovementdata' => 'App\\Domain\\Stock\\DTOs\\StockMovementData',
          'transferdata' => 'App\\Domain\\Stock\\DTOs\\TransferData',
          'invalidtransferexception' => 'App\\Domain\\Stock\\Exceptions\\InvalidTransferException',
          'movementtype' => 'App\\Domain\\Stock\\Models\\MovementType',
          'transfer' => 'App\\Domain\\Stock\\Models\\Transfer',
          'transferstatus' => 'App\\Domain\\Stock\\Models\\TransferStatus',
          'documentnumbergeneratorinterface' => 'App\\Support\\Documents\\DocumentNumberGeneratorInterface',
          'db' => 'Illuminate\\Support\\Facades\\DB',
        ),
         'className' => 'App\\Domain\\Stock\\Actions\\CreateTransferAction',
         'functionName' => NULL,
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => NULL,
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'b6c0b4a23f8c0732bcd52e7548efbcdd' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Domain\\Stock\\Actions',
         'uses' => 
        array (
          'stockwriterinterface' => 'App\\Domain\\Stock\\Contracts\\StockWriterInterface',
          'stockmovementdata' => 'App\\Domain\\Stock\\DTOs\\StockMovementData',
          'transferdata' => 'App\\Domain\\Stock\\DTOs\\TransferData',
          'invalidtransferexception' => 'App\\Domain\\Stock\\Exceptions\\InvalidTransferException',
          'movementtype' => 'App\\Domain\\Stock\\Models\\MovementType',
          'transfer' => 'App\\Domain\\Stock\\Models\\Transfer',
          'transferstatus' => 'App\\Domain\\Stock\\Models\\TransferStatus',
          'documentnumbergeneratorinterface' => 'App\\Support\\Documents\\DocumentNumberGeneratorInterface',
          'db' => 'Illuminate\\Support\\Facades\\DB',
        ),
         'className' => 'App\\Domain\\Stock\\Actions\\CreateTransferAction',
         'functionName' => '__construct',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Domain\\Stock\\Actions',
           'uses' => 
          array (
            'stockwriterinterface' => 'App\\Domain\\Stock\\Contracts\\StockWriterInterface',
            'stockmovementdata' => 'App\\Domain\\Stock\\DTOs\\StockMovementData',
            'transferdata' => 'App\\Domain\\Stock\\DTOs\\TransferData',
            'invalidtransferexception' => 'App\\Domain\\Stock\\Exceptions\\InvalidTransferException',
            'movementtype' => 'App\\Domain\\Stock\\Models\\MovementType',
            'transfer' => 'App\\Domain\\Stock\\Models\\Transfer',
            'transferstatus' => 'App\\Domain\\Stock\\Models\\TransferStatus',
            'documentnumbergeneratorinterface' => 'App\\Support\\Documents\\DocumentNumberGeneratorInterface',
            'db' => 'Illuminate\\Support\\Facades\\DB',
          ),
           'className' => 'App\\Domain\\Stock\\Actions\\CreateTransferAction',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '7d228c86f8e68cd143a1ad648a84c055' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Domain\\Stock\\Actions',
         'uses' => 
        array (
          'stockwriterinterface' => 'App\\Domain\\Stock\\Contracts\\StockWriterInterface',
          'stockmovementdata' => 'App\\Domain\\Stock\\DTOs\\StockMovementData',
          'transferdata' => 'App\\Domain\\Stock\\DTOs\\TransferData',
          'invalidtransferexception' => 'App\\Domain\\Stock\\Exceptions\\InvalidTransferException',
          'movementtype' => 'App\\Domain\\Stock\\Models\\MovementType',
          'transfer' => 'App\\Domain\\Stock\\Models\\Transfer',
          'transferstatus' => 'App\\Domain\\Stock\\Models\\TransferStatus',
          'documentnumbergeneratorinterface' => 'App\\Support\\Documents\\DocumentNumberGeneratorInterface',
          'db' => 'Illuminate\\Support\\Facades\\DB',
        ),
         'className' => 'App\\Domain\\Stock\\Actions\\CreateTransferAction',
         'functionName' => 'execute',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Domain\\Stock\\Actions',
           'uses' => 
          array (
            'stockwriterinterface' => 'App\\Domain\\Stock\\Contracts\\StockWriterInterface',
            'stockmovementdata' => 'App\\Domain\\Stock\\DTOs\\StockMovementData',
            'transferdata' => 'App\\Domain\\Stock\\DTOs\\TransferData',
            'invalidtransferexception' => 'App\\Domain\\Stock\\Exceptions\\InvalidTransferException',
            'movementtype' => 'App\\Domain\\Stock\\Models\\MovementType',
            'transfer' => 'App\\Domain\\Stock\\Models\\Transfer',
            'transferstatus' => 'App\\Domain\\Stock\\Models\\TransferStatus',
            'documentnumbergeneratorinterface' => 'App\\Support\\Documents\\DocumentNumberGeneratorInterface',
            'db' => 'Illuminate\\Support\\Facades\\DB',
          ),
           'className' => 'App\\Domain\\Stock\\Actions\\CreateTransferAction',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
    ),
    1 => 
    array (
      'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Actions\\CreateTransferAction.php' => 'aaec7f34bfc15073f862b22f5ad2c6969fe3849c8dce67902d3829fd6a902cc2',
    ),
  ),
));