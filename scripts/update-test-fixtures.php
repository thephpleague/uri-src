<?php

$directory = __DIR__.'/../vendor/uri-templates/uritemplate-test';

if (is_dir($directory.'/.git')) {
    passthru(sprintf(
        'git -C %s pull --ff-only origin master',
        escapeshellarg($directory)
    ));

    exit;
}

passthru(sprintf(
    'git clone --depth 1 --branch master %s %s',
    escapeshellarg('https://github.com/uri-templates/uritemplate-test.git'),
    escapeshellarg($directory)
));
