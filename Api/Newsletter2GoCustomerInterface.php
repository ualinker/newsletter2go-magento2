<?php

namespace Newsletter2Go\Export\Api;

interface Newsletter2GoCustomerInterface
{

    /**
     * Customer export method
     * @api
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function getCustomers();

    /**
     * Customer groups export method
     * @api
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function getCustomerGroups();

    /**
     * Customer count export method
     * @api
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function getCustomerCount();

    /**
     * Customer fields export method
     * @api
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function getCustomerFields();

    /**
     * Changes customer newsletter status
     * @api
     * @param string $email
     * @param string $status
     * @param string $storeId
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function changeSubscriberStatus($email, $status = '0', $storeId = '1');
}