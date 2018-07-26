<?php

use Aura\Cli\CliFactory;
use Mattsches\Util;
use ParagonIE\Halite\Alerts\InvalidKey;
use ParagonIE\Halite\KeyFactory;

require __DIR__.'/../vendor/autoload.php';

$cliFactory = new CliFactory();
$context = $cliFactory->newContext($GLOBALS);
$getopt = $context->getopt(['name,n:']);
$name = $getopt->get('-n');
if (!$name) {
    die('No name given');
}

try {
    $keyPair = Util::createSignatureKeypair();
} catch (InvalidKey $e) {
}
$privateKey = $keyPair->getSecretKey();
$publicKey = $keyPair->getPublicKey();
try {
    echo $name.'\'s Private Key: '.Util::getKeyAsString($privateKey).PHP_EOL;
    echo $name.'\'s Public Key:  '.Util::getKeyAsString($publicKey).PHP_EOL;
} catch (SodiumException $exception) {
    echo $exception->getMessage();
}
