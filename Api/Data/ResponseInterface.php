<?php

namespace Newsletter2Go\Export\Api\Data;

interface ResponseInterface
{

    /**
     * @api
     * @return boolean
     */
    public function isSuccess();

    /**
     * @api
     * @param boolean $success
     * @return null
     */
    public function setSuccess($success);

    /**
     * @api
     * @return string
     */
    public function getMessage();

    /**
     * @api
     * @param string $message
     * @return null
     */
    public function setMessage($message);

    /**
     * @api
     * @return string
     */
    public function getErrorcode();

    /**
     * @api
     * @param string $errorcode
     * @return null
     */
    public function setErrorcode($errorcode);

    /**
     * @api
     * @return mixed
     */
    public function getData();

    /**
     * @api
     * @param mixed $data
     * @return null
     */
    public function setData($data);
}