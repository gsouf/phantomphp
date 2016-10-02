<?php
/**
 * @license see LICENSE
 */

namespace PhantomPhp;

class Process
{
    const STATUS_READY = 'ready';
    const STATUS_STARTED = 'started';
    const STATUS_TERMINATED = 'terminated';

    const COMCHANNEL_STREAM = 'stream';
    const COMCHANNEL_HTTP   = 'http';

    protected $command;
    protected $cwd;
    protected $process;
    protected $pipes;
    protected $status = self::STATUS_READY;
    protected $processInformation;
    protected $readLineOffset = 0;

    /**
     * Process constructor.
     * @param $string
     */
    public function __construct($command, $cwd = null)
    {
        $this->command = $command;

        $this->cwd = $cwd;
        // on Windows, if the cwd changed via chdir(), proc_open defaults to the dir where PHP was started
        // on Gnu/Linux, PHP builds with --enable-maintainer-zts are also affected
        // @see : https://bugs.php.net/bug.php?id=51800
        // @see : https://bugs.php.net/bug.php?id=50524
        if (null === $this->cwd && (defined('ZEND_THREAD_SAFE') || '\\' === DIRECTORY_SEPARATOR)) {
            $this->cwd = getcwd();
        }
    }


    public function start()
    {
        $desc = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $this->process = proc_open($this->command, $desc, $this->pipes, $this->cwd);
        stream_set_blocking($this->pipes[1], 0);
        stream_set_blocking($this->pipes[2], 0);
        $this->status = self::STATUS_STARTED;
    }

    public function isRunning()
    {
        if (self::STATUS_STARTED !== $this->status) {
            return false;
        }
        $this->updateStatus();
        return $this->processInformation['running'];
    }

    public function updateStatus()
    {
        if (self::STATUS_STARTED !== $this->status) {
            return;
        }

        $this->processInformation = proc_get_status($this->process);
    }

    public function write($message)
    {
        fwrite($this->pipes[0], $message);
    }


    public function readLine()
    {
        return fgets($this->pipes[1]);
    }

    public function close()
    {
        proc_close($this->process);
        $this->status = self::STATUS_TERMINATED;
    }
}
