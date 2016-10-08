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

class HttpRequest implements ChannelInterface
{

    /**
     * @var Process
     */
    protected $host;
    protected $port;
    protected $responsePool;
    protected $curlMultiHandler;

    /**
     * ProcessStream constructor.
     * @param Process $process
     */
    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;
        $this->responsePool = new ResponsePool();
    }

    private function getMultiHandler()
    {
        if (!$this->curlMultiHandler) {
            $this->curlMultiHandler = curl_multi_init();
        }
        return $this->curlMultiHandler;
    }


    public function sendMessage(Message $message)
    {
        $url = 'http://' . $this->host . ':' . $this->port . '/runAction';
        $data = [
            'id' => $message->getId(),
            'data' => $message->getData(),
            'action' => $message->getAction()
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['message' => json_encode($data)]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $mh = $this->getMultiHandler();
        curl_multi_add_handle($mh, $ch);
        curl_multi_exec($mh, $n);
    }

    /**
     * @param Message $message
     * @param int $timeout timeout in milliseconds
     * @param int $tryDelay delay between to tries of reading message
     * @return Response
     * @throws Exception\ResponseReadException
     */
    public function waitForResponse(Message $message, $timeout, $tryDelay = 50)
    {
        if ($response = $this->responsePool->getResponse($message->getId())) {
            return $response;
        }

        $mh = $this->getMultiHandler();
        $waitUntil = microtime(true) + ($timeout / 1000);
        do {
            // Keep a trace of the number of remaining requests
            // If it's 0 (= all requests are consumed)  we will quite the loop after the next check
            // and before timeout in order to save precious time
            curl_multi_exec($mh, $st);

            // Check and buff available responses
            $this->readResponses();

            if ($response = $this->responsePool->getResponse($message->getId())) {
                return $response;
            } elseif (0 == $st) {
                throw new Exception\NoMoreMessageException('No more message to read');
            }

            usleep(1000 * $tryDelay);
        } while (microtime(true) < $waitUntil);

        throw new Exception\TimeoutException('Timeout for reading response');
    }

    private function readResponses()
    {
        $mh = $this->getMultiHandler();
        while ($doneHandle = curl_multi_info_read($mh)) {
            if(CURLE_OK == $doneHandle['result']){
                $output = curl_multi_getcontent($doneHandle['handle']);
                $output = json_decode($output, true);
                if ($output) {
                    $this->responsePool->addResponse(Response::parse($output));
                }
                curl_multi_remove_handle($mh, $doneHandle['handle']);
                curl_close($doneHandle['handle']);
            }else{
                $errorMsg = curl_error($doneHandle['handle']);
                throw new Exception\ResponseReadException('Unable to connect to phantom process. Curl error: ' . $errorMsg);
            }

        }
    }
}
