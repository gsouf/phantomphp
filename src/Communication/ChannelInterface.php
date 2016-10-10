<?php
/**
 * @license see LICENSE
 */

namespace PhantomPhp\Communication;

use PhantomPhp\Exception\TimeoutException;
use PhantomPhp\Message;
use PhantomPhp\Response;

interface ChannelInterface
{

    public function sendMessage(Message $message);

    /**
     * @param Message $message
     * @param int $timeout timeout in milliseconds
     * @param int $tryDelay delay between two tries for reading message
     * @return Response
     * @throws TimeoutException
     */
    public function waitForResponse(Message $message, $timeout, $tryDelay = null);
}
