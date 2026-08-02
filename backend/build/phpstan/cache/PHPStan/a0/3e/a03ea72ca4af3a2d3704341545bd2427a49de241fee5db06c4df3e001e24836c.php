<?php declare(strict_types = 1);

// odsl-C:\OPTIZAWORKS\igoutech\backend\app
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v1-enums',
   'data' => 
  array (
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Contracts\\PermissionResolverInterface.php' => 
    array (
      0 => 'b412b420d1ad9071ae41d565712c4061ecfd12a8dd1fd7c3eed301dcf73ef002',
      1 => 
      array (
        0 => 'app\\domain\\access\\contracts\\permissionresolverinterface',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\contracts\\effectivepermissions',
        1 => 'app\\domain\\access\\contracts\\has',
        2 => 'app\\domain\\access\\contracts\\forget',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Models\\Permission.php' => 
    array (
      0 => '2530fe894c6467657d8606c9804be4191f2b132110857721612978370ab7d6e3',
      1 => 
      array (
        0 => 'app\\domain\\access\\models\\permission',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\models\\newfactory',
        1 => 'app\\domain\\access\\models\\roles',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Models\\Role.php' => 
    array (
      0 => 'a5b32a2118befac6127838c92c4546d09662eb339c35db7c477b8fe172db0434',
      1 => 
      array (
        0 => 'app\\domain\\access\\models\\role',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\models\\newfactory',
        1 => 'app\\domain\\access\\models\\casts',
        2 => 'app\\domain\\access\\models\\permissions',
        3 => 'app\\domain\\access\\models\\users',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Services\\PermissionResolver.php' => 
    array (
      0 => '7bf211345094969647266c46b5813224ef3babcc1fc1c0ac28fd0bd9f06cedd9',
      1 => 
      array (
        0 => 'app\\domain\\access\\services\\permissionresolver',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\services\\__construct',
        1 => 'app\\domain\\access\\services\\effectivepermissions',
        2 => 'app\\domain\\access\\services\\has',
        3 => 'app\\domain\\access\\services\\forget',
        4 => 'app\\domain\\access\\services\\compute',
        5 => 'app\\domain\\access\\services\\cachekey',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Actions\\CreateProductAction.php' => 
    array (
      0 => '5df38ae310c67459de82ff34db188b6170cab025b263cac91dbd8c93f3a6784b',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\actions\\createproductaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\actions\\__construct',
        1 => 'app\\domain\\catalog\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Actions\\UpdateProductAction.php' => 
    array (
      0 => '28b9f99d538a8331ff8e4dd903483817aa409e585340461fb8ecb921fd53af48',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\actions\\updateproductaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\actions\\__construct',
        1 => 'app\\domain\\catalog\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Contracts\\ProductRepositoryInterface.php' => 
    array (
      0 => '681f922ea253c43bef8827ad82e2f768ab50d7a592dd91cda283cfdbdf918d80',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\contracts\\productrepositoryinterface',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\contracts\\create',
        1 => 'app\\domain\\catalog\\contracts\\update',
        2 => 'app\\domain\\catalog\\contracts\\updatepricing',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\DTOs\\ProductData.php' => 
    array (
      0 => '8eb7d35f136229a5fdb6c6c026915b67d32dd3d3f7c651ebb3bb72b689dbeb16',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\dtos\\productdata',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\dtos\\__construct',
        1 => 'app\\domain\\catalog\\dtos\\fromarray',
        2 => 'app\\domain\\catalog\\dtos\\toattributes',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Models\\Brand.php' => 
    array (
      0 => 'ea9bd7110dc756473fe545f1228209af3074a9871cf18343c00ed0e70d0d0a46',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\models\\brand',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\models\\casts',
        1 => 'app\\domain\\catalog\\models\\newfactory',
        2 => 'app\\domain\\catalog\\models\\products',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Models\\Category.php' => 
    array (
      0 => '4f94c84f4443e73f0bd502316b20ee739e824858febfeb7ca2caba9115f510df',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\models\\category',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\models\\casts',
        1 => 'app\\domain\\catalog\\models\\newfactory',
        2 => 'app\\domain\\catalog\\models\\parent',
        3 => 'app\\domain\\catalog\\models\\children',
        4 => 'app\\domain\\catalog\\models\\products',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Models\\Product.php' => 
    array (
      0 => '2ad3086480700fd2717d8aa14720c0b9f53f61021aea3f2c10e39deb11c7b324',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\models\\product',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\models\\casts',
        1 => 'app\\domain\\catalog\\models\\newfactory',
        2 => 'app\\domain\\catalog\\models\\category',
        3 => 'app\\domain\\catalog\\models\\brand',
        4 => 'app\\domain\\catalog\\models\\unit',
        5 => 'app\\domain\\catalog\\models\\serials',
        6 => 'app\\domain\\catalog\\models\\attributes',
        7 => 'app\\domain\\catalog\\models\\images',
        8 => 'app\\domain\\catalog\\models\\suppliers',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Models\\ProductSerial.php' => 
    array (
      0 => '1c0492f93a6cf871f0e073fbf653ce5abbd3b4b91606313310cb4b97d7def861',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\models\\productserial',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\models\\casts',
        1 => 'app\\domain\\catalog\\models\\product',
        2 => 'app\\domain\\catalog\\models\\warehouse',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Models\\Unit.php' => 
    array (
      0 => '5c5a3aabfe2b79c30f09d50ed641406610c048d2c874b362698230192af0af4b',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\models\\unit',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\models\\casts',
        1 => 'app\\domain\\catalog\\models\\newfactory',
        2 => 'app\\domain\\catalog\\models\\products',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Repositories\\ProductRepository.php' => 
    array (
      0 => 'df3e2131ebf3578c4d55f7454d1478ed55fea4f4c35b41b8f4a02de62883480f',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\repositories\\productrepository',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\repositories\\create',
        1 => 'app\\domain\\catalog\\repositories\\update',
        2 => 'app\\domain\\catalog\\repositories\\updatepricing',
        3 => 'app\\domain\\catalog\\repositories\\defaultunitid',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Actions\\CreateTransferAction.php' => 
    array (
      0 => 'aaec7f34bfc15073f862b22f5ad2c6969fe3849c8dce67902d3829fd6a902cc2',
      1 => 
      array (
        0 => 'app\\domain\\stock\\actions\\createtransferaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\actions\\__construct',
        1 => 'app\\domain\\stock\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Actions\\ReceiveTransferAction.php' => 
    array (
      0 => '99128d02cb1eff409f0bcace99ae9ba846da88d04eb1c7eaa1d2057a20f06cf0',
      1 => 
      array (
        0 => 'app\\domain\\stock\\actions\\receivetransferaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\actions\\__construct',
        1 => 'app\\domain\\stock\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Actions\\StockInAction.php' => 
    array (
      0 => '9b3dff14c08fa15936d91ef0e8a18890f5e92963461237e44faa21d79db03f9e',
      1 => 
      array (
        0 => 'app\\domain\\stock\\actions\\stockinaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\actions\\__construct',
        1 => 'app\\domain\\stock\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Actions\\StockOutAction.php' => 
    array (
      0 => 'ca984844afbb5b58743be85dd39107425d2a92a3df29945abca6deb07f60bc1b',
      1 => 
      array (
        0 => 'app\\domain\\stock\\actions\\stockoutaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\actions\\__construct',
        1 => 'app\\domain\\stock\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Contracts\\StockReaderInterface.php' => 
    array (
      0 => 'c00cf82a0b14400fbc345feab9e4132142bf1b8203ac769465276a67400ab7af',
      1 => 
      array (
        0 => 'app\\domain\\stock\\contracts\\stockreaderinterface',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\contracts\\quantityfor',
        1 => 'app\\domain\\stock\\contracts\\globalquantityfor',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Contracts\\StockValuationInterface.php' => 
    array (
      0 => 'bd00dcd24dbc2c0356d433613bbb3e458962103a2138b73265ca582cf7572deb',
      1 => 
      array (
        0 => 'app\\domain\\stock\\contracts\\stockvaluationinterface',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\contracts\\newunitcost',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Contracts\\StockWriterInterface.php' => 
    array (
      0 => '167b7c0c4b6779b4c0ed39f1ef66e82ebb5e72a9fcd43313825c063ec502c920',
      1 => 
      array (
        0 => 'app\\domain\\stock\\contracts\\stockwriterinterface',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\contracts\\increase',
        1 => 'app\\domain\\stock\\contracts\\decrease',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\DTOs\\StockMovementData.php' => 
    array (
      0 => '3ce101f9f3b79402cf0e2f62aeea02e8e7416b60a400055a8c2a7b25458f5c0e',
      1 => 
      array (
        0 => 'app\\domain\\stock\\dtos\\stockmovementdata',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\dtos\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\DTOs\\TransferData.php' => 
    array (
      0 => 'f777370a4f4e0dab17ea7b96948342616fe9995457a5290762964925dfecfdff',
      1 => 
      array (
        0 => 'app\\domain\\stock\\dtos\\transferdata',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\dtos\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\DTOs\\TransferLineData.php' => 
    array (
      0 => '00276605d2b9f7ca2821e8f7a2386b6133c1eec1a219c24267ba9e9b5896c912',
      1 => 
      array (
        0 => 'app\\domain\\stock\\dtos\\transferlinedata',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\dtos\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Events\\TransferDiscrepancyDetected.php' => 
    array (
      0 => '37097a8a47c50556f83e2a371a9c2f0c811416018d3d7a0de221ddc3a63988c2',
      1 => 
      array (
        0 => 'app\\domain\\stock\\events\\transferdiscrepancydetected',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\events\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Exceptions\\InsufficientStockException.php' => 
    array (
      0 => 'fead8a18ee0a50f9eccf582e16667f8bad7de6d901496a2248adc95fd89dd503',
      1 => 
      array (
        0 => 'app\\domain\\stock\\exceptions\\insufficientstockexception',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\exceptions\\for',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Exceptions\\InvalidTransferException.php' => 
    array (
      0 => '52e478a158017c1012d199aad60c1eea6663aa982fe74284c4e23f590ac48156',
      1 => 
      array (
        0 => 'app\\domain\\stock\\exceptions\\invalidtransferexception',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\exceptions\\samewarehouse',
        1 => 'app\\domain\\stock\\exceptions\\notintransit',
        2 => 'app\\domain\\stock\\exceptions\\emptylines',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Models\\Inventory.php' => 
    array (
      0 => 'b2e31ceaa4d4c1c8cab8ed4139984a56041c4174425bf37a82ba9bac3a867836',
      1 => 
      array (
        0 => 'app\\domain\\stock\\models\\inventory',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\models\\casts',
        1 => 'app\\domain\\stock\\models\\warehouse',
        2 => 'app\\domain\\stock\\models\\lines',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Models\\InventoryLine.php' => 
    array (
      0 => 'ddc72c5d3a5f0d6e4113e8165a8fd8f4faef6916162b6685f18451a5e930e526',
      1 => 
      array (
        0 => 'app\\domain\\stock\\models\\inventoryline',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\models\\casts',
        1 => 'app\\domain\\stock\\models\\inventory',
        2 => 'app\\domain\\stock\\models\\product',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Models\\MovementType.php' => 
    array (
      0 => '72a9cd36aebe1e7931b247c1203139908c255757eb32261cc710b5c6165a1530',
      1 => 
      array (
        0 => 'app\\domain\\stock\\models\\movementtype',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\models\\casts',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Models\\Stock.php' => 
    array (
      0 => '10ae386dc6269979bba62726b31da1dd49bddee119befce064d22468b7206085',
      1 => 
      array (
        0 => 'app\\domain\\stock\\models\\stock',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\models\\casts',
        1 => 'app\\domain\\stock\\models\\product',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Models\\StockMovement.php' => 
    array (
      0 => '2e1d7c22dbc260b1de60913acaa75c16f018c4662bb968e28f1b3a9610c741e7',
      1 => 
      array (
        0 => 'app\\domain\\stock\\models\\stockmovement',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\models\\casts',
        1 => 'app\\domain\\stock\\models\\movementtype',
        2 => 'app\\domain\\stock\\models\\product',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Models\\Transfer.php' => 
    array (
      0 => 'b7f89bcea2f5f2fd236ad6448dd9203a1832ed135a6b26bc7d7c8348a5db8d0c',
      1 => 
      array (
        0 => 'app\\domain\\stock\\models\\transfer',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\models\\casts',
        1 => 'app\\domain\\stock\\models\\fromwarehouse',
        2 => 'app\\domain\\stock\\models\\towarehouse',
        3 => 'app\\domain\\stock\\models\\status',
        4 => 'app\\domain\\stock\\models\\lines',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Models\\TransferLine.php' => 
    array (
      0 => '502b56b38f18de5d330b4840e06e565e7415fce74314808e54113d51bc7a6bd4',
      1 => 
      array (
        0 => 'app\\domain\\stock\\models\\transferline',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\models\\casts',
        1 => 'app\\domain\\stock\\models\\transfer',
        2 => 'app\\domain\\stock\\models\\product',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Models\\TransferStatus.php' => 
    array (
      0 => '22a425464fdba9d34f72e28503fba79ae1b5a8d7ed9e5ac5e3a250da42024188',
      1 => 
      array (
        0 => 'app\\domain\\stock\\models\\transferstatus',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Repositories\\StockRepository.php' => 
    array (
      0 => '61bf3307ae0239a8818b9e32c9a52a8a34d5a5450d14844a76baa0b6913fbf28',
      1 => 
      array (
        0 => 'app\\domain\\stock\\repositories\\stockrepository',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\repositories\\__construct',
        1 => 'app\\domain\\stock\\repositories\\quantityfor',
        2 => 'app\\domain\\stock\\repositories\\globalquantityfor',
        3 => 'app\\domain\\stock\\repositories\\increase',
        4 => 'app\\domain\\stock\\repositories\\decrease',
        5 => 'app\\domain\\stock\\repositories\\lockorcreatestock',
        6 => 'app\\domain\\stock\\repositories\\recordmovement',
        7 => 'app\\domain\\stock\\repositories\\movementtype',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Services\\AverageCostValuation.php' => 
    array (
      0 => 'ebe35caeecd9c8c81ca82d763cc2d8b1cfe2b18d5a7db18674ea5ecddb1c2563',
      1 => 
      array (
        0 => 'app\\domain\\stock\\services\\averagecostvaluation',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\services\\newunitcost',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Services\\StockOverviewService.php' => 
    array (
      0 => 'f3bbbe2c2561108a5844769bd919b140bdde74b3c0e5309fddf8f767141fb880',
      1 => 
      array (
        0 => 'app\\domain\\stock\\services\\stockoverviewservice',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\services\\summary',
        1 => 'app\\domain\\stock\\services\\consolidatedstock',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Warehouses\\Models\\Warehouse.php' => 
    array (
      0 => '74d0d7903ade9d73fd46a0e960ee16af8c332c831d637b2776a4ba7e7d4cd860',
      1 => 
      array (
        0 => 'app\\domain\\warehouses\\models\\warehouse',
      ),
      2 => 
      array (
        0 => 'app\\domain\\warehouses\\models\\newfactory',
        1 => 'app\\domain\\warehouses\\models\\casts',
        2 => 'app\\domain\\warehouses\\models\\type',
        3 => 'app\\domain\\warehouses\\models\\manager',
        4 => 'app\\domain\\warehouses\\models\\parent',
        5 => 'app\\domain\\warehouses\\models\\children',
        6 => 'app\\domain\\warehouses\\models\\users',
        7 => 'app\\domain\\warehouses\\models\\stocks',
        8 => 'app\\domain\\warehouses\\models\\isvehicle',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Warehouses\\Models\\WarehouseType.php' => 
    array (
      0 => 'd38407173b9a3e7f3dc8da2d069078aa3fde0dc9f057b11e435efb443e5d9586',
      1 => 
      array (
        0 => 'app\\domain\\warehouses\\models\\warehousetype',
      ),
      2 => 
      array (
        0 => 'app\\domain\\warehouses\\models\\newfactory',
        1 => 'app\\domain\\warehouses\\models\\casts',
        2 => 'app\\domain\\warehouses\\models\\warehouses',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\DashboardController.php' => 
    array (
      0 => 'feca507f045e52c7a43eb20f8140aafe8d0d78c79500a2cfff6b631dd1fec809',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\dashboardcontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\index',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\MeController.php' => 
    array (
      0 => 'b8bd0ac81d5b75d2e461b9364418f064ec4776a52707c96c3c7f0a89e2068f8a',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\mecontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\__invoke',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\ProductController.php' => 
    array (
      0 => 'cd14da3f699b6198cd9ef95b12c49f9d911f63a0a59b5217dc18e2dad67a44a6',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\productcontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\perpage',
        1 => 'app\\http\\controllers\\api\\v1\\index',
        2 => 'app\\http\\controllers\\api\\v1\\store',
        3 => 'app\\http\\controllers\\api\\v1\\show',
        4 => 'app\\http\\controllers\\api\\v1\\update',
        5 => 'app\\http\\controllers\\api\\v1\\updatepricing',
        6 => 'app\\http\\controllers\\api\\v1\\destroy',
        7 => 'app\\http\\controllers\\api\\v1\\bulkdestroy',
        8 => 'app\\http\\controllers\\api\\v1\\export',
        9 => 'app\\http\\controllers\\api\\v1\\import',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Auth\\AuthenticatedSessionController.php' => 
    array (
      0 => '1df31b379d350abc3d853c716ab3cdf63474a319c0c9d95d9255e01d2ec5fcd4',
      1 => 
      array (
        0 => 'app\\http\\controllers\\auth\\authenticatedsessioncontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\auth\\store',
        1 => 'app\\http\\controllers\\auth\\destroy',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Controller.php' => 
    array (
      0 => '09e5cac5a69959ccf23d756cda697b993df937c106655d124ff27ca5fe24a705',
      1 => 
      array (
        0 => 'app\\http\\controllers\\controller',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Middleware\\EnsureEmailIsVerified.php' => 
    array (
      0 => 'd16b7ea01f8ea1dd97db83635d4c2235333c114221c7124f2674154e08f0ec25',
      1 => 
      array (
        0 => 'app\\http\\middleware\\ensureemailisverified',
      ),
      2 => 
      array (
        0 => 'app\\http\\middleware\\handle',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\Auth\\LoginRequest.php' => 
    array (
      0 => '2e47ef7735160032aeeecd5c539f19952c07d57c168bd3f65d10b0decf7dcd94',
      1 => 
      array (
        0 => 'app\\http\\requests\\auth\\loginrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\auth\\authorize',
        1 => 'app\\http\\requests\\auth\\rules',
        2 => 'app\\http\\requests\\auth\\authenticate',
        3 => 'app\\http\\requests\\auth\\registerfailedattempt',
        4 => 'app\\http\\requests\\auth\\ensureisnotratelimited',
        5 => 'app\\http\\requests\\auth\\throttlekey',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\StoreProductRequest.php' => 
    array (
      0 => 'a63f0a39a64ad908727dbc49fc32dea4e32780f18daafdaabd8038808ac8f481',
      1 => 
      array (
        0 => 'app\\http\\requests\\storeproductrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\UpdateProductRequest.php' => 
    array (
      0 => '2145177ae36fc39852eff35126434e2539b19727cec0f43233353c8cf8219791',
      1 => 
      array (
        0 => 'app\\http\\requests\\updateproductrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Resources\\ProductResource.php' => 
    array (
      0 => '6614cbdc7801e00b686f71b343cba73631da216314cf35a84715d2aadb11745d',
      1 => 
      array (
        0 => 'app\\http\\resources\\productresource',
      ),
      2 => 
      array (
        0 => 'app\\http\\resources\\toarray',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Resources\\UserResource.php' => 
    array (
      0 => '2f779f557aacf63fbc7af8e2f389e8da345919dc10a68e6a0f825702885e53dd',
      1 => 
      array (
        0 => 'app\\http\\resources\\userresource',
      ),
      2 => 
      array (
        0 => 'app\\http\\resources\\toarray',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Models\\User.php' => 
    array (
      0 => 'b14b37b1dfded1f4d10e4c0f3e4fc202b790f4bf83164525265618fa97a220e9',
      1 => 
      array (
        0 => 'app\\models\\user',
      ),
      2 => 
      array (
        0 => 'app\\models\\casts',
        1 => 'app\\models\\islocked',
        2 => 'app\\models\\warehouse',
        3 => 'app\\models\\roles',
        4 => 'app\\models\\permissionoverrides',
        5 => 'app\\models\\hasglobalaccess',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Providers\\AppServiceProvider.php' => 
    array (
      0 => '48b8ccd20f8e416851572adcf58571352a7bb1c3e219041f381ed16d05825712',
      1 => 
      array (
        0 => 'app\\providers\\appserviceprovider',
      ),
      2 => 
      array (
        0 => 'app\\providers\\register',
        1 => 'app\\providers\\boot',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Providers\\Domain\\AccessServiceProvider.php' => 
    array (
      0 => '16e44d9bbff888e1aacf905f277e8e34f2369f308669e1349bc6ffe4e295beea',
      1 => 
      array (
        0 => 'app\\providers\\domain\\accessserviceprovider',
      ),
      2 => 
      array (
        0 => 'app\\providers\\domain\\boot',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Providers\\Domain\\CatalogServiceProvider.php' => 
    array (
      0 => '34443971d8857d3beeaebf0685fefd94197c399d844ae52de0e7f131162960f4',
      1 => 
      array (
        0 => 'app\\providers\\domain\\catalogserviceprovider',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Providers\\Domain\\StockServiceProvider.php' => 
    array (
      0 => '9eedaaffdf495e2fe29b2b6bcfc6082fc2fcab9f5173498ff469a2c33a73ce4c',
      1 => 
      array (
        0 => 'app\\providers\\domain\\stockserviceprovider',
      ),
      2 => 
      array (
        0 => 'app\\providers\\domain\\register',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Support\\Concerns\\BelongsToWarehouse.php' => 
    array (
      0 => '089a2e0fe1f9f45aca48d7a363594c1b1efd218396054a5f86a8bc57d656b304',
      1 => 
      array (
        0 => 'app\\support\\concerns\\belongstowarehouse',
      ),
      2 => 
      array (
        0 => 'app\\support\\concerns\\bootbelongstowarehouse',
        1 => 'app\\support\\concerns\\warehouse',
        2 => 'app\\support\\concerns\\globalviewpermission',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Support\\Documents\\DocumentNumberGeneratorInterface.php' => 
    array (
      0 => '91eea9e01debff72e5da2a5a424ab729efb95254c0f5e4cdee346203861a032f',
      1 => 
      array (
        0 => 'app\\support\\documents\\documentnumbergeneratorinterface',
      ),
      2 => 
      array (
        0 => 'app\\support\\documents\\next',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Support\\Documents\\SequentialDocumentNumberGenerator.php' => 
    array (
      0 => 'd4566abe3f0687612de130d81f0fd4ca663417276cb91c0bc8067da231ca8515',
      1 => 
      array (
        0 => 'app\\support\\documents\\sequentialdocumentnumbergenerator',
      ),
      2 => 
      array (
        0 => 'app\\support\\documents\\next',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Support\\Scopes\\WarehouseScope.php' => 
    array (
      0 => 'edcc2d8aaf04cfe8006f0bb5a1d09e109182ff343e14cbea59c53bfcba8a2fa5',
      1 => 
      array (
        0 => 'app\\support\\scopes\\warehousescope',
      ),
      2 => 
      array (
        0 => 'app\\support\\scopes\\apply',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Warehouses\\Actions\\CreateWarehouseAction.php' => 
    array (
      0 => '7cb244efa16c899b81f78f05212c8daf5be1aaf15be92313e77d5eff6bf894c2',
      1 => 
      array (
        0 => 'app\\domain\\warehouses\\actions\\createwarehouseaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\warehouses\\actions\\__construct',
        1 => 'app\\domain\\warehouses\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Warehouses\\Actions\\UpdateWarehouseAction.php' => 
    array (
      0 => 'e913e34455ffefb9fe14cb2cccc06b96bbdfe4a19a839e8436cefc0afa1d74df',
      1 => 
      array (
        0 => 'app\\domain\\warehouses\\actions\\updatewarehouseaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\warehouses\\actions\\__construct',
        1 => 'app\\domain\\warehouses\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Warehouses\\Contracts\\WarehouseRepositoryInterface.php' => 
    array (
      0 => 'ef49b7c3329b8a565940004239fd2534e58cd208dfc05db32079f1adbac504c7',
      1 => 
      array (
        0 => 'app\\domain\\warehouses\\contracts\\warehouserepositoryinterface',
      ),
      2 => 
      array (
        0 => 'app\\domain\\warehouses\\contracts\\create',
        1 => 'app\\domain\\warehouses\\contracts\\update',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Warehouses\\DTOs\\WarehouseData.php' => 
    array (
      0 => '2f04f03c29db9ed00bad15b10ed23b541d5374e6a6d3c04999dde01985e16eb3',
      1 => 
      array (
        0 => 'app\\domain\\warehouses\\dtos\\warehousedata',
      ),
      2 => 
      array (
        0 => 'app\\domain\\warehouses\\dtos\\__construct',
        1 => 'app\\domain\\warehouses\\dtos\\fromarray',
        2 => 'app\\domain\\warehouses\\dtos\\toattributes',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Warehouses\\Repositories\\WarehouseRepository.php' => 
    array (
      0 => 'f6bf1cde1b7a96505a2e6fe5d41b7891043ade4468138593fc380cb0e470c237',
      1 => 
      array (
        0 => 'app\\domain\\warehouses\\repositories\\warehouserepository',
      ),
      2 => 
      array (
        0 => 'app\\domain\\warehouses\\repositories\\create',
        1 => 'app\\domain\\warehouses\\repositories\\update',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\WarehouseController.php' => 
    array (
      0 => 'ca9604831bc00e03b1b97c5d4262c057a063a6b97ca4764d27bca50accb66b82',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\warehousecontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\index',
        1 => 'app\\http\\controllers\\api\\v1\\store',
        2 => 'app\\http\\controllers\\api\\v1\\show',
        3 => 'app\\http\\controllers\\api\\v1\\update',
        4 => 'app\\http\\controllers\\api\\v1\\toggle',
        5 => 'app\\http\\controllers\\api\\v1\\users',
        6 => 'app\\http\\controllers\\api\\v1\\assignusers',
        7 => 'app\\http\\controllers\\api\\v1\\summary',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\WarehouseTypeController.php' => 
    array (
      0 => '6e9e474abdb083d9c6674081ec4dec27919bef22300f45b462220af90abd15ab',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\warehousetypecontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\index',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\StoreWarehouseRequest.php' => 
    array (
      0 => '15be1e97c6b6744d94b09190b8268adbfed795d811aae3f5d766118624a9d713',
      1 => 
      array (
        0 => 'app\\http\\requests\\storewarehouserequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\UpdateWarehouseRequest.php' => 
    array (
      0 => '0fc9f45de69d7a1aa314614b05b54e2bfff8a343dbce6f5c1dde08758ec66ffd',
      1 => 
      array (
        0 => 'app\\http\\requests\\updatewarehouserequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Resources\\WarehouseResource.php' => 
    array (
      0 => '562bfae9e044d1b20aef129a157c3e6ddd5ebcf17ae2c50729612b99b9e4139a',
      1 => 
      array (
        0 => 'app\\http\\resources\\warehouseresource',
      ),
      2 => 
      array (
        0 => 'app\\http\\resources\\toarray',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Resources\\WarehouseTypeResource.php' => 
    array (
      0 => '5a7c2291edb11c9cfcfd90b349982959a6a787da075768a7908e15cc0ec32988',
      1 => 
      array (
        0 => 'app\\http\\resources\\warehousetyperesource',
      ),
      2 => 
      array (
        0 => 'app\\http\\resources\\toarray',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Providers\\Domain\\WarehousesServiceProvider.php' => 
    array (
      0 => 'a2c6925aca3cef7ca3ad7c2a497d749fb1a4786dccbf570d7821cee985658e3b',
      1 => 
      array (
        0 => 'app\\providers\\domain\\warehousesserviceprovider',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Actions\\CreateCategoryAction.php' => 
    array (
      0 => 'a2a9c06cd48ef2e48ab02272a0aae78ffdc17168b7f0c529363606729a40b867',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\actions\\createcategoryaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\actions\\__construct',
        1 => 'app\\domain\\catalog\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Actions\\SetProductPriceAction.php' => 
    array (
      0 => '875a950c36b3f92ed65ddd4472c736afda4272b196c697c0eeb241c64aaf687b',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\actions\\setproductpriceaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\actions\\__construct',
        1 => 'app\\domain\\catalog\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Actions\\UpdateCategoryAction.php' => 
    array (
      0 => '75211d3d8c2c203689945f7a6c232146f60b4353d325bc02fced77a30a64a2bd',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\actions\\updatecategoryaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\actions\\__construct',
        1 => 'app\\domain\\catalog\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Contracts\\CategoryRepositoryInterface.php' => 
    array (
      0 => '9784ca19d76af9f0c28490daa182843c883b4f8423ebd0bc1a8ec81f7df6ba41',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\contracts\\categoryrepositoryinterface',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\contracts\\create',
        1 => 'app\\domain\\catalog\\contracts\\update',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\DTOs\\CategoryData.php' => 
    array (
      0 => '27fc098559d844dd08981ba38d2e5d63c85338c67523effb2e16df5c8c661f77',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\dtos\\categorydata',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\dtos\\__construct',
        1 => 'app\\domain\\catalog\\dtos\\fromarray',
        2 => 'app\\domain\\catalog\\dtos\\toattributes',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\DTOs\\PricingData.php' => 
    array (
      0 => '1a10a2ad2fa2dec76272446d2761b945162b067cbd57f3ff18a432395bdbc557',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\dtos\\pricingdata',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\dtos\\__construct',
        1 => 'app\\domain\\catalog\\dtos\\fromarray',
        2 => 'app\\domain\\catalog\\dtos\\toattributes',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Repositories\\CategoryRepository.php' => 
    array (
      0 => '672a91a7380763bc73006bfbe436f9f0a63577e7f2453fea2dacdb42d191d821',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\repositories\\categoryrepository',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\repositories\\create',
        1 => 'app\\domain\\catalog\\repositories\\update',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\CategoryController.php' => 
    array (
      0 => '944164ea2fb81978521de24dc131ef319339b1924344dc2fbd2cff5c791da885',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\categorycontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\index',
        1 => 'app\\http\\controllers\\api\\v1\\reorder',
        2 => 'app\\http\\controllers\\api\\v1\\store',
        3 => 'app\\http\\controllers\\api\\v1\\update',
        4 => 'app\\http\\controllers\\api\\v1\\destroy',
        5 => 'app\\http\\controllers\\api\\v1\\bulkdestroy',
        6 => 'app\\http\\controllers\\api\\v1\\export',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\StoreCategoryRequest.php' => 
    array (
      0 => '8f0d8fb236e8a41b8e64141a20ed46cccb42f07aea601d22b7410d47ce36d50b',
      1 => 
      array (
        0 => 'app\\http\\requests\\storecategoryrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\UpdateCategoryRequest.php' => 
    array (
      0 => '248353d0e75a24c22acb295351b79a84abb13e4c8a944f6bfd1cb5917b424f77',
      1 => 
      array (
        0 => 'app\\http\\requests\\updatecategoryrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\UpdatePricingRequest.php' => 
    array (
      0 => '1a44336e205a900842fb6835f1e082899249fc8a3690e4b0ee4e2a41c6f5901d',
      1 => 
      array (
        0 => 'app\\http\\requests\\updatepricingrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Resources\\CategoryResource.php' => 
    array (
      0 => '5bd77b6acc16377b69826ec64000b83fac9e2ac91e09d5984afe7e8542bd29bd',
      1 => 
      array (
        0 => 'app\\http\\resources\\categoryresource',
      ),
      2 => 
      array (
        0 => 'app\\http\\resources\\toarray',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Pricing\\Actions\\SetProductPricesAction.php' => 
    array (
      0 => 'a08e9402653c41e06a2f632f6465a1fa25f94ded8da7c9b9ac7706e807fe7723',
      1 => 
      array (
        0 => 'app\\domain\\pricing\\actions\\setproductpricesaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\pricing\\actions\\__construct',
        1 => 'app\\domain\\pricing\\actions\\execute',
        2 => 'app\\domain\\pricing\\actions\\assertorder',
        3 => 'app\\domain\\pricing\\actions\\amountfor',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Pricing\\Contracts\\MarginCalculatorInterface.php' => 
    array (
      0 => 'fc45302ad1796735662df4e26fb24affb628c603f4e69ab986c7c92c7bef81b3',
      1 => 
      array (
        0 => 'app\\domain\\pricing\\contracts\\margincalculatorinterface',
      ),
      2 => 
      array (
        0 => 'app\\domain\\pricing\\contracts\\marginpercent',
        1 => 'app\\domain\\pricing\\contracts\\floorprice',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Pricing\\Contracts\\PriceResolverInterface.php' => 
    array (
      0 => 'c6f55269f06a33fa0f3be4e48e0cfab6a61db54ec49994b02e739d71bedcbb4d',
      1 => 
      array (
        0 => 'app\\domain\\pricing\\contracts\\priceresolverinterface',
      ),
      2 => 
      array (
        0 => 'app\\domain\\pricing\\contracts\\resolve',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Pricing\\Contracts\\ProductPriceRepositoryInterface.php' => 
    array (
      0 => '02dce24a59573ffa4b5525928868880d696f2c58d27d9bed836d30833e05c199',
      1 => 
      array (
        0 => 'app\\domain\\pricing\\contracts\\productpricerepositoryinterface',
      ),
      2 => 
      array (
        0 => 'app\\domain\\pricing\\contracts\\currentfor',
        1 => 'app\\domain\\pricing\\contracts\\activeprice',
        2 => 'app\\domain\\pricing\\contracts\\replace',
        3 => 'app\\domain\\pricing\\contracts\\historyfor',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Pricing\\DTOs\\PriceLevelData.php' => 
    array (
      0 => 'e73569ac1f7ca6e5e936ff6e3e78363126c7be6726850f31e74692bbb88cd344',
      1 => 
      array (
        0 => 'app\\domain\\pricing\\dtos\\priceleveldata',
      ),
      2 => 
      array (
        0 => 'app\\domain\\pricing\\dtos\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Pricing\\DTOs\\ResolvedPrice.php' => 
    array (
      0 => 'e7e270a0eba5853ea3697c6362d3f76702d62210824df2eb04d47cf0560c1fc7',
      1 => 
      array (
        0 => 'app\\domain\\pricing\\dtos\\resolvedprice',
      ),
      2 => 
      array (
        0 => 'app\\domain\\pricing\\dtos\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Pricing\\Exceptions\\InvalidPriceOrderException.php' => 
    array (
      0 => '1d7c34b42d30fa127df58c3667eddeb3f787822013ef0772ca838aedb3fa577a',
      1 => 
      array (
        0 => 'app\\domain\\pricing\\exceptions\\invalidpriceorderexception',
      ),
      2 => 
      array (
        0 => 'app\\domain\\pricing\\exceptions\\make',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Pricing\\Exceptions\\NoPriceDefinedException.php' => 
    array (
      0 => '2a213ede42930691cf79dadae6b2e93ac32ffd7ceac39ec77547377ef879652d',
      1 => 
      array (
        0 => 'app\\domain\\pricing\\exceptions\\nopricedefinedexception',
      ),
      2 => 
      array (
        0 => 'app\\domain\\pricing\\exceptions\\for',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Pricing\\Models\\PriceType.php' => 
    array (
      0 => '352e751e3b5426ed343433ea8f2c47bd3371f8610a5a59a2e90e81579ff02761',
      1 => 
      array (
        0 => 'app\\domain\\pricing\\models\\pricetype',
      ),
      2 => 
      array (
        0 => 'app\\domain\\pricing\\models\\casts',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Pricing\\Models\\ProductPrice.php' => 
    array (
      0 => 'b97c7a67ae418b0d451187a013faeb19cf0019a1a661053b18f1a3c9ffd3dda4',
      1 => 
      array (
        0 => 'app\\domain\\pricing\\models\\productprice',
      ),
      2 => 
      array (
        0 => 'app\\domain\\pricing\\models\\casts',
        1 => 'app\\domain\\pricing\\models\\product',
        2 => 'app\\domain\\pricing\\models\\pricetype',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Pricing\\Repositories\\ProductPriceRepository.php' => 
    array (
      0 => '4f5c198c088ff14c665bf6a92617b32737dd10e2a5081709e57d3e7f62f65e35',
      1 => 
      array (
        0 => 'app\\domain\\pricing\\repositories\\productpricerepository',
      ),
      2 => 
      array (
        0 => 'app\\domain\\pricing\\repositories\\currentfor',
        1 => 'app\\domain\\pricing\\repositories\\activeprice',
        2 => 'app\\domain\\pricing\\repositories\\replace',
        3 => 'app\\domain\\pricing\\repositories\\historyfor',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Pricing\\Services\\MarginCalculator.php' => 
    array (
      0 => '5d87c34b138ecc0fdcec55e93ed512521af74f586be56584b572981e68a6c16c',
      1 => 
      array (
        0 => 'app\\domain\\pricing\\services\\margincalculator',
      ),
      2 => 
      array (
        0 => 'app\\domain\\pricing\\services\\marginpercent',
        1 => 'app\\domain\\pricing\\services\\floorprice',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Pricing\\Services\\PriceResolver.php' => 
    array (
      0 => 'e027b147776074d2671579465b02ea03988626fc8aad13b110292a857fffe8cb',
      1 => 
      array (
        0 => 'app\\domain\\pricing\\services\\priceresolver',
      ),
      2 => 
      array (
        0 => 'app\\domain\\pricing\\services\\__construct',
        1 => 'app\\domain\\pricing\\services\\resolve',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Pricing\\Services\\ProductCostResolver.php' => 
    array (
      0 => '042d183e6c0c6ccb7935104e188a38584989466f40701da31e78aa2ea2d8c9ec',
      1 => 
      array (
        0 => 'app\\domain\\pricing\\services\\productcostresolver',
      ),
      2 => 
      array (
        0 => 'app\\domain\\pricing\\services\\unitcost',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\ProductPriceController.php' => 
    array (
      0 => 'b2223f1fd134e019edcf73b8bf0102975bc2da9cc8686d063c8b5f7267d90075',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\productpricecontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\__construct',
        1 => 'app\\http\\controllers\\api\\v1\\pricetypes',
        2 => 'app\\http\\controllers\\api\\v1\\list',
        3 => 'app\\http\\controllers\\api\\v1\\export',
        4 => 'app\\http\\controllers\\api\\v1\\index',
        5 => 'app\\http\\controllers\\api\\v1\\update',
        6 => 'app\\http\\controllers\\api\\v1\\history',
        7 => 'app\\http\\controllers\\api\\v1\\bulkupdate',
        8 => 'app\\http\\controllers\\api\\v1\\belowfloor',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\UpdateProductPricesRequest.php' => 
    array (
      0 => '635df56ab1538972aab341cdc95152e2e85d1ac68eaba2adb0599f013cd899e9',
      1 => 
      array (
        0 => 'app\\http\\requests\\updateproductpricesrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Providers\\Domain\\PricingServiceProvider.php' => 
    array (
      0 => 'd856a9ce12d4743a0d68a4ca60c66d97b4f185a87f06e8c36acd9b6e795c9b31',
      1 => 
      array (
        0 => 'app\\providers\\domain\\pricingserviceprovider',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Actions\\DeleteCategoryAction.php' => 
    array (
      0 => '9577b2f74a9ffd9d0fb0e15500f7276e1efb43681c0bda1c71475db075998d68',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\actions\\deletecategoryaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Actions\\DeleteProductAction.php' => 
    array (
      0 => '73b5929f69a2de0399462274fd5239c421b006b6b64b7d0d02d9c21ab4f25e7a',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\actions\\deleteproductaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Exceptions\\CategoryInUseException.php' => 
    array (
      0 => 'df0f09bf070754c19019952da58569d33503e125ce870bdf1be406d32dd86777',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\exceptions\\categoryinuseexception',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\exceptions\\hasproducts',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Exceptions\\ProductInUseException.php' => 
    array (
      0 => '84733ab392582e12480f5b7f702e5b50b9441ee007802016086f74d10e08544f',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\exceptions\\productinuseexception',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\exceptions\\make',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Exports\\ArrayExport.php' => 
    array (
      0 => '7a3ec2daa7d0ce3675cc9303a55fc1336403ec1c8a7f1d404f8e4c92f389e2cc',
      1 => 
      array (
        0 => 'app\\exports\\arrayexport',
      ),
      2 => 
      array (
        0 => 'app\\exports\\__construct',
        1 => 'app\\exports\\array',
        2 => 'app\\exports\\headings',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Imports\\ArticlesImport.php' => 
    array (
      0 => '400d17f6b0f8121d4bc47532bed0a4596c08569bfb31b0ec185f5e2e78c111cf',
      1 => 
      array (
        0 => 'app\\imports\\articlesimport',
      ),
      2 => 
      array (
        0 => 'app\\imports\\__construct',
        1 => 'app\\imports\\collection',
        2 => 'app\\imports\\resolvecategory',
        3 => 'app\\imports\\ensuredetailprice',
        4 => 'app\\imports\\str',
        5 => 'app\\imports\\num',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Support\\Export\\HtmlTable.php' => 
    array (
      0 => '0b4f7cf2603ecd9224f1b9dc805909cb3c54f509ccfd819607c3b6a38cc98ec6',
      1 => 
      array (
        0 => 'app\\support\\export\\htmltable',
      ),
      2 => 
      array (
        0 => 'app\\support\\export\\render',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Support\\Query\\Sortable.php' => 
    array (
      0 => '13052cd11d11f9d76b2f161ce2a4c314fc9931b56dd3c17ee83758382ab43a40',
      1 => 
      array (
        0 => 'app\\support\\query\\sortable',
      ),
      2 => 
      array (
        0 => 'app\\support\\query\\apply',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Console\\Commands\\PurgeExpiredPermissions.php' => 
    array (
      0 => '2222f7f7e3a3b15fb3ad8f25d5aac1961a262edf321e93f49a3651a85b076128',
      1 => 
      array (
        0 => 'app\\console\\commands\\purgeexpiredpermissions',
      ),
      2 => 
      array (
        0 => 'app\\console\\commands\\handle',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Events\\RoleAssigned.php' => 
    array (
      0 => '4d860b89eda4700cd4b865245137dd5d4f3e2e5dc3d7f0d7d9de04027920c058',
      1 => 
      array (
        0 => 'app\\domain\\access\\events\\roleassigned',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\events\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Events\\RolePermissionsChanged.php' => 
    array (
      0 => '9527b661900df9f326620398140259417e0cc01442263d00ed7be2d3f3f46073',
      1 => 
      array (
        0 => 'app\\domain\\access\\events\\rolepermissionschanged',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\events\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Events\\RoleRevoked.php' => 
    array (
      0 => 'a03f751c79047bb93c13eeb8650f66095f544cdbec7258de8b31d29221c849d6',
      1 => 
      array (
        0 => 'app\\domain\\access\\events\\rolerevoked',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\events\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Events\\UserDeactivated.php' => 
    array (
      0 => 'd5da60897b0641051a68d922b0dcb6045aac26ce16fc2587ee6464da02954829',
      1 => 
      array (
        0 => 'app\\domain\\access\\events\\userdeactivated',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\events\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Events\\UserPermissionChanged.php' => 
    array (
      0 => 'b9124754f2d207c019acd6f5a68896799b0e8e85c19a714c209bc41d7d46e8cf',
      1 => 
      array (
        0 => 'app\\domain\\access\\events\\userpermissionchanged',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\events\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Listeners\\PermissionCacheInvalidator.php' => 
    array (
      0 => '2cbca7030f680f4b49395ff02c572f32938b4829dd0c9a90e6810e5d60735eaf',
      1 => 
      array (
        0 => 'app\\domain\\access\\listeners\\permissioncacheinvalidator',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\listeners\\__construct',
        1 => 'app\\domain\\access\\listeners\\onuserchanged',
        2 => 'app\\domain\\access\\listeners\\onrolepermissionschanged',
        3 => 'app\\domain\\access\\listeners\\subscribe',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\UserPermissionController.php' => 
    array (
      0 => 'f4a0ca2a2dc93ba39fe8437889909ec81e04c6b07cba0be54ec8856258b2aaac',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\userpermissioncontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\__construct',
        1 => 'app\\http\\controllers\\api\\v1\\index',
        2 => 'app\\http\\controllers\\api\\v1\\store',
        3 => 'app\\http\\controllers\\api\\v1\\destroy',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\StoreUserPermissionRequest.php' => 
    array (
      0 => 'f84135eb7f02ce2a77489543601f8f303657898e3e036b5e5625ab513e4c9d65',
      1 => 
      array (
        0 => 'app\\http\\requests\\storeuserpermissionrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Models\\UserPermissionPivot.php' => 
    array (
      0 => '7ea6ad6c5348f3cbef65ae2d6f702ec87414f18a8463825053c9027cf2ea240b',
      1 => 
      array (
        0 => 'app\\domain\\access\\models\\userpermissionpivot',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\models\\casts',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Contracts\\UserRepositoryInterface.php' => 
    array (
      0 => '4c36f7cd1fe0e908b365b1a82b23bb178742969a1ba91b082e7e63dcedeca5fd',
      1 => 
      array (
        0 => 'app\\domain\\access\\contracts\\userrepositoryinterface',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\contracts\\create',
        1 => 'app\\domain\\access\\contracts\\update',
        2 => 'app\\domain\\access\\contracts\\otheractiveadminscount',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\DTOs\\UserData.php' => 
    array (
      0 => '33ac2588b9b6a8fb4473155be48ba8789d876861243dbce65a38ded4cfa2d361',
      1 => 
      array (
        0 => 'app\\domain\\access\\dtos\\userdata',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\dtos\\__construct',
        1 => 'app\\domain\\access\\dtos\\fromarray',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Exceptions\\UserManagementException.php' => 
    array (
      0 => 'e0bb8b67836d64b135fd6115a04e30c4074c7c5740310de316a43592b41eb8f6',
      1 => 
      array (
        0 => 'app\\domain\\access\\exceptions\\usermanagementexception',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\exceptions\\warehouserequired',
        1 => 'app\\domain\\access\\exceptions\\cannotdeactivateself',
        2 => 'app\\domain\\access\\exceptions\\cannoteditownroles',
        3 => 'app\\domain\\access\\exceptions\\lastactiveadmin',
        4 => 'app\\domain\\access\\exceptions\\roleaboveownrank',
        5 => 'app\\domain\\access\\exceptions\\vehiclehasseller',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Actions\\AssignRolesAction.php' => 
    array (
      0 => 'e1ebe649709041d464fd4bc251aa936ad72a7915510f3b905b944cd4375249aa',
      1 => 
      array (
        0 => 'app\\domain\\access\\actions\\assignrolesaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\actions\\execute',
        1 => 'app\\domain\\access\\actions\\maxlevel',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Events\\UserCreated.php' => 
    array (
      0 => '6444e2c22298000ed9f4611785594c5ac42ba892bf6697c4254f8b38deb1101e',
      1 => 
      array (
        0 => 'app\\domain\\access\\events\\usercreated',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\events\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Repositories\\UserRepository.php' => 
    array (
      0 => '4cdebdd8485655447702a48b11450d50a36c508d2ba02b5a3c9b307585280154',
      1 => 
      array (
        0 => 'app\\domain\\access\\repositories\\userrepository',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\repositories\\create',
        1 => 'app\\domain\\access\\repositories\\update',
        2 => 'app\\domain\\access\\repositories\\otheractiveadminscount',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Actions\\CreateUserAction.php' => 
    array (
      0 => 'dd919784a0ffe53d08e089658f962580b920a2f101c99aaff69a1ad19ace27dc',
      1 => 
      array (
        0 => 'app\\domain\\access\\actions\\createuseraction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\actions\\__construct',
        1 => 'app\\domain\\access\\actions\\execute',
        2 => 'app\\domain\\access\\actions\\assertwarehouseconsistency',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Actions\\ResetUserPasswordAction.php' => 
    array (
      0 => '7d413b784fbe18b644c791155462319202bd582fd886fe0bb1779141433d490f',
      1 => 
      array (
        0 => 'app\\domain\\access\\actions\\resetuserpasswordaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Actions\\ToggleUserAction.php' => 
    array (
      0 => 'f3ecb89384cbb316444c464ea7a6722a209b6cf976195479c46b9b48da5f5a8e',
      1 => 
      array (
        0 => 'app\\domain\\access\\actions\\toggleuseraction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\actions\\__construct',
        1 => 'app\\domain\\access\\actions\\execute',
        2 => 'app\\domain\\access\\actions\\isadmin',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Actions\\UpdateUserAction.php' => 
    array (
      0 => '12d1276b021af23d1cce4f9c309efb5c99da3a6bb43fb73defd8bbd8f11df3f9',
      1 => 
      array (
        0 => 'app\\domain\\access\\actions\\updateuseraction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\actions\\__construct',
        1 => 'app\\domain\\access\\actions\\execute',
        2 => 'app\\domain\\access\\actions\\assertwarehouseconsistency',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Notifications\\UserInvitationNotification.php' => 
    array (
      0 => '0983407f2b907ef702e72a232d7da0d6fa6eda06b98fe7a0245713a57f61d115',
      1 => 
      array (
        0 => 'app\\domain\\access\\notifications\\userinvitationnotification',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\notifications\\__construct',
        1 => 'app\\domain\\access\\notifications\\via',
        2 => 'app\\domain\\access\\notifications\\tomail',
        3 => 'app\\domain\\access\\notifications\\tokenfor',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\UserController.php' => 
    array (
      0 => '36398b6b43c349b320f044d97809a93e5b7a88d7f02ac0b62678f51327e4fc16',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\usercontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\perpage',
        1 => 'app\\http\\controllers\\api\\v1\\index',
        2 => 'app\\http\\controllers\\api\\v1\\store',
        3 => 'app\\http\\controllers\\api\\v1\\show',
        4 => 'app\\http\\controllers\\api\\v1\\update',
        5 => 'app\\http\\controllers\\api\\v1\\toggle',
        6 => 'app\\http\\controllers\\api\\v1\\roles',
        7 => 'app\\http\\controllers\\api\\v1\\resendinvitation',
        8 => 'app\\http\\controllers\\api\\v1\\forcepasswordreset',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\AssignRolesRequest.php' => 
    array (
      0 => '8a76a5d2350e587b8e76fdfde70aad3b5b0a79732ec47295bc7dc958e10068e5',
      1 => 
      array (
        0 => 'app\\http\\requests\\assignrolesrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\StoreUserRequest.php' => 
    array (
      0 => '905e84c9617bb02aae233c982396809244731e915f656a4564972561b3e189f1',
      1 => 
      array (
        0 => 'app\\http\\requests\\storeuserrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\UpdateUserRequest.php' => 
    array (
      0 => '7f6f2c9da75e356a060f3be5f9706f0951e31882370b5c4cb1fce559c24dafe9',
      1 => 
      array (
        0 => 'app\\http\\requests\\updateuserrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Resources\\AdminUserResource.php' => 
    array (
      0 => '95dc69c48ae424fbbdafb976df8949dc8706b0f47845dbedabc87679dce0a759',
      1 => 
      array (
        0 => 'app\\http\\resources\\adminuserresource',
      ),
      2 => 
      array (
        0 => 'app\\http\\resources\\toarray',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Policies\\UserPolicy.php' => 
    array (
      0 => 'e8c90cae5a75ffec4c5687f5ec2fb658243fe36b019af2d672a327ad41ce3125',
      1 => 
      array (
        0 => 'app\\policies\\userpolicy',
      ),
      2 => 
      array (
        0 => 'app\\policies\\viewany',
        1 => 'app\\policies\\view',
        2 => 'app\\policies\\create',
        3 => 'app\\policies\\update',
        4 => 'app\\policies\\deactivate',
        5 => 'app\\policies\\assignroles',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Actions\\SetRolePermissionsAction.php' => 
    array (
      0 => 'd42427f1f169550219ea5a6a06d17b953af8ff02998dfdeda9fb2f44c4ea0e19',
      1 => 
      array (
        0 => 'app\\domain\\access\\actions\\setrolepermissionsaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\actions\\execute',
        1 => 'app\\domain\\access\\actions\\assertcriticalpermissionspreserved',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\CriticalPermissions.php' => 
    array (
      0 => '963fe1d8991e78c9aef0351d4837e3f3e29ece0b29bd27b32f39eeac97358942',
      1 => 
      array (
        0 => 'app\\domain\\access\\criticalpermissions',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Exceptions\\RoleManagementException.php' => 
    array (
      0 => '242d721b776e977ee86942ff5a5b73d34559a78ffaf2a2206686bfa05af37e2e',
      1 => 
      array (
        0 => 'app\\domain\\access\\exceptions\\rolemanagementexception',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\exceptions\\systemrole',
        1 => 'app\\domain\\access\\exceptions\\roleinuse',
        2 => 'app\\domain\\access\\exceptions\\wouldlockoutadmins',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Console\\Commands\\DetectBrands.php' => 
    array (
      0 => '331f914f5f992062e221b2d44667764b320c0c0b7810d7082364738cef166c3f',
      1 => 
      array (
        0 => 'app\\console\\commands\\detectbrands',
      ),
      2 => 
      array (
        0 => 'app\\console\\commands\\handle',
        1 => 'app\\console\\commands\\normalize',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Actions\\DeleteRoleAction.php' => 
    array (
      0 => '11a80b6288481960643662196aef3ed34650ba9f1af7827d0c8a367a38ffc611',
      1 => 
      array (
        0 => 'app\\domain\\access\\actions\\deleteroleaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Actions\\DuplicateRoleAction.php' => 
    array (
      0 => 'b4b9a8be95cf46e1cb7d8f4ae61813c198c02acd12d5d029f6c63569f27aa923',
      1 => 
      array (
        0 => 'app\\domain\\access\\actions\\duplicateroleaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Actions\\DeleteBrandAction.php' => 
    array (
      0 => 'a88acb1bb78a0d3c18d149da84b86a46fd0021bd19396532942051e6765e7e8d',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\actions\\deletebrandaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Actions\\DeleteUnitAction.php' => 
    array (
      0 => '531ac6d193faaaf7cb86feea61c2f29d9f35e428a40e3af48df08e472e17dabd',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\actions\\deleteunitaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Actions\\SaveUnitAction.php' => 
    array (
      0 => '2bc5ffb41b417201c2886cc2e36e5d8b61583a266fa9a5d4708ef7234fd32b5b',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\actions\\saveunitaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\actions\\create',
        1 => 'app\\domain\\catalog\\actions\\update',
        2 => 'app\\domain\\catalog\\actions\\hasdecimalmovements',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Exceptions\\BrandInUseException.php' => 
    array (
      0 => 'd063de4ee50d9580e454b476d1e40ef4ccd96fa0dc5a503277f8b61a016ec840',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\exceptions\\brandinuseexception',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\exceptions\\usedbyproducts',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Exceptions\\UnitInUseException.php' => 
    array (
      0 => 'a4da0f18a749f36581cdae50776fba2895ce3d4fa1c7376a082fe47a7d73ac95',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\exceptions\\unitinuseexception',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\exceptions\\usedbyproducts',
        1 => 'app\\domain\\catalog\\exceptions\\decimalmovementsexist',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\BrandController.php' => 
    array (
      0 => 'af747dca9ce97d22c7f0ae958effbbe85395cf36d72ee8d93df41b1ed6162961',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\brandcontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\index',
        1 => 'app\\http\\controllers\\api\\v1\\store',
        2 => 'app\\http\\controllers\\api\\v1\\update',
        3 => 'app\\http\\controllers\\api\\v1\\destroy',
        4 => 'app\\http\\controllers\\api\\v1\\logo',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\PermissionController.php' => 
    array (
      0 => '844c618cffb9e9da444e478b8430712e8edafb3a610fd79597e4896f86e0f891',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\permissioncontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\index',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\RoleController.php' => 
    array (
      0 => 'e7c2422ab44ac0458be8eea2ba1f6af3fedd71e2fc3e7d1d280afb55bf615021',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\rolecontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\index',
        1 => 'app\\http\\controllers\\api\\v1\\store',
        2 => 'app\\http\\controllers\\api\\v1\\update',
        3 => 'app\\http\\controllers\\api\\v1\\destroy',
        4 => 'app\\http\\controllers\\api\\v1\\permissions',
        5 => 'app\\http\\controllers\\api\\v1\\duplicate',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\UnitController.php' => 
    array (
      0 => '3a9fc80a675834d71cbeb96eea3e839aa12b9847fbbe0dd832133ea5a7ace6a4',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\unitcontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\index',
        1 => 'app\\http\\controllers\\api\\v1\\store',
        2 => 'app\\http\\controllers\\api\\v1\\update',
        3 => 'app\\http\\controllers\\api\\v1\\destroy',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\DuplicateRoleRequest.php' => 
    array (
      0 => '6279546f3c310f754087a904b397f0c6904deb44118622b336cd1ff14a0c980a',
      1 => 
      array (
        0 => 'app\\http\\requests\\duplicaterolerequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\SetRolePermissionsRequest.php' => 
    array (
      0 => '42896df9629b3b69bfcd7ab79669bdc053752bbdda9a7f844e1f1f5101cb7bf6',
      1 => 
      array (
        0 => 'app\\http\\requests\\setrolepermissionsrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\StoreBrandRequest.php' => 
    array (
      0 => '9f47d3f5f613f85791ad38230881666b3e07ec9780b41704a8e351886e778b5b',
      1 => 
      array (
        0 => 'app\\http\\requests\\storebrandrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\StoreRoleRequest.php' => 
    array (
      0 => '05d314735a77560f715447419b27e97848c0c2d223c7eca22a1811c063355c85',
      1 => 
      array (
        0 => 'app\\http\\requests\\storerolerequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\StoreUnitRequest.php' => 
    array (
      0 => '372bce811c10ddc2ebd02735dc56cb3d86bb2d0162a1d29a79101ddd6dc1002c',
      1 => 
      array (
        0 => 'app\\http\\requests\\storeunitrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\UpdateBrandRequest.php' => 
    array (
      0 => '3fd447aac754cb916018c36afee251fea9c579eca75baa745939cd6bc396766e',
      1 => 
      array (
        0 => 'app\\http\\requests\\updatebrandrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\UpdateRoleRequest.php' => 
    array (
      0 => 'af8a0ed0224eeee4e1167b65aa1ea480ff75ee9e1883227f796d9130a7044a4f',
      1 => 
      array (
        0 => 'app\\http\\requests\\updaterolerequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\UpdateUnitRequest.php' => 
    array (
      0 => '87f36a99241e421ed901c83b988023f18ffd3026c6cc3a453fa07ded999e9a66',
      1 => 
      array (
        0 => 'app\\http\\requests\\updateunitrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Resources\\BrandResource.php' => 
    array (
      0 => '68ecd2620178e62cb20ca3b197a2e41c4890762ff91e2c60f6430a70cf6c1452',
      1 => 
      array (
        0 => 'app\\http\\resources\\brandresource',
      ),
      2 => 
      array (
        0 => 'app\\http\\resources\\toarray',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Resources\\RoleResource.php' => 
    array (
      0 => '3c16714333800806af18da6137579a70295a4666c99939741b3a3f024101ff00',
      1 => 
      array (
        0 => 'app\\http\\resources\\roleresource',
      ),
      2 => 
      array (
        0 => 'app\\http\\resources\\toarray',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Resources\\UnitResource.php' => 
    array (
      0 => '9e9522bc3216b8d3bfb5ef7e1a9b4598394bfd6ca0fcec908ee1e4ddde2d6ef0',
      1 => 
      array (
        0 => 'app\\http\\resources\\unitresource',
      ),
      2 => 
      array (
        0 => 'app\\http\\resources\\toarray',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Actions\\ReorderCategoriesAction.php' => 
    array (
      0 => '4f4fc54863356ffd5cfa93f345c4a91de78f95e3e762424d12acf3671a39d799',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\actions\\reordercategoriesaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\actions\\execute',
        1 => 'app\\domain\\catalog\\actions\\assertvalid',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Exceptions\\CategoryReorderException.php' => 
    array (
      0 => '265a0ae24fef10392aea8ed6981d01f56bf0418f4a27b77fe597d86404d5ae49',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\exceptions\\categoryreorderexception',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\exceptions\\selfparent',
        1 => 'app\\domain\\catalog\\exceptions\\depthexceeded',
        2 => 'app\\domain\\catalog\\exceptions\\parenthaschildren',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\ReorderCategoriesRequest.php' => 
    array (
      0 => '8ef5dd31d90bf101008254cce7a06c13d7a40ca7a4b152c519ecf4b5cf37004f',
      1 => 
      array (
        0 => 'app\\http\\requests\\reordercategoriesrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Actions\\WarehouseAssignmentGuard.php' => 
    array (
      0 => 'e98c5a89b240d822ded13c3974036e0a0f4f3f881f8f2d24838b79227525ba40',
      1 => 
      array (
        0 => 'app\\domain\\access\\actions\\warehouseassignmentguard',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\actions\\assertvehiclefree',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Warehouses\\Actions\\ToggleWarehouseAction.php' => 
    array (
      0 => '0cd8cb1d514b1bebfa7167b3c0a3ca91310388d2824caafb13f1e89da2935a26',
      1 => 
      array (
        0 => 'app\\domain\\warehouses\\actions\\togglewarehouseaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\warehouses\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Warehouses\\Exceptions\\WarehouseInUseException.php' => 
    array (
      0 => '21ac6c294254720d50095d7222e3b965da856a4bf33139093feb54de22881968',
      1 => 
      array (
        0 => 'app\\domain\\warehouses\\exceptions\\warehouseinuseexception',
      ),
      2 => 
      array (
        0 => 'app\\domain\\warehouses\\exceptions\\stocknotempty',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Models\\TaxRate.php' => 
    array (
      0 => '9054a0da029599d907fa6851b1b04586a87c87b5b22ec2807fb3f64404129bb4',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\models\\taxrate',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\models\\casts',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\TaxRateController.php' => 
    array (
      0 => '6724f15c507577b2f57335ababf453a7ba2b8f3f5430edecfa86b994ac347586',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\taxratecontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\index',
        1 => 'app\\http\\controllers\\api\\v1\\store',
        2 => 'app\\http\\controllers\\api\\v1\\update',
        3 => 'app\\http\\controllers\\api\\v1\\destroy',
        4 => 'app\\http\\controllers\\api\\v1\\ensuresingledefault',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\SaveTaxRateRequest.php' => 
    array (
      0 => '443347fcd840a343a094023ec122aaf8f1af4dcf91f61a24ea057eaff4c9e008',
      1 => 
      array (
        0 => 'app\\http\\requests\\savetaxraterequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Resources\\TaxRateResource.php' => 
    array (
      0 => 'b7890982f4aabe5766e8ac52b2c78aaf961a7c80f69240475eb9d0ed93ded453',
      1 => 
      array (
        0 => 'app\\http\\resources\\taxrateresource',
      ),
      2 => 
      array (
        0 => 'app\\http\\resources\\toarray',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Purchasing\\Models\\Supplier.php' => 
    array (
      0 => 'aa7993b205c536f4a095971468a3777c9c1a98a5d91cc69aace53e6819c9774e',
      1 => 
      array (
        0 => 'app\\domain\\purchasing\\models\\supplier',
      ),
      2 => 
      array (
        0 => 'app\\domain\\purchasing\\models\\contacts',
        1 => 'app\\domain\\purchasing\\models\\products',
        2 => 'app\\domain\\purchasing\\models\\casts',
        3 => 'app\\domain\\purchasing\\models\\newfactory',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\SupplierController.php' => 
    array (
      0 => '9cbe866e16669fdf62e62c19c4138cf5ef9f5711f5a8a4c4d54b0d3a4a6a6201',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\suppliercontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\perpage',
        1 => 'app\\http\\controllers\\api\\v1\\index',
        2 => 'app\\http\\controllers\\api\\v1\\store',
        3 => 'app\\http\\controllers\\api\\v1\\show',
        4 => 'app\\http\\controllers\\api\\v1\\update',
        5 => 'app\\http\\controllers\\api\\v1\\destroy',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\StoreSupplierRequest.php' => 
    array (
      0 => '4b1649124dcbd4e25fdfa9a043530e103323edae5ba0d632f65991530cbc9b88',
      1 => 
      array (
        0 => 'app\\http\\requests\\storesupplierrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\UpdateSupplierRequest.php' => 
    array (
      0 => '9de46913ecb4a85f127c838853b899faf46b9cff6bbbff87a2ba0eef36712ab7',
      1 => 
      array (
        0 => 'app\\http\\requests\\updatesupplierrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Resources\\SupplierResource.php' => 
    array (
      0 => '5c94235e383cb78426975ad7680f918ae15195a3734aa3f11c30359a2201b9f9',
      1 => 
      array (
        0 => 'app\\http\\resources\\supplierresource',
      ),
      2 => 
      array (
        0 => 'app\\http\\resources\\toarray',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Customers\\Models\\Customer.php' => 
    array (
      0 => '21ef7a6f25a369bdf869d2b6592e1d4985fbd23c125cffee1d0ed44627832eb2',
      1 => 
      array (
        0 => 'app\\domain\\customers\\models\\customer',
      ),
      2 => 
      array (
        0 => 'app\\domain\\customers\\models\\casts',
        1 => 'app\\domain\\customers\\models\\newfactory',
        2 => 'app\\domain\\customers\\models\\availablecredit',
        3 => 'app\\domain\\customers\\models\\pricetype',
        4 => 'app\\domain\\customers\\models\\seller',
        5 => 'app\\domain\\customers\\models\\warehouse',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\CustomerController.php' => 
    array (
      0 => '323dd09bc33362e2b987a013ecfaeb2f2541647ef597b23b656bb726d7ff037a',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\customercontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\perpage',
        1 => 'app\\http\\controllers\\api\\v1\\index',
        2 => 'app\\http\\controllers\\api\\v1\\store',
        3 => 'app\\http\\controllers\\api\\v1\\show',
        4 => 'app\\http\\controllers\\api\\v1\\update',
        5 => 'app\\http\\controllers\\api\\v1\\destroy',
        6 => 'app\\http\\controllers\\api\\v1\\setcreditlimit',
        7 => 'app\\http\\controllers\\api\\v1\\toggleblock',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\SetCreditLimitRequest.php' => 
    array (
      0 => '35a780c3c01e1789f023c9a402b63048a65e122c549c0a15e6e644c06f5a3dab',
      1 => 
      array (
        0 => 'app\\http\\requests\\setcreditlimitrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\StoreCustomerRequest.php' => 
    array (
      0 => '40b6206e6ff9870e25fe2d5b6bd117fc0dbe3e00fecd645fc8374d319083c4df',
      1 => 
      array (
        0 => 'app\\http\\requests\\storecustomerrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\UpdateCustomerRequest.php' => 
    array (
      0 => '00461b4123d1224fdf52e6397e07296b660206870ae139a091e42e98961ba749',
      1 => 
      array (
        0 => 'app\\http\\requests\\updatecustomerrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Resources\\CustomerResource.php' => 
    array (
      0 => '68cfb0f97df03ae464937aded4ca155f167f081888b1c68dc5bac76ca2c84c4f',
      1 => 
      array (
        0 => 'app\\http\\resources\\customerresource',
      ),
      2 => 
      array (
        0 => 'app\\http\\resources\\toarray',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Actions\\IssueStockAction.php' => 
    array (
      0 => 'cbff166158ad45ef0d1e08f83e759ef38e2d941670ddf4bc4d023ac077d7af42',
      1 => 
      array (
        0 => 'app\\domain\\stock\\actions\\issuestockaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\actions\\__construct',
        1 => 'app\\domain\\stock\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\StockController.php' => 
    array (
      0 => 'e5ba19d3a4cb594e689295e5e8096e5b715ad673136470d2f2bc1584c02788a5',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\stockcontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\perpage',
        1 => 'app\\http\\controllers\\api\\v1\\index',
        2 => 'app\\http\\controllers\\api\\v1\\movements',
        3 => 'app\\http\\controllers\\api\\v1\\movementtypes',
        4 => 'app\\http\\controllers\\api\\v1\\export',
        5 => 'app\\http\\controllers\\api\\v1\\matrix',
        6 => 'app\\http\\controllers\\api\\v1\\matrixexport',
        7 => 'app\\http\\controllers\\api\\v1\\quantitiesbyproductandwarehouse',
        8 => 'app\\http\\controllers\\api\\v1\\entry',
        9 => 'app\\http\\controllers\\api\\v1\\issue',
        10 => 'app\\http\\controllers\\api\\v1\\adjust',
        11 => 'app\\http\\controllers\\api\\v1\\returnin',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\StockIssueRequest.php' => 
    array (
      0 => '4d1729ba214a660f891e6910ead8cab60a1155113b2fb6e3f8c3ef13f81361d1',
      1 => 
      array (
        0 => 'app\\http\\requests\\stockissuerequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Actions\\ApproveInventoryAction.php' => 
    array (
      0 => 'a9dea0c46b61ba29d6dbae2df56257f382f7ac91eacbf5290f4df33ce1de3a57',
      1 => 
      array (
        0 => 'app\\domain\\stock\\actions\\approveinventoryaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\actions\\__construct',
        1 => 'app\\domain\\stock\\actions\\execute',
        2 => 'app\\domain\\stock\\actions\\regularize',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Stock\\Actions\\EntryStockAction.php' => 
    array (
      0 => '29feff0fae2f40c029e621a12d6ced8601539ca61318a4bc5585780a2a6562f9',
      1 => 
      array (
        0 => 'app\\domain\\stock\\actions\\entrystockaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\stock\\actions\\__construct',
        1 => 'app\\domain\\stock\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\InventoryController.php' => 
    array (
      0 => '77941381e60cfc5617c3b7150ec139e720c7ed289b89ba034f1feabec33a113b',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\inventorycontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\index',
        1 => 'app\\http\\controllers\\api\\v1\\store',
        2 => 'app\\http\\controllers\\api\\v1\\show',
        3 => 'app\\http\\controllers\\api\\v1\\sheet',
        4 => 'app\\http\\controllers\\api\\v1\\savelines',
        5 => 'app\\http\\controllers\\api\\v1\\cancel',
        6 => 'app\\http\\controllers\\api\\v1\\approve',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\SaveInventoryLinesRequest.php' => 
    array (
      0 => '9e5ea29e017b8b8b506bada121bac26d8a78ecec18798746a810042b022e4a43',
      1 => 
      array (
        0 => 'app\\http\\requests\\saveinventorylinesrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\StockEntryRequest.php' => 
    array (
      0 => 'f30cc0f9ef8bdfdf3316308568c75aba068b09b09d3f80bfa3febf5f29f390f7',
      1 => 
      array (
        0 => 'app\\http\\requests\\stockentryrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\StoreInventoryRequest.php' => 
    array (
      0 => '03aab60e9adbd9a9b217d1eef1ead5750e44e43b3f9a43c0ef37693923a8110a',
      1 => 
      array (
        0 => 'app\\http\\requests\\storeinventoryrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Resources\\InventoryResource.php' => 
    array (
      0 => '29afcf5227d22044377b2e1746551a9fb42e2bbfb66e24185ada2f6864dfb371',
      1 => 
      array (
        0 => 'app\\http\\resources\\inventoryresource',
      ),
      2 => 
      array (
        0 => 'app\\http\\resources\\toarray',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Contracts\\AuditLoggerInterface.php' => 
    array (
      0 => '40b8ad412ab6bd8f6af413904d9752528a11717b331f73579d9b64d26374bad5',
      1 => 
      array (
        0 => 'app\\domain\\access\\contracts\\auditloggerinterface',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\contracts\\log',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Listeners\\AuditEventSubscriber.php' => 
    array (
      0 => 'd3642a68a7f6299f1feff693ac0059fd2bd55b28eec02400040b3449a3a1ab67',
      1 => 
      array (
        0 => 'app\\domain\\access\\listeners\\auditeventsubscriber',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\listeners\\__construct',
        1 => 'app\\domain\\access\\listeners\\onusercreated',
        2 => 'app\\domain\\access\\listeners\\onuserdeactivated',
        3 => 'app\\domain\\access\\listeners\\onuserpermissionchanged',
        4 => 'app\\domain\\access\\listeners\\onroleassigned',
        5 => 'app\\domain\\access\\listeners\\onrolerevoked',
        6 => 'app\\domain\\access\\listeners\\onrolepermissionschanged',
        7 => 'app\\domain\\access\\listeners\\useraction',
        8 => 'app\\domain\\access\\listeners\\subscribe',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Models\\AuditLog.php' => 
    array (
      0 => 'd2d9b759aa3571a505b98e386277768b997a0595834cd95dcd29493924d0ceae',
      1 => 
      array (
        0 => 'app\\domain\\access\\models\\auditlog',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\models\\user',
        1 => 'app\\domain\\access\\models\\casts',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Services\\AuditLogger.php' => 
    array (
      0 => 'dd4d208638999ce5f0f916abd511d477a32144a54bead34f8d2fb6aa6c0b7f7d',
      1 => 
      array (
        0 => 'app\\domain\\access\\services\\auditlogger',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\services\\__construct',
        1 => 'app\\domain\\access\\services\\log',
        2 => 'app\\domain\\access\\services\\modulefromaction',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Access\\Support\\PasswordPolicy.php' => 
    array (
      0 => 'b105d3c73bb523e7bffdec26a197f1c97c0b9cb871086eab3437f6674a452506',
      1 => 
      array (
        0 => 'app\\domain\\access\\support\\passwordpolicy',
      ),
      2 => 
      array (
        0 => 'app\\domain\\access\\support\\rule',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Settings\\Contracts\\SettingsRepositoryInterface.php' => 
    array (
      0 => '482bdc388aab614cc8e8a6d4e0124eec07ebcfd7c49a393dd3e087ae5adb87ca',
      1 => 
      array (
        0 => 'app\\domain\\settings\\contracts\\settingsrepositoryinterface',
      ),
      2 => 
      array (
        0 => 'app\\domain\\settings\\contracts\\get',
        1 => 'app\\domain\\settings\\contracts\\allgrouped',
        2 => 'app\\domain\\settings\\contracts\\setmany',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Settings\\Models\\DocumentSequence.php' => 
    array (
      0 => '1f1c5c1d050133ed0c1ebf17783d8ce3db25eab785a26fe09f2ef79618f56e3d',
      1 => 
      array (
        0 => 'app\\domain\\settings\\models\\documentsequence',
      ),
      2 => 
      array (
        0 => 'app\\domain\\settings\\models\\casts',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Settings\\Models\\PaymentMethod.php' => 
    array (
      0 => '2bdb2127da914e317f8d3e5175eefb94865d4d8b0074c89c7e56e09876a7d7a6',
      1 => 
      array (
        0 => 'app\\domain\\settings\\models\\paymentmethod',
      ),
      2 => 
      array (
        0 => 'app\\domain\\settings\\models\\casts',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Settings\\Models\\Setting.php' => 
    array (
      0 => '4ba1203ef31958a5d227e6ef456968861bb6261503016617e46dd69646954482',
      1 => 
      array (
        0 => 'app\\domain\\settings\\models\\setting',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Settings\\Repositories\\SettingsRepository.php' => 
    array (
      0 => '96b9828fe119aef07517e28007232d4c58fba6751d94798e2adc1bfc4d665ab0',
      1 => 
      array (
        0 => 'app\\domain\\settings\\repositories\\settingsrepository',
      ),
      2 => 
      array (
        0 => 'app\\domain\\settings\\repositories\\get',
        1 => 'app\\domain\\settings\\repositories\\allgrouped',
        2 => 'app\\domain\\settings\\repositories\\setmany',
        3 => 'app\\domain\\settings\\repositories\\raw',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Settings\\SettingsCatalog.php' => 
    array (
      0 => 'a548322b3c59faf156a41da889581a7ec7bf012b7a3b5f1f2717394e6bb94163',
      1 => 
      array (
        0 => 'app\\domain\\settings\\settingscatalog',
      ),
      2 => 
      array (
        0 => 'app\\domain\\settings\\isknown',
        1 => 'app\\domain\\settings\\cast',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\AuditController.php' => 
    array (
      0 => '78db59ac0a72e3556013ab67e69ef52d7249a16040d044aef53b337e7921bb13',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\auditcontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\index',
        1 => 'app\\http\\controllers\\api\\v1\\filters',
        2 => 'app\\http\\controllers\\api\\v1\\export',
        3 => 'app\\http\\controllers\\api\\v1\\filtered',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\BackupController.php' => 
    array (
      0 => '8226025ce9284be3fa734973575092134dfee5c4fcbab48fe5871a7cf5f2975f',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\backupcontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\download',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\DocumentSequenceController.php' => 
    array (
      0 => 'cef53270f01b792ba947ae6e633ff5f7ed4ba3dd8328eeac9cbb653306c9c8fb',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\documentsequencecontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\index',
        1 => 'app\\http\\controllers\\api\\v1\\update',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\PaymentMethodController.php' => 
    array (
      0 => '6dc1b90347225213c8a0f2334e4b4019170155f68de7381cc11c231427c8c5d3',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\paymentmethodcontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\index',
        1 => 'app\\http\\controllers\\api\\v1\\store',
        2 => 'app\\http\\controllers\\api\\v1\\update',
        3 => 'app\\http\\controllers\\api\\v1\\destroy',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\SessionController.php' => 
    array (
      0 => '6ce8f80b148d21a31ac3c87f765113334ea56c0b6ffa65a5bff65ce2e70a2f6d',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\sessioncontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\__construct',
        1 => 'app\\http\\controllers\\api\\v1\\index',
        2 => 'app\\http\\controllers\\api\\v1\\destroy',
        3 => 'app\\http\\controllers\\api\\v1\\destroyforuser',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\SettingController.php' => 
    array (
      0 => 'd9107c759a31faa48d95d74a3f683f53e96244247d190b8a4c6950c5142e5611',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\settingcontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\__construct',
        1 => 'app\\http\\controllers\\api\\v1\\index',
        2 => 'app\\http\\controllers\\api\\v1\\update',
        3 => 'app\\http\\controllers\\api\\v1\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\AssignWarehouseUsersRequest.php' => 
    array (
      0 => '5ddfc4475096ef85b632bf6764367d88a3507e038f953ac293256e554e52fcb8',
      1 => 
      array (
        0 => 'app\\http\\requests\\assignwarehouseusersrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Requests\\SavePaymentMethodRequest.php' => 
    array (
      0 => 'dfa1b2307ed1973c55705dc1842f5c63fa16778a1e2cb57e14d6245c5695205d',
      1 => 
      array (
        0 => 'app\\http\\requests\\savepaymentmethodrequest',
      ),
      2 => 
      array (
        0 => 'app\\http\\requests\\authorize',
        1 => 'app\\http\\requests\\rules',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Resources\\AuditLogResource.php' => 
    array (
      0 => 'a4c9145a77ea49fb0aea5ad14d41980e7dc90ae5cdcd243547afea53920d2350',
      1 => 
      array (
        0 => 'app\\http\\resources\\auditlogresource',
      ),
      2 => 
      array (
        0 => 'app\\http\\resources\\toarray',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Resources\\PaymentMethodResource.php' => 
    array (
      0 => 'd982d4cdec19f480b6f7a7d90013777723c09184d0380e086300bd52ce601a25',
      1 => 
      array (
        0 => 'app\\http\\resources\\paymentmethodresource',
      ),
      2 => 
      array (
        0 => 'app\\http\\resources\\toarray',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Providers\\Domain\\SettingsServiceProvider.php' => 
    array (
      0 => 'c6aad7c84847a548176f9431ea50f0c7f6f28c09a9a8e1704e3d990510e3fc39',
      1 => 
      array (
        0 => 'app\\providers\\domain\\settingsserviceprovider',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Models\\AttributeTemplate.php' => 
    array (
      0 => '95a5f87c7df9fe7b9503154f3b2ade68db89e1dd44b53b52daae45f67c07ad57',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\models\\attributetemplate',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\models\\category',
        1 => 'app\\domain\\catalog\\models\\casts',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Models\\ProductAttribute.php' => 
    array (
      0 => '9b39690d2286a1bb6080dc1fecc275419ecb3bbdd228c10bad1595982fd14a24',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\models\\productattribute',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\models\\product',
        1 => 'app\\domain\\catalog\\models\\casts',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Catalog\\Models\\ProductImage.php' => 
    array (
      0 => 'a363c7461587eb2c680e9c99aebf2f003975a78c8f4250ba65cdff981e30db09',
      1 => 
      array (
        0 => 'app\\domain\\catalog\\models\\productimage',
      ),
      2 => 
      array (
        0 => 'app\\domain\\catalog\\models\\product',
        1 => 'app\\domain\\catalog\\models\\casts',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Purchasing\\Models\\SupplierContact.php' => 
    array (
      0 => '83072f1a0fd1ceb81798751457ca7a7c7d43852adf8cf872af7ddb72af0e69c9',
      1 => 
      array (
        0 => 'app\\domain\\purchasing\\models\\suppliercontact',
      ),
      2 => 
      array (
        0 => 'app\\domain\\purchasing\\models\\supplier',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\ProductAttributeController.php' => 
    array (
      0 => 'a0e982c7ca2b9bef6df2e72d48bf9bb3dc9a53efe352b0cb57d1bc8aa6606da7',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\productattributecontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\index',
        1 => 'app\\http\\controllers\\api\\v1\\save',
        2 => 'app\\http\\controllers\\api\\v1\\savetemplate',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\ProductImageController.php' => 
    array (
      0 => 'bb3c7c15f6471820e43c082458ea2ce9782ef85bd5a3cda7e29d55a356cd70ad',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\productimagecontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\index',
        1 => 'app\\http\\controllers\\api\\v1\\store',
        2 => 'app\\http\\controllers\\api\\v1\\setmain',
        3 => 'app\\http\\controllers\\api\\v1\\destroy',
        4 => 'app\\http\\controllers\\api\\v1\\list',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\ProductSerialController.php' => 
    array (
      0 => '3a51f846a5ea48686b932944abfaefe512a86aab07570a60200d3798930d0470',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\productserialcontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\index',
        1 => 'app\\http\\controllers\\api\\v1\\store',
        2 => 'app\\http\\controllers\\api\\v1\\destroy',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\SupplierContactController.php' => 
    array (
      0 => '44d94eb4d341bfcad48d2b8076b664887b42da855b40593114091e26a485e7b6',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\suppliercontactcontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\index',
        1 => 'app\\http\\controllers\\api\\v1\\store',
        2 => 'app\\http\\controllers\\api\\v1\\update',
        3 => 'app\\http\\controllers\\api\\v1\\destroy',
        4 => 'app\\http\\controllers\\api\\v1\\validated',
        5 => 'app\\http\\controllers\\api\\v1\\list',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\SupplierProductController.php' => 
    array (
      0 => '4bb425b17d3c8815d118dc59e1d4b6f062712b829fa8988a6e6074ca940f2786',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\supplierproductcontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\index',
        1 => 'app\\http\\controllers\\api\\v1\\attach',
        2 => 'app\\http\\controllers\\api\\v1\\detach',
        3 => 'app\\http\\controllers\\api\\v1\\stats',
        4 => 'app\\http\\controllers\\api\\v1\\list',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Customers\\Models\\CustomerLedgerEntry.php' => 
    array (
      0 => '6d5328e54ab772d60835d361025b187ebb9057a3ce297a5d3d22b9f8325ec45a',
      1 => 
      array (
        0 => 'app\\domain\\customers\\models\\customerledgerentry',
      ),
      2 => 
      array (
        0 => 'app\\domain\\customers\\models\\casts',
        1 => 'app\\domain\\customers\\models\\customer',
        2 => 'app\\domain\\customers\\models\\user',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Customers\\Services\\CustomerLedger.php' => 
    array (
      0 => 'e88c552a43800584e947deaad076c5dc2affb20bdeb1c49962902327ae7194d1',
      1 => 
      array (
        0 => 'app\\domain\\customers\\services\\customerledger',
      ),
      2 => 
      array (
        0 => 'app\\domain\\customers\\services\\record',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Expenses\\Models\\Expense.php' => 
    array (
      0 => 'b4b144ce8087bf4b7dcffb106c4db3ad305ee8d416200432305db2c754ecedd2',
      1 => 
      array (
        0 => 'app\\domain\\expenses\\models\\expense',
      ),
      2 => 
      array (
        0 => 'app\\domain\\expenses\\models\\casts',
        1 => 'app\\domain\\expenses\\models\\category',
        2 => 'app\\domain\\expenses\\models\\warehouse',
        3 => 'app\\domain\\expenses\\models\\user',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Expenses\\Models\\ExpenseCategory.php' => 
    array (
      0 => 'e00f445f37972dbb5da2bb2646bdc7210d35bb3d5425345de9e7ea690cab18ee',
      1 => 
      array (
        0 => 'app\\domain\\expenses\\models\\expensecategory',
      ),
      2 => 
      array (
        0 => 'app\\domain\\expenses\\models\\casts',
        1 => 'app\\domain\\expenses\\models\\expenses',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Purchasing\\Actions\\ReceivePurchaseOrderAction.php' => 
    array (
      0 => 'b1db33b339711f29c969c4b7c8aecb9d42575a9311075ed718ff7744c614c6d5',
      1 => 
      array (
        0 => 'app\\domain\\purchasing\\actions\\receivepurchaseorderaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\purchasing\\actions\\__construct',
        1 => 'app\\domain\\purchasing\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Purchasing\\Models\\PurchaseOrder.php' => 
    array (
      0 => '016846f0bc970b6c40a9e9afee0b65c5efa9285c556ee24ec86fb512c2a19d27',
      1 => 
      array (
        0 => 'app\\domain\\purchasing\\models\\purchaseorder',
      ),
      2 => 
      array (
        0 => 'app\\domain\\purchasing\\models\\casts',
        1 => 'app\\domain\\purchasing\\models\\supplier',
        2 => 'app\\domain\\purchasing\\models\\warehouse',
        3 => 'app\\domain\\purchasing\\models\\lines',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Purchasing\\Models\\PurchaseOrderLine.php' => 
    array (
      0 => 'b9eec9bbe268c35a5eb9a342bdd99dbfefcd696f2532fd8e0ca8969d5ab99799',
      1 => 
      array (
        0 => 'app\\domain\\purchasing\\models\\purchaseorderline',
      ),
      2 => 
      array (
        0 => 'app\\domain\\purchasing\\models\\casts',
        1 => 'app\\domain\\purchasing\\models\\purchaseorder',
        2 => 'app\\domain\\purchasing\\models\\product',
        3 => 'app\\domain\\purchasing\\models\\remaining',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Sales\\Actions\\CancelSaleAction.php' => 
    array (
      0 => '41350d05f9b349a4f0ea7b64dc6e832d80291cfedcb7376ccada8652b3a38526',
      1 => 
      array (
        0 => 'app\\domain\\sales\\actions\\cancelsaleaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\sales\\actions\\__construct',
        1 => 'app\\domain\\sales\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Sales\\Actions\\ConfirmSaleAction.php' => 
    array (
      0 => '55b4ea6021317c91b5534330b00e0649a4a450f0b730f2d6c8d7234a0db3ebdc',
      1 => 
      array (
        0 => 'app\\domain\\sales\\actions\\confirmsaleaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\sales\\actions\\__construct',
        1 => 'app\\domain\\sales\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Sales\\Actions\\RecordPaymentAction.php' => 
    array (
      0 => '1adeab614940af19a79bb34546862a27235eba4e00b38ec0652106cc61aa022a',
      1 => 
      array (
        0 => 'app\\domain\\sales\\actions\\recordpaymentaction',
      ),
      2 => 
      array (
        0 => 'app\\domain\\sales\\actions\\__construct',
        1 => 'app\\domain\\sales\\actions\\execute',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Sales\\Models\\CashSession.php' => 
    array (
      0 => 'b08e5d6e67b7de406d8b8f27a6a0622c8447885b2e7ccd2ffdd1ab25851cbfa0',
      1 => 
      array (
        0 => 'app\\domain\\sales\\models\\cashsession',
      ),
      2 => 
      array (
        0 => 'app\\domain\\sales\\models\\casts',
        1 => 'app\\domain\\sales\\models\\warehouse',
        2 => 'app\\domain\\sales\\models\\opener',
        3 => 'app\\domain\\sales\\models\\payments',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Sales\\Models\\Payment.php' => 
    array (
      0 => 'e4657240c23ed4d127c4e207f4ddcd1ca63bbbbc70579dee99d4f100e4e6200f',
      1 => 
      array (
        0 => 'app\\domain\\sales\\models\\payment',
      ),
      2 => 
      array (
        0 => 'app\\domain\\sales\\models\\casts',
        1 => 'app\\domain\\sales\\models\\customer',
        2 => 'app\\domain\\sales\\models\\sale',
        3 => 'app\\domain\\sales\\models\\method',
        4 => 'app\\domain\\sales\\models\\cashsession',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Sales\\Models\\Sale.php' => 
    array (
      0 => '78a7a29b04d08001c0b948c2c5f5626c4eedcdba1d5a25e79514facffa1138e5',
      1 => 
      array (
        0 => 'app\\domain\\sales\\models\\sale',
      ),
      2 => 
      array (
        0 => 'app\\domain\\sales\\models\\casts',
        1 => 'app\\domain\\sales\\models\\customer',
        2 => 'app\\domain\\sales\\models\\warehouse',
        3 => 'app\\domain\\sales\\models\\user',
        4 => 'app\\domain\\sales\\models\\lines',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Domain\\Sales\\Models\\SaleLine.php' => 
    array (
      0 => '32f38b520c623e2d7813ae9467e11695912e947dd97728bd892f8852849678ba',
      1 => 
      array (
        0 => 'app\\domain\\sales\\models\\saleline',
      ),
      2 => 
      array (
        0 => 'app\\domain\\sales\\models\\casts',
        1 => 'app\\domain\\sales\\models\\sale',
        2 => 'app\\domain\\sales\\models\\product',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\CashSessionController.php' => 
    array (
      0 => 'e574ea9f36f95c02182f66cbadd0715355afdc3ef9757a7dd91a794839243d92',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\cashsessioncontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\index',
        1 => 'app\\http\\controllers\\api\\v1\\current',
        2 => 'app\\http\\controllers\\api\\v1\\open',
        3 => 'app\\http\\controllers\\api\\v1\\close',
        4 => 'app\\http\\controllers\\api\\v1\\serialize',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\ExpenseController.php' => 
    array (
      0 => '012922233012742afe272a8ac6a43b9f397ee9b26bbda12884a7fc1111269a27',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\expensecontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\categories',
        1 => 'app\\http\\controllers\\api\\v1\\storecategory',
        2 => 'app\\http\\controllers\\api\\v1\\index',
        3 => 'app\\http\\controllers\\api\\v1\\store',
        4 => 'app\\http\\controllers\\api\\v1\\decide',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\PaymentController.php' => 
    array (
      0 => 'b7b1e0d0047742f36fd5d07a744ee28dd0cb1b9ce534d4a6a3cfb2f31e3a922f',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\paymentcontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\index',
        1 => 'app\\http\\controllers\\api\\v1\\store',
        2 => 'app\\http\\controllers\\api\\v1\\chequestatus',
        3 => 'app\\http\\controllers\\api\\v1\\aging',
        4 => 'app\\http\\controllers\\api\\v1\\statement',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\PurchaseOrderController.php' => 
    array (
      0 => '85b9fec0783898e2b29bc7e412e9e4e1ed27aa843e8d1b96a72a7c1aab18f446',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\purchaseordercontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\index',
        1 => 'app\\http\\controllers\\api\\v1\\store',
        2 => 'app\\http\\controllers\\api\\v1\\show',
        3 => 'app\\http\\controllers\\api\\v1\\receive',
        4 => 'app\\http\\controllers\\api\\v1\\cancel',
        5 => 'app\\http\\controllers\\api\\v1\\supplierreturn',
        6 => 'app\\http\\controllers\\api\\v1\\replenishment',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\SaleController.php' => 
    array (
      0 => 'f593f2d0c82cc7601617650b78b2e51a9ab567082c57247e983a9376a68925b4',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\salecontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\index',
        1 => 'app\\http\\controllers\\api\\v1\\price',
        2 => 'app\\http\\controllers\\api\\v1\\store',
        3 => 'app\\http\\controllers\\api\\v1\\show',
        4 => 'app\\http\\controllers\\api\\v1\\confirm',
        5 => 'app\\http\\controllers\\api\\v1\\cancel',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\TransferController.php' => 
    array (
      0 => '72b371878d2463dd7e6669efcdacad179728fee2fc50c357ce39a9b33b8e052b',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\transfercontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\index',
        1 => 'app\\http\\controllers\\api\\v1\\store',
        2 => 'app\\http\\controllers\\api\\v1\\show',
        3 => 'app\\http\\controllers\\api\\v1\\receive',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\AlertController.php' => 
    array (
      0 => 'b12f83e516fe36e3ea712cda07c6438b5b511ea58c07289c398b2282b36a216f',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\alertcontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\index',
      ),
      3 => 
      array (
      ),
    ),
    'C:\\OPTIZAWORKS\\igoutech\\backend\\app\\Http\\Controllers\\Api\\V1\\ReportController.php' => 
    array (
      0 => '769fb14c1ccf2c67131736381a1e3eb22fc1256fe790791fdcf5bed32eb9f157',
      1 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\reportcontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\api\\v1\\sales',
        1 => 'app\\http\\controllers\\api\\v1\\stockvaluation',
        2 => 'app\\http\\controllers\\api\\v1\\dormantproducts',
        3 => 'app\\http\\controllers\\api\\v1\\margins',
      ),
      3 => 
      array (
      ),
    ),
  ),
));