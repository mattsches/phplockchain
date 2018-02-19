<?php

use Aura\Cli\CliFactory;
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
    $keyPair = KeyFactory::generateSignatureKeyPair();
} catch (InvalidKey $e) {
}
$privateKey = $keyPair->getSecretKey();
$publicKey = $keyPair->getPublicKey();
echo $name.'\'s Private Key: ' . sodium_bin2hex($privateKey->getRawKeyMaterial()).PHP_EOL;
echo $name.'\'s Public Key:  ' . sodium_bin2hex($publicKey->getRawKeyMaterial()).PHP_EOL;
