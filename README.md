PhantomPhp
==========

**WIP** - This project is under heavy development if you can use it now, but be aware that everything can change at anytime.

The project
-----------

Start a phantomjs process and control it from php. The top feature is the ability to communicate with a single
phantomjs script from many php scripts.


Note: The project is NOT tested on windows and thus it might work only on unix systems 

Overview
--------

```php

use PhantomPhp\PhantomClient;
use PhantomPhp\PageManager;

// Create a phantom client that is responsible for managing phantomjs process
$client = new PhantomClient();
// Start the phantomjs js process
$client->start();

// Page manager helps to generate page (like a browser with pages/tabs)
$pageManager = new PageManager($client);

// Create a new page
$page = $pageManager->createPage();
// Open an url on this page
$page->navigate('http://example.com');

// Get the dom
$domString = $page->getDomContent();

// Run custom javascript on the page
$divWidth = $page->runScript('return document.getElementsByTagName("div")[0].offsetWidth;');

// Stop the phantomjs process
$client->stop();

```

Page API 
--------

TODO: page api doc


Multi process communication
---------------------------

### Communication Channel

PhnatomPhp supports different communication channel. A communication channel represents a way to communicate
with the underlying phantomjs process. By default php and phantomjs communicate through pipes, but you can turn 
phantomjs in a real application waiting for any script to interact with it.

To do this just start a HttpClient with a given port and you are ready to play with:


```php

use PhantomPhp\HttpClient;
use PhantomPhp\PageManager;

$client = new HttpClient(8080);
$client->start();
// Now phantomjs is listening on port 8080

// As with the stream client you can start a pageManager based uppon this http client
$pageManager = new PageManager($client);
// Create a page named foo
$page = $pageManager->createPage('foo');
$page->navigate('http://example.com');

// ....

```

If you leave this previous process opened and open another process, 
the other process is able to call phantomjs without starting a new client.
To do this you just have to define a http channel for the same port:


```php

use PhantomPhp\Communication\HttpRequest;
use PhantomPhp\PageManager;

$channel = new HttpRequest(8080);

// and pass this channel to a new page manager
$pageManager = new PageManager($client);
// Get the page named foo created on the other process
$page = $pageManager->getPage('foo');  // warning: getPage is not implemented yet
$dom = $page->getDomContent();

// ....

```

Roadmap
-------

- More end points for page api
- make the page high level synchronizable on demand (also on ``$page->navigate`` to lower request count)
- Support PSR-7 request
- Support page cookies, proxy, viewport, ua, etc...
- Support default proxy, defualt ua, default viewport, etc...
- Test error
- File log