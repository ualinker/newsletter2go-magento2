<?php

namespace Newsletter2Go\Export\Model\Api;

use Newsletter2Go\Export\Api\Data\ResponseInterface;

class Response implements ResponseInterface
{

    /** @var boolean  */
    private $success;

    /** @var  string */
    private $message;

    /** @var  string */
    private $errorcode;

    /** @var mixed */
    private $data;

    /**
     * @api
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @api
     * @param mixed $data
     * @return null
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @api
     * @return boolean
     */
    public function isSuccess()
    {
        return $this->success;
    }

    /**
     * @api
     * @param boolean $success
     * @return null
     */
    public function setSuccess($success)
    {
        $this->success = $success;
    }

    /**
     * @api
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @api
     * @param string $message
     * @return null
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @api
     * @return string
     */
    public function getErrorcode()
    {
        return $this->errorcode;
    }

    /**
     * @api
     * @param string $errorcode
     * @return null
     */
    public function setErrorcode($errorcode)
    {
        $this->errorcode = $errorcode;
    }

}