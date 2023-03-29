<?php
//require_once("vendor/autoload.php");
require_once "Urlparser.php";

if (extension_loaded("pthreads")) {
    echo "Using pthreads" . PHP_EOL;
} else {
    echo "Threads start fail! Using polyfill" . PHP_EOL;
}

$start = microtime(true);

$url = "https://wiki.pwodev.com";

$parser = new Urlparser($url, 10, 0, ALL, true, true);
$parser->parse();

printf(PHP_EOL . "Done for %.2f seconds" . PHP_EOL, microtime(true) - $start);
echo 'Total: ' . count($parser->provider->getLinks()) . PHP_EOL;
echo 'Unique: ' . ($parser->provider->internal_counter + $parser->provider->external_counter) . PHP_EOL;
echo 'Internal: ' . $parser->provider->internal_counter . PHP_EOL;
echo 'External: ' . $parser->provider->external_counter . PHP_EOL;
echo 'Total raw: ' . $parser->provider->raw_links_counter . PHP_EOL;
die;