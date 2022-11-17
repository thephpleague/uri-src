uri-src
=======

[![Build](https://github.com/thephpleague/uri-src/workflows/build/badge.svg)](https://github.com/thephpleague/uri-src/actions?query=workflow%3A%22build%22)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

The `uri-src` is the monorepo which enables the development of the differnt league `uri` related packages:

- URI
- URI Components
- URI Interfaces

System Requirements
-------

To contribute to the package development you are required to have your code tested with the following requirements:

- **PHP >= 8.1** but the latest stable version of PHP is recommended
- The **ext-intl**
- The **ext-fileinfo**

Documentation
--------

Full documentation can be found at [uri.thephpleague.com][].

Contributing
-------

Contributions are welcome and will be fully credited. Please see [CONTRIBUTING](.github/CONTRIBUTING.md) and [CODE OF CONDUCT](.github/CODE_OF_CONDUCT.md) for details.

Testing
-------

The library has a :

- a [PHPUnit](https://phpunit.de) test suite
- a coding style compliance test suite using [PHP CS Fixer](https://cs.sensiolabs.org/).
- a code analysis compliance test suite using [PHPStan](https://github.com/phpstan/phpstan).

To run the tests, run the following command from the project folder.

``` bash
$ composer test
```

Security
-------

If you discover any security related issues, please email nyamsprod@gmail.com instead of using the issue tracker.

Credits
-------

- [ignace nyamagana butera](https://github.com/nyamsprod)
- [All Contributors](https://github.com/thephpleague/uri-src/contributors)

Attribution
-------

The `UriTemplate` class is adapted from the [Guzzle 6][] project. 

License
-------

The MIT License (MIT). Please see [License File](LICENSE) for more information.

[PSR-7]: https://www.php-fig.org/psr/psr-7/
[RFC3986]: https://tools.ietf.org/html/rfc3986
[RFC3987]: https://tools.ietf.org/html/rfc3987
[RFC6570]: https://tools.ietf.org/html/rfc6570
[uri.thephpleague.com]: https://uri.thephpleague.com
[Guzzle 6]: https://github.com/guzzle/guzzle/blob/6.5/src/UriTemplate.php
