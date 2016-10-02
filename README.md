PhantomPhp
==========

WIP

Start a phantomjs process, and control it from php.


Usage
-----

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

Roadmap
-------

- Ability to access the same phantomjs process from multiple php scripts
- Offer a webpage communication api (open a page with proxy/cookies, navigate, interact, screenshot, close page...) 