<?php
/**
 * @license see LICENSE
 */

namespace PhantomPhp\Communication;

use PhantomPhp\Communication\ChannelInterface;
use PhantomPhp\Exception;
use PhantomPhp\Message;
use PhantomPhp\Process;
use PhantomPhp\Response;
use PhantomPhp\ResponsePool;

class ProcessStream implements ChannelInterface
{

    /**
     * @var Process
     */
    protected $process;
    protected $responsePool;

    /**
     * ProcessStream constructor.
     * @param Process $process
     */
    public function __construct(Process $process)
    {
        $this->process = $process;
        $this->responsePool = new ResponsePool();
    }


    /**
     * @return Process
     */
    public function getProcess()
    {
        return $this->process;
    }



    public function sendMessage(Message $message)
    {
        $process = $this->getProcess();
        if (!$process->isRunning()) {
            throw new Exception('Unable to send message. Process is not running.');
        }

        $data = json_encode([
            'id' => $message->getId(),
            'action' => $message->getAction(),
            'data' => $message->getData()
        ]);

        $this->getProcess()->write($data . PHP_EOL);
    }

    /**
     * @param Message $message
     * @param int $timeout timeout in milliseconds
     * @param int $tryDelay delay between to tries of reading message
     * @return Response
     * @throws Exception\TimeoutException
     */
    public function waitForResponse(Message $message, $timeout, $tryDelay = 50)
    {
        if ($response = $this->responsePool->getResponse($message->getId())) {
            return $response;
        }
        $waitUntil = microtime(true) + ($timeout / 1000);
        while (microtime(true) < $waitUntil) {
            $this->readResponses();
            if ($response = $this->responsePool->getResponse($message->getId())) {
                return $response;
            }
            usleep(1000 * $tryDelay);
        }
        throw new Exception\TimeoutException('Timeout for reading response');
    }

    private function readResponses()
    {
        $process = $this->getProcess();
        while ($response = $process->readLine()) {
            if (!empty($response)) {
                $response = json_decode($response, true);
                if ($response) {
                    $this->responsePool->addResponse(Response::parse($response));
                }
            }
        }
    }
}
