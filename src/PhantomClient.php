<?php
/**
 * @license see LICENSE
 */

namespace PhantomPhp;

use PhantomPhp\Communication\HttpRequest;
use PhantomPhp\Communication\ProcessStream;
use PhantomPhp\Communication\ChannelInterface;
use PhantomPhp\Exception\ResponseReadException;
use PhantomPhp\Exception\TimeoutException;
use PhantomPhp\Message\ExitMessage;
use PhantomPhp\Message\Ping;
use PhantomPhp\Process;

class PhantomClient implements ChannelInterface
{

    protected $phantomjsBinaries;
    private $process = null;

    private $communicationChannel;

    /**
     * @param $phantomjsBinaries
     */
    public function __construct(array $handlers = [], $phantomjsBinaries = 'phantomjs', $communicationMode = 'stream')
    {
        $this->phantomjsBinaries = $phantomjsBinaries;
        // Create the process but does not start it for the moment
        $this->createProcess($communicationMode, $handlers);
    }

    private function createProcess($communicationMode, array $handlers)
    {
        $startScript = [
            'exec',
            $this->phantomjsBinaries,
            __DIR__ . '/phantomStart.js',
            "'" . json_encode(['mode' => $communicationMode, 'plugins' => $handlers]) . "'"
        ];

        $this->process = new Process(implode(' ', $startScript));
        switch ($communicationMode) {
            case Process::COMCHANNEL_STREAM:
                $this->communicationChannel = new ProcessStream($this->process);
                break;
            case Process::COMCHANNEL_HTTP:
                $this->communicationChannel = new HttpRequest('localhost', 8080);
                break;
            default:
                throw new Exception("Communication mode $communicationMode is not valid");
        }
    }

    /**
     * @return Process
     */
    protected function getProcess()
    {
        return $this->process;
    }


    /**
     * @return ChannelInterface
     */
    public function getCommunicationChannel()
    {
        return $this->communicationChannel;
    }

    /**
     * start the phantom process
     * @throws Exception
     */
    public function start()
    {
        $process =$this->getProcess();
        if ($process->isRunning()) {
            throw new Exception('Process is already running');
        }
        $process->start();

        $this->waitForStart($process);
    }

    private function waitForStart(Process $process){
        $dieOn = microtime(true) + 1000 * 500; // 500ms
        do {
            usleep(1000 * 20); // 20ms
            $r = $process->readLine();

            if (trim($r) == 'ok') {
                if ($this->ping(5000)) {
                    return true;
                } else {
                    break;
                }
            }
        } while (microtime(true) < $dieOn);
        throw new Exception('Unable to start process');
    }

    public function stop()
    {
        $exit = new ExitMessage();
        $this->sendMessage($exit);
        $this->waitForResponse($exit, 10000);
        $this->getProcess()->close();
    }


    /**
     * @return bool
     */
    public function isRunning()
    {
        return $this->getProcess()->isRunning();
    }



    /**
     * Sends a ping to the process and wait for the response
     */
    public function ping($timeout = 1000)
    {
        $ping = new Ping();
        $this->sendMessage($ping);
        try {
            $response = $this->waitForResponse($ping, $timeout);
            return $response->getStatus() == Response::STATUS_SUCCESS;
        } catch (ResponseReadException $e) {
            return false;
        }
    }


    // CHANNEL INTERFACE PROXY
    public function sendMessage(Message $message)
    {
        $this->getCommunicationChannel()->sendMessage($message);
    }


    public function waitForResponse(Message $message, $timeout, $tryDelay = 50)
    {
        return $this->getCommunicationChannel()->waitForResponse($message, $timeout, $tryDelay);
    }
}
