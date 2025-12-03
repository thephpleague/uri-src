<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

(new Uri\Rfc3986\UriBuilder())
    ->scheme("https")
    ->userInfo("user:pass")
    ->host("example.com")
    ->port(8080)
    ->path("foo/bar")
    ->query("a=1&b=2")
    ->fragment("section1")
    ->build()
    ->toString();
