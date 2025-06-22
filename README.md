# European Central Bank Service for Peso

[![Packagist]][Packagist Link]
[![PHP]][Packagist Link]
[![License]][License Link]

[Packagist]: https://img.shields.io/packagist/v/peso/peso.svg?style=flat-square
[PHP]: https://img.shields.io/packagist/php-v/peso/peso.svg?style=flat-square
[License]: https://img.shields.io/packagist/l/peso/peso.svg?style=flat-square

[Packagist Link]: https://packagist.org/packages/peso/peso
[License Link]: LICENSE.md

A simple standalone currency converter based on the Peso framework.

## Installation

```bash
composer require peso/peso
```

## Example

```php
<?php

use Peso\Peso\CurrencyConverter;
use Peso\Services\EuropeanCentralBankService;

require __DIR__ . '/vendor/autoload.php';

$peso = new CurrencyConverter(new EuropeanCentralBankService());

// current
echo $peso->convert('1500', 'EUR', 'PHP', 2), PHP_EOL; // '98746.50' as of 2025-06-22
// and historical
echo $peso->convertOnDate('1500', 'EUR', 'PHP', 2, '2025-06-13'), PHP_EOL; // '97059.00'
```

## Documentation

Read the full documentation here: <https://phpeso.org/v0.x/integrations/peso.html>

## Support

Please file issues on our main repo at GitHub: <https://github.com/phpeso/peso/issues>

## License

The library is available as open source under the terms of the [MIT License][License Link].
