PhantomPhp
==========

WIP

Start a phantomjs process and control it from php.

Disclaimer: Not tested on windows and thus it might work only on unix systems 


Overview
--------

```php

use PhantomPhp\PhantomClient;
use PhantomPhp\Message\Ping;
use PhantomPhp\Exception\TimeoutException;

$client = new PhantomClient();
// Starts the phantomjs process
$client->start();

// Create and send a ping message
$ping = new Ping();
$client->sendMessage($ping);
// Wait 500 ms for a response
try {
    $response = $client->waitForResponse($ping, 500);
} catch (TimeoutException $e) {
    // Unable to get a response under 500ms
}

$client->stop();

```

Work with pages
---------------

TODO: page api 


Internal doc
------------

### Communication Channel

PhnatomPhp supports different communication channel. A communication channel represents a way to communicate
with the underlying phantomjs process.

List of communication processes:

- ``stream``: lightweight and intended for single process communication
- ``http``: Supports multi process communication through http server

TODO : Example of channel


### Message

PhantomPhp share information with phantomjs through message. For instance to know if the process is responding you will 
issue a ping message:

TODO: Ping example

### Custom actions

Each message issues an action: ping, exit... but you can as much as action as you want.

TODO: Plug action example


Roadmap
-------

- Offer a webpage communication api (open a page with proxy/cookies, navigate, interact, screenshot, close page...) 
- Test error
- File log