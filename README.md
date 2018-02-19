# phplockchain - A Blockchain in PHP

I implemented this blockchain to better understand the blockchain technology. This program is intended for educational and learning purposes, **not** for use in production environments!

The intention was to keep the structure of the project simple in order to facilitate learning and understanding what is happening in a blockchain.

## Installation

Check out this repository and install the dependencies using composer:

```bash
$ git clone https://github.com/mattsches/phplockchain.git .
$ cd phplockchain
$ composer install
```

## Usage

To start a node, open a terminal and run

```bash
$ php src/node.php -p 5001 -d 4
```

where `-p` is the port number and `-d` the difficulty (`4` is default, be careful not to set it too high).

Then, you can *talk* to the node through its API using Postman, CURL, or your favorite tool.

TODO: Add API docs.

## Contributing

If you find an error in my blockchain implementation, please report an issue or open a pull request.

If you think any part of the program can be simplified or changed so it will be easier to comprehend, don't hesitate to offer your thoughts.

## Todos

There are central concepts of a blockchain that have not been implemented here, but probably should be added; some of these are:

* Validation of (the signature of) transactions
* Broadcasting transactions to peer nodes
* Broadcasting freshly mined block to peer nodes and negotiating consensus

## Acknowledgments

Inspired by and based on the following resources:

* https://hackernoon.com/learn-blockchains-by-building-one-117428612f46
* https://anders.com/blockchain/
* https://github.com/knowledgearcdotorg/phpblockchain
