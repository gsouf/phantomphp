<?php
/**
 * @license see LICENSE
 */
namespace PhantomPhp\Test;

use PhantomPhp\Communication\HttpRequest;
use PhantomPhp\HttpClient;
use PhantomPhp\Message;
use PhantomPhp\Message\Ping;
use PhantomPhp\PhantomClient;
use PhantomPhp\Process;
use PhantomPhp\Response;

/**
 * @covers PhantomPhp\PhantomClient
 */
class PhantomClientTest extends \PHPUnit_Framework_TestCase
{
    public function testPing()
    {
        $client = new PhantomClient();
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
        $client = new PhantomClient();
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
        $client = new PhantomClient();
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

    public function testHttp()
    {

        $client = new HttpClient();
        $client->addHandler(__DIR__ . '/../resources/foo.handlers.js');
        $client->addHandler(__DIR__ . '/../resources/rejectedMessage.handlers.js');
        $client->start();

        $channel = new HttpRequest('localhost', 8080);

        $ping = new Ping();

        $channel->sendMessage($ping);
        $response = $channel->waitForResponse($ping, 2000);

        $this->assertEquals('pong', $response->getData());
        $this->assertEquals('success', $response->getStatus());
        $this->assertEquals($ping->getId(), $response->getId());
        $client->stop();
    }

    public function testHttpCustomPort()
    {

        $client = new HttpClient(8282);
        $client->start();

        $channel = new HttpRequest('127.0.0.1', 8282);

        $ping = new Ping();

        $channel->sendMessage($ping);
        $response = $channel->waitForResponse($ping, 2000);

        $this->assertEquals('pong', $response->getData());
        $this->assertEquals('success', $response->getStatus());
        $this->assertEquals($ping->getId(), $response->getId());

        $client->stop();
    }

    public function testPageApi()
    {

//        $client = new HttpClient(8282);
//        $client->start();

        $channel = new HttpRequest('127.0.0.1', 8282);

        $ping = new Ping();

        // Create page
        $message = new Message('pageCreate');
        $channel->sendMessage($message);
        $response = $channel->waitForResponse($message, 2000);
        $pageId = $response->getData('pageId');

        // Navigate
        $message = new Message('pageNavigate', ['pageId' => $pageId, 'url' => 'http://httpbin.org/get?a']);
        $channel->sendMessage($message);
        $response = $channel->waitForResponse($message, 3000);
        var_dump($response);
//        $client->stop();
    }
}
