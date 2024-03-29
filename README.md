# Module for migrate Catalog - Customers - Orders from Prestashop 1.7 to Magento 2

![GitHub](https://img.shields.io/github/license/mimou78/magento2-prestashop-migration-tools.svg) ![GitHub release](https://img.shields.io/github/release/mimou78/magento2-prestashop-migration-tools.svg) [![Total Downloads](https://poser.pugx.org/mimou78/magento2-prestashop-migration-tools/downloads)](https://packagist.org/packages/mimou78/magento2-prestashop-migration-tools) [![Magento2-prestashop-migration-tools  Stable Version](https://poser.pugx.org/mimou78/magento2-prestashop-migration-tools/version)](https://packagist.org/packages/mimou78/magento2-prestashop-migration-tools)

## Installation for Magento 2

You can get library through [composer](https://getcomposer.org/)

```
composer require cleatsquad/magento2-prestashop-migration-tools
```

```
php bin/magento setup:update
```

Done!

## Usage

For using of this module you must use the sql query in fixtures folder for generate csvs.

Copy the csv in pub/media/flow/input

And execute the commande

```
 php bin/magento mimlab:flow:import catalog
 php bin/magento mimlab:flow:import customer
 php bin/magento mimlab:flow:import order
```

## License
[MIT](LICENSE)
