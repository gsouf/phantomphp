<?php
/**
 * @license see LICENSE
 */

namespace PhantomPhp;

class Message
{

    private static $messageId = 0;

    protected $id;
    protected $action;
    protected $data;
    protected $timeout;

    public function __construct($action, $data = null)
    {
        $this->id = ++self::$messageId;
        $this->action = $action;
        $this->data = $data;
    }

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return null
     */
    public function getData()
    {
        return $this->data;
    }
}
