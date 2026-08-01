<?php declare(strict_types = 1);

// ftm-C:\OPTIZAWORKS\igoutech\backend\app\Http\Controllers\Api\V1\ProductPriceController.php
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v5-2.3.2',
   'data' => 
  array (
    0 => 
    array (
      'df9c4c3e9787466731bf5ee5f0090f97' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Api\\V1',
         'uses' => 
        array (
          'product' => 'App\\Domain\\Catalog\\Models\\Product',
          'setproductpricesaction' => 'App\\Domain\\Pricing\\Actions\\SetProductPricesAction',
          'margincalculatorinterface' => 'App\\Domain\\Pricing\\Contracts\\MarginCalculatorInterface',
          'productpricerepositoryinterface' => 'App\\Domain\\Pricing\\Contracts\\ProductPriceRepositoryInterface',
          'priceleveldata' => 'App\\Domain\\Pricing\\DTOs\\PriceLevelData',
          'invalidpriceorderexception' => 'App\\Domain\\Pricing\\Exceptions\\InvalidPriceOrderException',
          'pricetype' => 'App\\Domain\\Pricing\\Models\\PriceType',
          'productprice' => 'App\\Domain\\Pricing\\Models\\ProductPrice',
          'productcostresolver' => 'App\\Domain\\Pricing\\Services\\ProductCostResolver',
          'arrayexport' => 'App\\Exports\\ArrayExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'updateproductpricesrequest' => 'App\\Http\\Requests\\UpdateProductPricesRequest',
          'htmltable' => 'App\\Support\\Export\\HtmlTable',
          'sortable' => 'App\\Support\\Query\\Sortable',
          'pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
          'httpresponse' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'className' => 'App\\Http\\Controllers\\Api\\V1\\ProductPriceController',
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
      '356464cbce5c1e07af463406a6d9fd91' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Api\\V1',
         'uses' => 
        array (
          'product' => 'App\\Domain\\Catalog\\Models\\Product',
          'setproductpricesaction' => 'App\\Domain\\Pricing\\Actions\\SetProductPricesAction',
          'margincalculatorinterface' => 'App\\Domain\\Pricing\\Contracts\\MarginCalculatorInterface',
          'productpricerepositoryinterface' => 'App\\Domain\\Pricing\\Contracts\\ProductPriceRepositoryInterface',
          'priceleveldata' => 'App\\Domain\\Pricing\\DTOs\\PriceLevelData',
          'invalidpriceorderexception' => 'App\\Domain\\Pricing\\Exceptions\\InvalidPriceOrderException',
          'pricetype' => 'App\\Domain\\Pricing\\Models\\PriceType',
          'productprice' => 'App\\Domain\\Pricing\\Models\\ProductPrice',
          'productcostresolver' => 'App\\Domain\\Pricing\\Services\\ProductCostResolver',
          'arrayexport' => 'App\\Exports\\ArrayExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'updateproductpricesrequest' => 'App\\Http\\Requests\\UpdateProductPricesRequest',
          'htmltable' => 'App\\Support\\Export\\HtmlTable',
          'sortable' => 'App\\Support\\Query\\Sortable',
          'pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
          'httpresponse' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'className' => 'App\\Http\\Controllers\\Api\\V1\\ProductPriceController',
         'functionName' => '__construct',
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
      '9b371646ea4d85e7e59204e71865021a' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Api\\V1',
         'uses' => 
        array (
          'product' => 'App\\Domain\\Catalog\\Models\\Product',
          'setproductpricesaction' => 'App\\Domain\\Pricing\\Actions\\SetProductPricesAction',
          'margincalculatorinterface' => 'App\\Domain\\Pricing\\Contracts\\MarginCalculatorInterface',
          'productpricerepositoryinterface' => 'App\\Domain\\Pricing\\Contracts\\ProductPriceRepositoryInterface',
          'priceleveldata' => 'App\\Domain\\Pricing\\DTOs\\PriceLevelData',
          'invalidpriceorderexception' => 'App\\Domain\\Pricing\\Exceptions\\InvalidPriceOrderException',
          'pricetype' => 'App\\Domain\\Pricing\\Models\\PriceType',
          'productprice' => 'App\\Domain\\Pricing\\Models\\ProductPrice',
          'productcostresolver' => 'App\\Domain\\Pricing\\Services\\ProductCostResolver',
          'arrayexport' => 'App\\Exports\\ArrayExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'updateproductpricesrequest' => 'App\\Http\\Requests\\UpdateProductPricesRequest',
          'htmltable' => 'App\\Support\\Export\\HtmlTable',
          'sortable' => 'App\\Support\\Query\\Sortable',
          'pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
          'httpresponse' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'className' => 'App\\Http\\Controllers\\Api\\V1\\ProductPriceController',
         'functionName' => 'list',
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
      'd5899d714203c00ada1a71f2f372b607' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Api\\V1',
         'uses' => 
        array (
          'product' => 'App\\Domain\\Catalog\\Models\\Product',
          'setproductpricesaction' => 'App\\Domain\\Pricing\\Actions\\SetProductPricesAction',
          'margincalculatorinterface' => 'App\\Domain\\Pricing\\Contracts\\MarginCalculatorInterface',
          'productpricerepositoryinterface' => 'App\\Domain\\Pricing\\Contracts\\ProductPriceRepositoryInterface',
          'priceleveldata' => 'App\\Domain\\Pricing\\DTOs\\PriceLevelData',
          'invalidpriceorderexception' => 'App\\Domain\\Pricing\\Exceptions\\InvalidPriceOrderException',
          'pricetype' => 'App\\Domain\\Pricing\\Models\\PriceType',
          'productprice' => 'App\\Domain\\Pricing\\Models\\ProductPrice',
          'productcostresolver' => 'App\\Domain\\Pricing\\Services\\ProductCostResolver',
          'arrayexport' => 'App\\Exports\\ArrayExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'updateproductpricesrequest' => 'App\\Http\\Requests\\UpdateProductPricesRequest',
          'htmltable' => 'App\\Support\\Export\\HtmlTable',
          'sortable' => 'App\\Support\\Query\\Sortable',
          'pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
          'httpresponse' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'className' => 'App\\Http\\Controllers\\Api\\V1\\ProductPriceController',
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
      '8adcd8ffced0f6303971f3dc01c92a09' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Api\\V1',
         'uses' => 
        array (
          'product' => 'App\\Domain\\Catalog\\Models\\Product',
          'setproductpricesaction' => 'App\\Domain\\Pricing\\Actions\\SetProductPricesAction',
          'margincalculatorinterface' => 'App\\Domain\\Pricing\\Contracts\\MarginCalculatorInterface',
          'productpricerepositoryinterface' => 'App\\Domain\\Pricing\\Contracts\\ProductPriceRepositoryInterface',
          'priceleveldata' => 'App\\Domain\\Pricing\\DTOs\\PriceLevelData',
          'invalidpriceorderexception' => 'App\\Domain\\Pricing\\Exceptions\\InvalidPriceOrderException',
          'pricetype' => 'App\\Domain\\Pricing\\Models\\PriceType',
          'productprice' => 'App\\Domain\\Pricing\\Models\\ProductPrice',
          'productcostresolver' => 'App\\Domain\\Pricing\\Services\\ProductCostResolver',
          'arrayexport' => 'App\\Exports\\ArrayExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'updateproductpricesrequest' => 'App\\Http\\Requests\\UpdateProductPricesRequest',
          'htmltable' => 'App\\Support\\Export\\HtmlTable',
          'sortable' => 'App\\Support\\Query\\Sortable',
          'pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
          'httpresponse' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'className' => 'App\\Http\\Controllers\\Api\\V1\\ProductPriceController',
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
      '2dcac35a8472caffe2be0c0388d49869' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Api\\V1',
         'uses' => 
        array (
          'product' => 'App\\Domain\\Catalog\\Models\\Product',
          'setproductpricesaction' => 'App\\Domain\\Pricing\\Actions\\SetProductPricesAction',
          'margincalculatorinterface' => 'App\\Domain\\Pricing\\Contracts\\MarginCalculatorInterface',
          'productpricerepositoryinterface' => 'App\\Domain\\Pricing\\Contracts\\ProductPriceRepositoryInterface',
          'priceleveldata' => 'App\\Domain\\Pricing\\DTOs\\PriceLevelData',
          'invalidpriceorderexception' => 'App\\Domain\\Pricing\\Exceptions\\InvalidPriceOrderException',
          'pricetype' => 'App\\Domain\\Pricing\\Models\\PriceType',
          'productprice' => 'App\\Domain\\Pricing\\Models\\ProductPrice',
          'productcostresolver' => 'App\\Domain\\Pricing\\Services\\ProductCostResolver',
          'arrayexport' => 'App\\Exports\\ArrayExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'updateproductpricesrequest' => 'App\\Http\\Requests\\UpdateProductPricesRequest',
          'htmltable' => 'App\\Support\\Export\\HtmlTable',
          'sortable' => 'App\\Support\\Query\\Sortable',
          'pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
          'httpresponse' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'className' => 'App\\Http\\Controllers\\Api\\V1\\ProductPriceController',
         'functionName' => 'update',
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
      '69999dfe28e95e5e1256f3451f3d56ee' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Api\\V1',
         'uses' => 
        array (
          'product' => 'App\\Domain\\Catalog\\Models\\Product',
          'setproductpricesaction' => 'App\\Domain\\Pricing\\Actions\\SetProductPricesAction',
          'margincalculatorinterface' => 'App\\Domain\\Pricing\\Contracts\\MarginCalculatorInterface',
          'productpricerepositoryinterface' => 'App\\Domain\\Pricing\\Contracts\\ProductPriceRepositoryInterface',
          'priceleveldata' => 'App\\Domain\\Pricing\\DTOs\\PriceLevelData',
          'invalidpriceorderexception' => 'App\\Domain\\Pricing\\Exceptions\\InvalidPriceOrderException',
          'pricetype' => 'App\\Domain\\Pricing\\Models\\PriceType',
          'productprice' => 'App\\Domain\\Pricing\\Models\\ProductPrice',
          'productcostresolver' => 'App\\Domain\\Pricing\\Services\\ProductCostResolver',
          'arrayexport' => 'App\\Exports\\ArrayExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'updateproductpricesrequest' => 'App\\Http\\Requests\\UpdateProductPricesRequest',
          'htmltable' => 'App\\Support\\Export\\HtmlTable',
          'sortable' => 'App\\Support\\Query\\Sortable',
          'pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
          'httpresponse' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'className' => 'App\\Http\\Controllers\\Api\\V1\\ProductPriceController',
         'functionName' => 'history',
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
      'a4858ddebc01d51ef5cf6f32c8eebb8a' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Http\\Controllers\\Api\\V1',
         'uses' => 
        array (
          'product' => 'App\\Domain\\Catalog\\Models\\Product',
          'setproductpricesaction' => 'App\\Domain\\Pricing\\Actions\\SetProductPricesAction',
          'margincalculatorinterface' => 'App\\Domain\\Pricing\\Contracts\\MarginCalculatorInterface',
          'productpricerepositoryinterface' => 'App\\Domain\\Pricing\\Contracts\\ProductPriceRepositoryInterface',
          'priceleveldata' => 'App\\Domain\\Pricing\\DTOs\\PriceLevelData',
          'invalidpriceorderexception' => 'App\\Domain\\Pricing\\Exceptions\\InvalidPriceOrderException',
          'pricetype' => 'App\\Domain\\Pricing\\Models\\PriceType',
          'productprice' => 'App\\Domain\\Pricing\\Models\\ProductPrice',
          'productcostresolver' => 'App\\Domain\\Pricing\\Services\\ProductCostResolver',
          'arrayexport' => 'App\\Exports\\ArrayExport',
          'controller' => 'App\\Http\\Controllers\\Controller',
          'updateproductpricesrequest' => 'App\\Http\\Requests\\UpdateProductPricesRequest',
          'htmltable' => 'App\\Support\\Export\\HtmlTable',
          'sortable' => 'App\\Support\\Query\\Sortable',
          'pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
          'binaryfileresponse' => 'Symfony\\Component\\HttpFoundation\\BinaryFileResponse',
          'httpresponse' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'className' => 'App\\Http\\Controllers\\Api\\V1\\ProductPriceController',
         'functionName' => 'belowFloor',
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
      'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\ProductPriceController.php' => 'bdf04861d022706b06c48988c31d7554df162b1a8bb38ca85475de8c2946471e',
    ),
  ),
));