<?php

ini_set('error_log', __DIR__.'/../error.log');

use Aura\Cli\CliFactory;
use Mattsches\BlockChain;
use Mattsches\Transaction;
use ParagonIE\Halite\Asymmetric\Crypto;
use ParagonIE\Halite\Asymmetric\SignatureSecretKey;
use ParagonIE\Halite\HiddenString;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use React\Http\Response;
use React\Http\Server;
use React\Socket\Server as SocketServer;

require __DIR__.'/../vendor/autoload.php';

$cliFactory = new CliFactory();
$context = $cliFactory->newContext($GLOBALS);
$getopt = $context->getopt(['port,p:', 'difficulty,d:']);
$port = $getopt->get('-p');
$difficulty = $getopt->get('-d', 4);
if (!$port) {
    die('No port given');
}

$loop = React\EventLoop\Factory::create();

$nodeId = Uuid::uuid4()->toString();
$blockChain = new BlockChain($difficulty);

$bench = new Ubench;

$server = new Server(
    function (ServerRequestInterface $request) use ($blockChain, $nodeId, $bench) {
        switch ($request->getRequestTarget()) {
            case '/mine': // mine a new block
                try {
                    $bench->start();
                    // Actually, it's the current latest block of the chain, and will be the previous block after the new block has been mined
                    $previousBlock = $blockChain->getLatestBlock();
                    $previousHash = $previousBlock->calculateHash();
                    $proof = $blockChain->getProofOfWork($previousBlock->getProofOfWork(), $previousHash);
                    //TODO miner reward, make this more obvious:
                    $blockChain->addTransaction(new Transaction('0', $nodeId, 1, ''));
                    $block = $blockChain->addBlock($proof, $previousHash);
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
            case '/transactions':
                if ($request->getMethod() !== 'POST') {
                    return new Response(400, ['Content-Type' => 'text/plain'], 'no');
                }
                $in = json_decode($request->getBody()->getContents(), true);
                assert(array_key_exists('sender', $in));
                assert(array_key_exists('recipient', $in));
                assert(array_key_exists('amount', $in));
                assert(array_key_exists('privkey', $in)); //for demo purposes only
                try {
                    $message = $in['sender'].$in['recipient'].$in['amount'];
                    $signature = Crypto::sign(
                        $message,
                        new SignatureSecretKey(new HiddenString(sodium_hex2bin($in['privkey'])))
                    );
                    $index = $blockChain->addTransaction(
                        new Transaction($in['sender'], $in['recipient'], (int)$in['amount'], $signature)
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
            case '/chain':
                $response = [
                    'chain' => $blockChain,
                    'length' => count($blockChain->getBlocks()),
                ];

                break;
            case '/nodes/register':
                if ($request->getMethod() !== 'POST') {
                    return new Response(400, ['Content-Type' => 'text/plain'], 'no');
                }
                $in = json_decode($request->getBody()->getContents(), true);
                $nodes = $in['nodes'];
                assert(array_key_exists('nodes', $in));
                assert(is_array($in['nodes']));
                foreach ($nodes as $node) {
                    $blockChain->registerNode($node);
                }
                $response = [
                    'message' => 'New nodes have been added',
                    'total_nodes' => count($blockChain->getNodes()),
                ];
                break;
            case '/nodes/resolve':
                $replaced = $blockChain->resolveConflicts();
                if ($replaced) {
                    $response = [
                        'message' => 'Our chain was replaced',
                        'new_chain' => $blockChain->getBlocks(),
                    ];
                } else {
                    $response = [
                        'message' => 'Our chain is authoritative',
                        'chain' => $blockChain->getBlocks(),
                    ];
                }
                break;
            case '/chain/valid':
                $response = [
                    'message' => $blockChain->isValid() ? 'valid' : 'invalid',
                ];
                break;
            default:
                $response = ['message' => 'We\'re up and running!'];
        }

        return new Response(
            200,
            ['Content-Type' => 'text/plain'],
            json_encode($response, JSON_PRETTY_PRINT, 1000)
        );
    }
);

$socket = new SocketServer($port, $loop);
$server->listen($socket);
echo 'Node running at '.$socket->getAddress().PHP_EOL;

$loop->run();
