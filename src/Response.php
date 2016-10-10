<?php
/**
 * @license see LICENSE
 */

namespace PhantomPhp;

class Response
{

    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';

    protected $id;
    protected $status;
    protected $data;

    /**
     * @param $id
     * @param $status
     * @param $data
     */
    public function __construct($id, $status, $data)
    {
        $this->id = $id;
        $this->status = $status;
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getData($name = null)
    {
        if ($name) {
            return isset($this->data[$name]) ? $this->data[$name] : null;
        }
        return $this->data;
    }

    public function isSuccessful()
    {
        return $this->getStatus() === self::STATUS_SUCCESS;
    }

    public static function parse($rawMessage)
    {
        $data = isset($rawMessage['data']) ? $rawMessage['data'] : [];
        $id = isset($rawMessage['id']) ? $rawMessage['id'] : null;
        $status = isset($rawMessage['status']) ? $rawMessage['status'] : null;

        return new self($id, $status, $data);
    }
}
