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
    /**
     * @var Process
     */
    private $process = null;

    private $communicationChannel;
    private $communicationMode;
    private $handlers;
    private $additionalOptions;

    /**
     * @param $phantomjsBinaries
     */
    public function __construct(
        $phantomjsBinaries = null,
        $communicationMode = 'stream',
        array $additionalOptions = []
    ) {
    
        $this->phantomjsBinaries = $phantomjsBinaries ? $phantomjsBinaries : 'phantomjs';
        $this->communicationMode = $communicationMode;
        $this->handlers = [];
        $this->additionalOptions = $additionalOptions;
    }

    private function createProcess()
    {
        $communicationMode = $this->communicationMode;
        $handlers = $this->handlers;
        $additionalOptions = $this->additionalOptions;

        $startOptions = array_merge(['mode' => $communicationMode, 'plugins' => $handlers], $additionalOptions);

        $startScript = [
            'exec',
            $this->phantomjsBinaries,
            __DIR__ . '/phantomStart.js',
            "'" . json_encode($startOptions) . "'"
        ];

        $this->process = new Process(implode(' ', $startScript));
        switch ($communicationMode) {
            case Process::COMCHANNEL_STREAM:
                $this->communicationChannel = new ProcessStream($this->process);
                break;
            case Process::COMCHANNEL_HTTP:
                $httpPort = isset($this->additionalOptions['httpPort']) ? $this->additionalOptions['httpPort'] : 8080;
                $this->communicationChannel = new HttpRequest('localhost', $httpPort);
                break;
            default:
                throw new Exception("Communication mode $communicationMode is not valid");
        }
    }

    public function addHandler($handler)
    {
        if ($this->isRunning()) {
            throw new Exception('Cannot set handler after process was started');
        }
        $this->handlers[] = $handler;
    }

    public function setOption($option, $value)
    {
        if ($this->isRunning()) {
            throw new Exception('Cannot set option after process was started');
        }
        $this->additionalOptions[$option] = $value;
    }

    public function setExecutable($binaries)
    {
        if ($this->isRunning()) {
            throw new Exception('Cannot set executable after process was started');
        }
        $this->phantomjsBinaries = $binaries;
    }

    /**
     * @return ChannelInterface|null
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
        if (!$this->process) {
            $this->createProcess();
        }

        if ($this->process->isRunning()) {
            throw new Exception('Process is already running');
        }
        $this->process->start();

        $this->waitForStart($this->process);
    }

    private function waitForStart(Process $process)
    {
        $dieOn = microtime(true) + 1000 * 500; // 500ms
        do {
            usleep(1000 * 20); // 20ms
            $r = $process->readLine();

            $r = trim($r);

            if ($r === 'ok') {
                if ($this->ping(5000)) {
                    return true;
                } else {
                    break;
                }
            } elseif ($r === 'error') {
                throw new Exception('Unable to start process. The process has crashed before startup.');
            }
        } while (microtime(true) < $dieOn);
        throw new Exception('Unable to start process. The process is not responding.');
    }

    public function stop()
    {
        if (!$this->process || !$this->process->isRunning()) {
            throw new Exception('Process is not running');
        }
        $exit = new ExitMessage();
        $this->sendMessage($exit);
        $this->waitForResponse($exit, 10000);
        $this->process->close();
    }


    /**
     * @return bool
     */
    public function isRunning()
    {
        return $this->process ? $this->process->isRunning() : false;
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
