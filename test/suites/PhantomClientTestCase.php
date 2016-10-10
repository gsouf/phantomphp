<?php
/**
 * @license see LICENSE
 */

namespace PhantomPhp\Test;

use PhantomPhp\Message;
use PhantomPhp\Message\Ping;
use PhantomPhp\Page;
use PhantomPhp\PageManager;
use PhantomPhp\PhantomClient;
use PhantomPhp\Response;

abstract class PhantomClientTestCase extends \PHPUnit_Framework_TestCase
{

    /**
     * @return PhantomClient
     */
    abstract public function getClient();


    public function testPing()
    {
        $client = $this->getClient();
        $client->start();

        $ping = new Ping();
        $client->sendMessage($ping);
        $response = $client->waitForResponse($ping, 500);

        $this->assertEquals('pong', $response->getData());
        $this->assertEquals('success', $response->getStatus());
        $this->assertEquals($ping->getId(), $response->getId());

        $client->stop();
    }


    public function testIsRunning()
    {
        $client = $this->getClient();

        $this->assertFalse($client->isRunning());

        $client->start();
        $this->assertTrue($client->isRunning());

        $client->sendMessage(new Ping());
        $this->assertTrue($client->isRunning());

        $client->stop();
        $this->assertFalse($client->isRunning());
    }


    public function testCustomHandlers()
    {
        $client = $this->getClient();

        $client->addHandler(__DIR__ . '/../resources/foo.handlers.js');
        $client->addHandler(__DIR__ . '/../resources/rejectedMessage.handlers.js');

        $this->assertFalse($client->isRunning());

        $client->start();
        $this->assertTrue($client->isRunning());

        $message = new Message('foo');
        $client->sendMessage($message);
        $response = $client->waitForResponse($message, 1000);
        $this->assertEquals(Response::STATUS_SUCCESS, $response->getStatus());
        $this->assertEquals('foobar', $response->getData());
        $this->assertEquals($message->getId(), $response->getId());

        $message = new Message('rejectedMessage');
        $client->sendMessage($message);
        $response = $client->waitForResponse($message, 1000);
        $this->assertEquals('whoops :(', $response->getData('message'));
        $this->assertEquals(Response::STATUS_ERROR, $response->getStatus());
        $this->assertEquals('failure', $response->getData('errorType'));
        $this->assertEquals($message->getId(), $response->getId());

        $client->stop();
    }


    public function testPageLowLevelApi()
    {
        $client = $this->getClient();

        $client->start();

        $channel = $client;

        // Create page
        $message = new Message('pageCreate');
        $client->sendMessage($message);
        $response = $client->waitForResponse($message, 2000);
        $this->assertEquals('success', $response->getStatus());
        $pageId = $response->getData('pageId');
        $this->assertNotEmpty($pageId);


        // Navigate
        $message = new Message(
            'pageNavigate',
            ['pageId' => $pageId, 'url' => 'http://httpbin.org/redirect-to?url=http://httpbin.org/get?a=b']
        );
        $channel->sendMessage($message);
        $response = $channel->waitForResponse($message, 3000);
        $this->assertEquals('success', $response->getStatus());
        $this->assertEquals('http://httpbin.org/get?a=b', $response->getData('url'));


        // Get content
        $message = new Message(
            'pageGetDom',
            ['pageId' => $pageId]
        );
        $channel->sendMessage($message);
        $response = $channel->waitForResponse($message, 3000);
        $this->assertEquals('success', $response->getStatus());

        $dom = new \SimpleXMLElement($response->getData('DOM'));
        $element = $dom->xpath('//pre');
        $element = json_decode((string)$element[0]);

        $this->assertEquals('b', $element->args->a);
        $this->assertEquals('http://httpbin.org/get?a=b', $element->url);


        // Liste pages
        $message = new Message('pageList');
        $channel->sendMessage($message);
        $response = $channel->waitForResponse($message, 2000);
        $this->assertEquals([['id' => $pageId, 'url' => 'http://httpbin.org/get?a=b']], $response->getData());
        // Create an empty page to test list with empty pages
        $message = new Message('pageCreate', ['pageId' => 'foo']);
        $channel->sendMessage($message);
        $response = $channel->waitForResponse($message, 2000);
        $this->assertEquals('foo', $response->getData('pageId'));
        // Liste pages
        $message = new Message('pageList');
        $channel->sendMessage($message);
        $response = $channel->waitForResponse($message, 2000);
        $this->assertEquals(
            [
                ['id' => $pageId, 'url' => 'http://httpbin.org/get?a=b'],
                ['id' => 'foo', 'url' => 'about:blank']
            ],
            $response->getData()
        );


        // Run script
        $message = new Message(
            'pageRunScript',
            [
                'pageId' => $pageId,
                'script' => 'return JSON.parse(document.getElementsByTagName("pre")[0].textContent).headers.Host'
            ]
        );
        $channel->sendMessage($message);
        $response = $channel->waitForResponse($message, 2000);
        $this->assertEquals('httpbin.org', $response->getData());

        $client->stop();
    }


    public function testPageHighLevelApi()
    {
        $client = $this->getClient();
        $client->start();

        $pageManager = new PageManager($client->getCommunicationChannel());

        $page = $pageManager->createPage('foo');
        $this->assertInstanceOf(Page::class, $page);
        $this->assertEquals('foo', $page->getPageId());

        $url = $page->navigate('http://httpbin.org/redirect-to?url=http://httpbin.org/get?a=b');
        $this->assertEquals('http://httpbin.org/get?a=b', $url);

        $dom = new \SimpleXMLElement($page->getDomContent());
        $element = $dom->xpath('//pre');
        $element = json_decode((string)$element[0]);

        $this->assertEquals('b', $element->args->a);
        $this->assertEquals('http://httpbin.org/get?a=b', $element->url);


        $data = $page->runScript('return JSON.parse(document.getElementsByTagName("pre")[0].textContent).headers.Host');
        $this->assertEquals('httpbin.org', $data);

        $client->stop();
    }
}
