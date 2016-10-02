<?php
/**
 * @license see LICENSE
 */
namespace PhantomPhp\Test;

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
        $client = new PhantomClient(
            [
                __DIR__ . '/../resources/foo.handlers.js',
                __DIR__ . '/../resources/rejectedMessage.handlers.js'
            ]
        );
        $this->assertFalse($client->isRunning());

        $client->start();
        $this->assertTrue($client->isRunning());

        $message = new Message('foo');
        $client->sendMessage($message);
        $response = $client->waitForResponse($message, 1000);
        $this->assertEquals('foobar', $response->getData());
        $this->assertEquals(Response::STATUS_SUCCESS, $response->getStatus());
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
}
