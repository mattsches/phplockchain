<?php

ini_set('error_log', __DIR__.'/../error.log');

use Aura\Cli\CliFactory;
use Mattsches\Client;
use Mattsches\InitialClient;
use Mattsches\Transaction;
use Mattsches\Util;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use React\Http\Response;
use React\Http\Server;
use React\Socket\Server as SocketServer;
use Sikei\React\Http\Middleware\CorsMiddleware;

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
    $httpClient = new GuzzleHttp\Client();
    if ($isMaster) {
        $client = new InitialClient(Util::createSignatureKeypair(), $httpClient, $difficulty);
    } else {
        $client = new Client(Util::createSignatureKeypair(), $httpClient);
    }
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
                case '/transaction': // create a new  transaction
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
                        );
                        $index = $client->addTransaction(
                            new Transaction(
                                Uuid::uuid4(),
                                Util::getPublicKeyAsObject($in['sender']),
                                Util::getPublicKeyAsObject($in['recipient']),
                                (int)$in['amount'],
                                $signature
                            )
                        );
                        $response = [
                            'message' => sprintf('Transaction will be added to Block %d', $index),
                        ];
                        //TODO broadcast transaction to other nodes
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
                case '/chain/valid':
                    echo 'GET /chain/valid'.PHP_EOL;
                    $response = [
                        'message' => $blockChain->isValid() ? 'valid' : 'invalid',
                    ];
                    break;
                case '/chain':
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
                case '/getblock':
                    if ($request->getMethod() !== 'POST') {
                        return new Response(400, ['Content-Type' => 'text/plain'], 'no');
                    }
                    echo 'POST /getblock'.PHP_EOL;
                    $in = json_decode($request->getBody()->getContents(), true);
                    assert(array_key_exists('index', $in));
                    $block = $blockChain->getBlock($in['index']);
                    $response = $block->jsonSerialize();

                    break;
                case '/keys':
                    echo 'GET /keys'.PHP_EOL;
                    $response = [
                        'pubkey' => Util::getKeyAsString($client->getKeyPair()->getPublicKey()),
                        'privkey' => Util::getKeyAsString($client->getKeyPair()->getSecretKey()),
                    ];
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
