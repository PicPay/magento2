# PicPay payment plugin for Magento 2.x
Use PicPay's plugin for Magento to offer mobile payments online in your e-commerce.

## Integration
The plugin integrates Magento store with payments on PicPay App.

## Requirements
The plugin supports the Magento (version 2.1 and higher). 

## Collaboration
We commit all our new features directly into our GitHub repository.
But you can also request or suggest new features or code changes yourself!

## Support
Open new issue [https://github.com/PicPay/magento2/issues](https://github.com/PicPay/magento2/issues).

## Installation

Use composer:
```
composer require picpay/magento2
```

## API Documentation
##### - [PicPay E-Commerce registration page](https://ecommerce.picpay.com/)

##### - [PicPay E-Commerce public API documentation](https://ecommerce.picpay.com/doc/)

## Caching / Varnish configuration
In case you are using a caching layer such as Varnish, please exclude the following URL pattern from being cached
```
/picpay/*
```

## License
MIT license. For more information, see the LICENSE file.
