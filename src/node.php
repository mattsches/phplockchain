<?php

ini_set('error_log', __DIR__.'/../error.log');

use Aura\Cli\CliFactory;
use Mattsches\Client;
use Mattsches\InitialClient;
use Mattsches\Transaction;
use Mattsches\Util;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;
use React\Http\Server;
use React\Socket\Server as SocketServer;
use Sikei\React\Http\Middleware\CorsMiddleware;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

require __DIR__.'/../vendor/autoload.php';

$cliFactory = new CliFactory();
$context = $cliFactory->newContext($GLOBALS);
$getopt = $context->getopt(['port,p:', 'difficulty,d:', 'master:m']);
$port = $getopt->get('-p');
$difficulty = $getopt->get('-d', 4);
$isMaster = $getopt->get('-m', false);
if (!$port) {
    die('No port given');
}

$loop = React\EventLoop\Factory::create();

try {
    $client = $isMaster ? new InitialClient(Util::createSignatureKeypair(), $difficulty) : new Client(
        Util::createSignatureKeypair(), $blockChain
    );
    // todo: if not master, download blockchain:
//    if (!$isMaster) {
//        $nodeClient = new GuzzleHttp\Client();
//        $blockChain = $nodeClient->get('http://127.0.0.1:5001/connect')->getBody()->getContents();
//        $client->importBlockChain($blockChain);
//    }
} catch (Exception $e) {
    die($exception->getMessage());
}

$bench = new Ubench;

$server = new Server(
    [
        new CorsMiddleware(['allow_origin' => ['*']]),
        function (ServerRequestInterface $request) use ($client, $bench) {
            $response = [];
            $blockChain = $client->getBlockChain();
            switch ($request->getRequestTarget()) {
                case '/mine': // mine a new block
                    echo 'GET /mine'.PHP_EOL;
                    try {
                        $bench->start();
                        $block = $client->mine();
                        //TODO broadcast block to other nodes and negotiate consensus
                        $bench->end();
                        echo 'Mined new block in '.$bench->getTime().PHP_EOL;
                        $response = [
                            'message' => 'New block forged',
                            'index' => $block->getIndex(),
                            'transactions' => $block->getTransactions(),
                            'proof' => $block->getProofOfWork(),
                            'previousHash' => $block->getPreviousHash(),
                        ];
                    } catch (\Exception $e) {
                        echo $e->getMessage().PHP_EOL;
                    }
                    break;
                case '/transaction':
                    if ($request->getMethod() !== 'POST') {
                        return new Response(400, ['Content-Type' => 'text/plain'], 'no');
                    }
                    echo 'POST /transaction'.PHP_EOL;
                    $in = json_decode($request->getBody()->getContents(), true);
                    assert(array_key_exists('sender', $in));
                    assert(array_key_exists('recipient', $in));
                    assert(array_key_exists('amount', $in));
                    assert(array_key_exists('privkey', $in)); //for demo purposes only
                    try {
                        $signature = Util::signTransaction(
                            $in['sender'].$in['recipient'].$in['amount'],
                            $in['privkey'],
                            $in['recipient']
                        ); // todo
                        $index = $client->addTransaction(
                            new Transaction(
                                Util::getPublicKeyAsObject($in['sender']),
                                Util::getPublicKeyAsObject($in['recipient']),
                                (int)$in['amount'],
                                $signature
                            )
                        );
                        $response = [
                            'message' => sprintf('Transaction will be added to Block %d', $index),
                        ];
                        //TODO broadcast transaction
                    } catch (\Exception $e) {
                        $response = [
                            'message' => $e->getMessage(),
                        ];
                    }
                    break;
                case '/transaction/validate':
                    if ($request->getMethod() !== 'POST') {
                        return new Response(400, ['Content-Type' => 'text/plain'], 'no');
                    }
                    echo 'POST /transcation/validate'.PHP_EOL;
                    $in = json_decode($request->getBody()->getContents(), true);
                    assert(array_key_exists('txid', $in));
                    $response = [
                        'ours' => $client->verifyAndDecryptTransaction($in['txid']),
                    ];
                    break;
                case '/nodes/register':
                    if ($request->getMethod() !== 'POST') {
                        return new Response(400, ['Content-Type' => 'text/plain'], 'no');
                    }
                    echo 'POST /nodes/register'.PHP_EOL;
                    $in = json_decode($request->getBody()->getContents(), true);
                    $nodes = $in['nodes'];
                    assert(array_key_exists('nodes', $in));
                    assert(is_array($in['nodes']));
//                    foreach ($nodes as $node) {
//                        $blockChain->registerNode($node);
//                    }
//                    $response = [
//                        'message' => 'New nodes have been added',
//                        'total_nodes' => count($blockChain->getNodes()),
//                    ];
                    break;
                case '/nodes/resolve':
                    echo 'GET /nodes/resolve'.PHP_EOL;
                    $response = [
                        'message' => 'Not implemented yet.',
                    ];
//                    $replaced = $blockChain->resolveConflicts();
//                    if ($replaced) {
//                        $response = [
//                            'message' => 'Our chain was replaced',
//                            'new_chain' => $blockChain->getBlocks(),
//                        ];
//                    } else {
//                        $response = [
//                            'message' => 'Our chain is authoritative',
//                            'chain' => $blockChain->getBlocks(),
//                        ];
//                    }
                    break;
                case '/chain/valid':
                    echo 'GET /chain/valid'.PHP_EOL;
                    $response = [
                        'message' => $blockChain->isValid() ? 'valid' : 'invalid',
                    ];
                    break;
                case '/chain': // TODO @deprecated ?
                    echo 'GET /chain'.PHP_EOL;
                    $response = [
                        'chain' => $blockChain,
                        'length' => count($blockChain->getBlocks()),
                    ];
                    break;
                case '/getblockchaininfo': // based on https://bitcoin.org/en/developer-reference#getblockchaininfo
                    echo 'GET /getblockchaininfo'.PHP_EOL;
                    $response = [
                        'blocks' => count($blockChain->getBlocks()),
                        'difficulty' => $blockChain->getDifficulty(),
                    ];
                    break;
                case '/connect':
                    try {
                        $encoders = [new XmlEncoder(), new JsonEncoder()];
                        $normalizers = [new ObjectNormalizer()];
                        $serializer = new Serializer($normalizers, $encoders);
                        $serialized = $serializer->serialize($blockChain, 'xml');
//                        var_dump($serialized);
                    } catch (\Exception $e) {
                        var_dump($e->getMessage());
                    }

                    return new Response(200, [], $serialized);
                    break;
                case '/keys':
                    $response = [
                        'pubkey' => Util::getKeyAsString($client->getKeyPair()->getPublicKey()),
                        'privkey' => Util::getKeyAsString($client->getKeyPair()->getSecretKey()),
                    ];
                    break;
                case '/dashboard':
                    return new Response(200, [], file_get_contents(__DIR__.'/static/dashboard.html'));
                    break;
                default:
                    echo 'GET /'.PHP_EOL;
                    $response = ['message' => 'We\'re up and running!'];
            }

            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode($response, JSON_PRETTY_PRINT, 1000)
            );
        },
    ]
);

$socket = new SocketServer($port, $loop);
$server->listen($socket);
echo 'Node running at '.str_replace('tcp:', 'http:', $socket->getAddress()).PHP_EOL;

$loop->run();
