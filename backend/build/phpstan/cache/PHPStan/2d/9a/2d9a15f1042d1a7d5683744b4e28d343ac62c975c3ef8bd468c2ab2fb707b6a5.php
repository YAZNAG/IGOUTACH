<?php declare(strict_types = 1);

// ftm-C:\OPTIZAWORKS\igoutech\backend\app\Http\Controllers\Api\V1\StockController.php
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v5-2.3.2',
   'data' => 
  array (
    0 => 
    array (
      'dd7c33462555133d65f0a8d4982038b3' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Api\\V1',
         'uses' => 
        array (
          'product' => 'App\\Domain\\Catalog\\Models\\Product',
          'entrystockaction' => 'App\\Domain\\Stock\\Actions\\EntryStockAction',
          'issuestockaction' => 'App\\Domain\\Stock\\Actions\\IssueStockAction',
          'stockwriterinterface' => 'App\\Domain\\Stock\\Contracts\\StockWriterInterface',
          'stockmovementdata' => 'App\\Domain\\Stock\\DTOs\\StockMovementData',
          'insufficientstockexception' => 'App\\Domain\\Stock\\Exceptions\\InsufficientStockException',
          'movementtype' => 'App\\Domain\\Stock\\Models\\MovementType',
          'stock' => 'App\\Domain\\Stock\\Models\\Stock',
          'stockmovement' => 'App\\Domain\\Stock\\Models\\StockMovement',
          'warehouse' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
          'arrayexport' => 'App\\Exports\\ArrayExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'stockentryrequest' => 'App\\Http\\Requests\\StockEntryRequest',
          'stockissuerequest' => 'App\\Http\\Requests\\StockIssueRequest',
          'user' => 'App\\Models\\User',
          'htmltable' => 'App\\Support\\Export\\HtmlTable',
          'pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
          'httpresponse' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'className' => 'App\\Http\\Controllers\\Api\\V1\\StockController',
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
      '63416bda7c454b3dfa0caf9ab0eba26e' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Api\\V1',
         'uses' => 
        array (
          'product' => 'App\\Domain\\Catalog\\Models\\Product',
          'entrystockaction' => 'App\\Domain\\Stock\\Actions\\EntryStockAction',
          'issuestockaction' => 'App\\Domain\\Stock\\Actions\\IssueStockAction',
          'stockwriterinterface' => 'App\\Domain\\Stock\\Contracts\\StockWriterInterface',
          'stockmovementdata' => 'App\\Domain\\Stock\\DTOs\\StockMovementData',
          'insufficientstockexception' => 'App\\Domain\\Stock\\Exceptions\\InsufficientStockException',
          'movementtype' => 'App\\Domain\\Stock\\Models\\MovementType',
          'stock' => 'App\\Domain\\Stock\\Models\\Stock',
          'stockmovement' => 'App\\Domain\\Stock\\Models\\StockMovement',
          'warehouse' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
          'arrayexport' => 'App\\Exports\\ArrayExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'stockentryrequest' => 'App\\Http\\Requests\\StockEntryRequest',
          'stockissuerequest' => 'App\\Http\\Requests\\StockIssueRequest',
          'user' => 'App\\Models\\User',
          'htmltable' => 'App\\Support\\Export\\HtmlTable',
          'pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
          'httpresponse' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'className' => 'App\\Http\\Controllers\\Api\\V1\\StockController',
         'functionName' => 'perPage',
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
      '1339f4a1d5a6b98e1ba7b95182371ece' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Api\\V1',
         'uses' => 
        array (
          'product' => 'App\\Domain\\Catalog\\Models\\Product',
          'entrystockaction' => 'App\\Domain\\Stock\\Actions\\EntryStockAction',
          'issuestockaction' => 'App\\Domain\\Stock\\Actions\\IssueStockAction',
          'stockwriterinterface' => 'App\\Domain\\Stock\\Contracts\\StockWriterInterface',
          'stockmovementdata' => 'App\\Domain\\Stock\\DTOs\\StockMovementData',
          'insufficientstockexception' => 'App\\Domain\\Stock\\Exceptions\\InsufficientStockException',
          'movementtype' => 'App\\Domain\\Stock\\Models\\MovementType',
          'stock' => 'App\\Domain\\Stock\\Models\\Stock',
          'stockmovement' => 'App\\Domain\\Stock\\Models\\StockMovement',
          'warehouse' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
          'arrayexport' => 'App\\Exports\\ArrayExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'stockentryrequest' => 'App\\Http\\Requests\\StockEntryRequest',
          'stockissuerequest' => 'App\\Http\\Requests\\StockIssueRequest',
          'user' => 'App\\Models\\User',
          'htmltable' => 'App\\Support\\Export\\HtmlTable',
          'pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
          'httpresponse' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'className' => 'App\\Http\\Controllers\\Api\\V1\\StockController',
         'functionName' => 'index',
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
      '3708ac1760697daf1c39cd41326a99b4' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Api\\V1',
         'uses' => 
        array (
          'product' => 'App\\Domain\\Catalog\\Models\\Product',
          'entrystockaction' => 'App\\Domain\\Stock\\Actions\\EntryStockAction',
          'issuestockaction' => 'App\\Domain\\Stock\\Actions\\IssueStockAction',
          'stockwriterinterface' => 'App\\Domain\\Stock\\Contracts\\StockWriterInterface',
          'stockmovementdata' => 'App\\Domain\\Stock\\DTOs\\StockMovementData',
          'insufficientstockexception' => 'App\\Domain\\Stock\\Exceptions\\InsufficientStockException',
          'movementtype' => 'App\\Domain\\Stock\\Models\\MovementType',
          'stock' => 'App\\Domain\\Stock\\Models\\Stock',
          'stockmovement' => 'App\\Domain\\Stock\\Models\\StockMovement',
          'warehouse' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
          'arrayexport' => 'App\\Exports\\ArrayExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'stockentryrequest' => 'App\\Http\\Requests\\StockEntryRequest',
          'stockissuerequest' => 'App\\Http\\Requests\\StockIssueRequest',
          'user' => 'App\\Models\\User',
          'htmltable' => 'App\\Support\\Export\\HtmlTable',
          'pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
          'httpresponse' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'className' => 'App\\Http\\Controllers\\Api\\V1\\StockController',
         'functionName' => 'movements',
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
      '10fa243c2a230603036817c750b1d506' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Api\\V1',
         'uses' => 
        array (
          'product' => 'App\\Domain\\Catalog\\Models\\Product',
          'entrystockaction' => 'App\\Domain\\Stock\\Actions\\EntryStockAction',
          'issuestockaction' => 'App\\Domain\\Stock\\Actions\\IssueStockAction',
          'stockwriterinterface' => 'App\\Domain\\Stock\\Contracts\\StockWriterInterface',
          'stockmovementdata' => 'App\\Domain\\Stock\\DTOs\\StockMovementData',
          'insufficientstockexception' => 'App\\Domain\\Stock\\Exceptions\\InsufficientStockException',
          'movementtype' => 'App\\Domain\\Stock\\Models\\MovementType',
          'stock' => 'App\\Domain\\Stock\\Models\\Stock',
          'stockmovement' => 'App\\Domain\\Stock\\Models\\StockMovement',
          'warehouse' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
          'arrayexport' => 'App\\Exports\\ArrayExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'stockentryrequest' => 'App\\Http\\Requests\\StockEntryRequest',
          'stockissuerequest' => 'App\\Http\\Requests\\StockIssueRequest',
          'user' => 'App\\Models\\User',
          'htmltable' => 'App\\Support\\Export\\HtmlTable',
          'pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
          'httpresponse' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'className' => 'App\\Http\\Controllers\\Api\\V1\\StockController',
         'functionName' => 'movementTypes',
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
      '0851ff1b111d6ad8ea99830daa0bca14' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Api\\V1',
         'uses' => 
        array (
          'product' => 'App\\Domain\\Catalog\\Models\\Product',
          'entrystockaction' => 'App\\Domain\\Stock\\Actions\\EntryStockAction',
          'issuestockaction' => 'App\\Domain\\Stock\\Actions\\IssueStockAction',
          'stockwriterinterface' => 'App\\Domain\\Stock\\Contracts\\StockWriterInterface',
          'stockmovementdata' => 'App\\Domain\\Stock\\DTOs\\StockMovementData',
          'insufficientstockexception' => 'App\\Domain\\Stock\\Exceptions\\InsufficientStockException',
          'movementtype' => 'App\\Domain\\Stock\\Models\\MovementType',
          'stock' => 'App\\Domain\\Stock\\Models\\Stock',
          'stockmovement' => 'App\\Domain\\Stock\\Models\\StockMovement',
          'warehouse' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
          'arrayexport' => 'App\\Exports\\ArrayExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'stockentryrequest' => 'App\\Http\\Requests\\StockEntryRequest',
          'stockissuerequest' => 'App\\Http\\Requests\\StockIssueRequest',
          'user' => 'App\\Models\\User',
          'htmltable' => 'App\\Support\\Export\\HtmlTable',
          'pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
          'httpresponse' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'className' => 'App\\Http\\Controllers\\Api\\V1\\StockController',
         'functionName' => 'export',
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
      '2d47fc24540219423971b789afb072a2' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Api\\V1',
         'uses' => 
        array (
          'product' => 'App\\Domain\\Catalog\\Models\\Product',
          'entrystockaction' => 'App\\Domain\\Stock\\Actions\\EntryStockAction',
          'issuestockaction' => 'App\\Domain\\Stock\\Actions\\IssueStockAction',
          'stockwriterinterface' => 'App\\Domain\\Stock\\Contracts\\StockWriterInterface',
          'stockmovementdata' => 'App\\Domain\\Stock\\DTOs\\StockMovementData',
          'insufficientstockexception' => 'App\\Domain\\Stock\\Exceptions\\InsufficientStockException',
          'movementtype' => 'App\\Domain\\Stock\\Models\\MovementType',
          'stock' => 'App\\Domain\\Stock\\Models\\Stock',
          'stockmovement' => 'App\\Domain\\Stock\\Models\\StockMovement',
          'warehouse' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
          'arrayexport' => 'App\\Exports\\ArrayExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'stockentryrequest' => 'App\\Http\\Requests\\StockEntryRequest',
          'stockissuerequest' => 'App\\Http\\Requests\\StockIssueRequest',
          'user' => 'App\\Models\\User',
          'htmltable' => 'App\\Support\\Export\\HtmlTable',
          'pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
          'httpresponse' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'className' => 'App\\Http\\Controllers\\Api\\V1\\StockController',
         'functionName' => 'matrix',
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
      'bb061ce8eadccbb80936abc6741f2b63' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Api\\V1',
         'uses' => 
        array (
          'product' => 'App\\Domain\\Catalog\\Models\\Product',
          'entrystockaction' => 'App\\Domain\\Stock\\Actions\\EntryStockAction',
          'issuestockaction' => 'App\\Domain\\Stock\\Actions\\IssueStockAction',
          'stockwriterinterface' => 'App\\Domain\\Stock\\Contracts\\StockWriterInterface',
          'stockmovementdata' => 'App\\Domain\\Stock\\DTOs\\StockMovementData',
          'insufficientstockexception' => 'App\\Domain\\Stock\\Exceptions\\InsufficientStockException',
          'movementtype' => 'App\\Domain\\Stock\\Models\\MovementType',
          'stock' => 'App\\Domain\\Stock\\Models\\Stock',
          'stockmovement' => 'App\\Domain\\Stock\\Models\\StockMovement',
          'warehouse' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
          'arrayexport' => 'App\\Exports\\ArrayExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'stockentryrequest' => 'App\\Http\\Requests\\StockEntryRequest',
          'stockissuerequest' => 'App\\Http\\Requests\\StockIssueRequest',
          'user' => 'App\\Models\\User',
          'htmltable' => 'App\\Support\\Export\\HtmlTable',
          'pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
          'httpresponse' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'className' => 'App\\Http\\Controllers\\Api\\V1\\StockController',
         'functionName' => 'matrixExport',
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
      'e4335194ccb3cb1ca36731544de5fb02' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Api\\V1',
         'uses' => 
        array (
          'product' => 'App\\Domain\\Catalog\\Models\\Product',
          'entrystockaction' => 'App\\Domain\\Stock\\Actions\\EntryStockAction',
          'issuestockaction' => 'App\\Domain\\Stock\\Actions\\IssueStockAction',
          'stockwriterinterface' => 'App\\Domain\\Stock\\Contracts\\StockWriterInterface',
          'stockmovementdata' => 'App\\Domain\\Stock\\DTOs\\StockMovementData',
          'insufficientstockexception' => 'App\\Domain\\Stock\\Exceptions\\InsufficientStockException',
          'movementtype' => 'App\\Domain\\Stock\\Models\\MovementType',
          'stock' => 'App\\Domain\\Stock\\Models\\Stock',
          'stockmovement' => 'App\\Domain\\Stock\\Models\\StockMovement',
          'warehouse' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
          'arrayexport' => 'App\\Exports\\ArrayExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'stockentryrequest' => 'App\\Http\\Requests\\StockEntryRequest',
          'stockissuerequest' => 'App\\Http\\Requests\\StockIssueRequest',
          'user' => 'App\\Models\\User',
          'htmltable' => 'App\\Support\\Export\\HtmlTable',
          'pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
          'httpresponse' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'className' => 'App\\Http\\Controllers\\Api\\V1\\StockController',
         'functionName' => 'quantitiesByProductAndWarehouse',
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
      '7e91895a6fd91ca2146d37751a6daa78' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Api\\V1',
         'uses' => 
        array (
          'product' => 'App\\Domain\\Catalog\\Models\\Product',
          'entrystockaction' => 'App\\Domain\\Stock\\Actions\\EntryStockAction',
          'issuestockaction' => 'App\\Domain\\Stock\\Actions\\IssueStockAction',
          'stockwriterinterface' => 'App\\Domain\\Stock\\Contracts\\StockWriterInterface',
          'stockmovementdata' => 'App\\Domain\\Stock\\DTOs\\StockMovementData',
          'insufficientstockexception' => 'App\\Domain\\Stock\\Exceptions\\InsufficientStockException',
          'movementtype' => 'App\\Domain\\Stock\\Models\\MovementType',
          'stock' => 'App\\Domain\\Stock\\Models\\Stock',
          'stockmovement' => 'App\\Domain\\Stock\\Models\\StockMovement',
          'warehouse' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
          'arrayexport' => 'App\\Exports\\ArrayExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'stockentryrequest' => 'App\\Http\\Requests\\StockEntryRequest',
          'stockissuerequest' => 'App\\Http\\Requests\\StockIssueRequest',
          'user' => 'App\\Models\\User',
          'htmltable' => 'App\\Support\\Export\\HtmlTable',
          'pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
          'httpresponse' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'className' => 'App\\Http\\Controllers\\Api\\V1\\StockController',
         'functionName' => 'entry',
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
      'd1b344d51779429c57322811cbba0b1a' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Api\\V1',
         'uses' => 
        array (
          'product' => 'App\\Domain\\Catalog\\Models\\Product',
          'entrystockaction' => 'App\\Domain\\Stock\\Actions\\EntryStockAction',
          'issuestockaction' => 'App\\Domain\\Stock\\Actions\\IssueStockAction',
          'stockwriterinterface' => 'App\\Domain\\Stock\\Contracts\\StockWriterInterface',
          'stockmovementdata' => 'App\\Domain\\Stock\\DTOs\\StockMovementData',
          'insufficientstockexception' => 'App\\Domain\\Stock\\Exceptions\\InsufficientStockException',
          'movementtype' => 'App\\Domain\\Stock\\Models\\MovementType',
          'stock' => 'App\\Domain\\Stock\\Models\\Stock',
          'stockmovement' => 'App\\Domain\\Stock\\Models\\StockMovement',
          'warehouse' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
          'arrayexport' => 'App\\Exports\\ArrayExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'stockentryrequest' => 'App\\Http\\Requests\\StockEntryRequest',
          'stockissuerequest' => 'App\\Http\\Requests\\StockIssueRequest',
          'user' => 'App\\Models\\User',
          'htmltable' => 'App\\Support\\Export\\HtmlTable',
          'pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
          'httpresponse' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'className' => 'App\\Http\\Controllers\\Api\\V1\\StockController',
         'functionName' => 'issue',
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
      'e0af0ce6806f1b07fc166b59fddf6177' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Api\\V1',
         'uses' => 
        array (
          'product' => 'App\\Domain\\Catalog\\Models\\Product',
          'entrystockaction' => 'App\\Domain\\Stock\\Actions\\EntryStockAction',
          'issuestockaction' => 'App\\Domain\\Stock\\Actions\\IssueStockAction',
          'stockwriterinterface' => 'App\\Domain\\Stock\\Contracts\\StockWriterInterface',
          'stockmovementdata' => 'App\\Domain\\Stock\\DTOs\\StockMovementData',
          'insufficientstockexception' => 'App\\Domain\\Stock\\Exceptions\\InsufficientStockException',
          'movementtype' => 'App\\Domain\\Stock\\Models\\MovementType',
          'stock' => 'App\\Domain\\Stock\\Models\\Stock',
          'stockmovement' => 'App\\Domain\\Stock\\Models\\StockMovement',
          'warehouse' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
          'arrayexport' => 'App\\Exports\\ArrayExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'stockentryrequest' => 'App\\Http\\Requests\\StockEntryRequest',
          'stockissuerequest' => 'App\\Http\\Requests\\StockIssueRequest',
          'user' => 'App\\Models\\User',
          'htmltable' => 'App\\Support\\Export\\HtmlTable',
          'pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
          'httpresponse' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'className' => 'App\\Http\\Controllers\\Api\\V1\\StockController',
         'functionName' => 'adjust',
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
      '40f0dd57d9b30facafcfb29813f9f6ce' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Api\\V1',
         'uses' => 
        array (
          'product' => 'App\\Domain\\Catalog\\Models\\Product',
          'entrystockaction' => 'App\\Domain\\Stock\\Actions\\EntryStockAction',
          'issuestockaction' => 'App\\Domain\\Stock\\Actions\\IssueStockAction',
          'stockwriterinterface' => 'App\\Domain\\Stock\\Contracts\\StockWriterInterface',
          'stockmovementdata' => 'App\\Domain\\Stock\\DTOs\\StockMovementData',
          'insufficientstockexception' => 'App\\Domain\\Stock\\Exceptions\\InsufficientStockException',
          'movementtype' => 'App\\Domain\\Stock\\Models\\MovementType',
          'stock' => 'App\\Domain\\Stock\\Models\\Stock',
          'stockmovement' => 'App\\Domain\\Stock\\Models\\StockMovement',
          'warehouse' => 'App\\Domain\\Warehouses\\Models\\Warehouse',
          'arrayexport' => 'App\\Exports\\ArrayExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'stockentryrequest' => 'App\\Http\\Requests\\StockEntryRequest',
          'stockissuerequest' => 'App\\Http\\Requests\\StockIssueRequest',
          'user' => 'App\\Models\\User',
          'htmltable' => 'App\\Support\\Export\\HtmlTable',
          'pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
          'httpresponse' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'className' => 'App\\Http\\Controllers\\Api\\V1\\StockController',
         'functionName' => 'returnIn',
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
    ),
    1 => 
    array (
      'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\StockController.php' => 'ef50c94c97b2b336f54a9cb6fe41f5d8f5d225227daa6605af962be7447c9daa',
    ),
  ),
));