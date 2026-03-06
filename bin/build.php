<?php
declare(strict_types=1);

// explicitly give VERSION via ENV or ask git for current version
$version = getenv('VERSION');
if ($version === false) {
    $version = ltrim(exec('git describe --always --dirty', $_, $code), 'v');
    if ($code !== 0) {
        fwrite(STDERR, 'Error: Unable to get version info from git. Try passing VERSION via ENV' . PHP_EOL);
        exit(1);
    }
}

// use first argument as output file or use "graph-composer-{version}.phar"
$out = $argv[1] ?? ('graph-composer-' . $version . '.phar');

passthru('
rm -rf build && mkdir build &&
cp -r bin src composer.json LICENSE build/ &&
sed -i .bak \'s/@dev/' . $version .'/g\' build/src/App.php && rm build/src/App.php.bak &&
composer config -d build/ platform.php 8.2.0 &&
composer install -d build/ --no-dev &&

cd build/ && rm -rf bin/build.php vendor/*/*/tests/ vendor/*/*/examples/ vendor/*/*/*.md vendor/*/*/composer.* vendor/*/*/phpunit.* vendor/*/*/.gitignore vendor/*/*/.travis.yml && cd .. &&
vendor/bin/phar-composer build build/ ' . escapeshellarg($out) . ' &&

php ' . escapeshellarg($out) . ' --version', $code);
exit($code);
