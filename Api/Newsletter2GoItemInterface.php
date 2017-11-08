<?php

namespace Newsletter2Go\Export\Api;

interface Newsletter2GoItemInterface
{

    /**
     * Retrieves product by id or sku
     * @api
     * @param string $itemId
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function getItem($itemId);

    /**
     * Retrieves product fields
     * @api
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function getItemFields();
}