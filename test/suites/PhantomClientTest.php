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
class PhantomClientTest extends PhantomClientTestCase
{

    public function getClient()
    {
        return new PhantomClient();
    }
}
