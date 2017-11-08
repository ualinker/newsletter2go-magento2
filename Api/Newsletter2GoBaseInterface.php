<?php

namespace Newsletter2Go\Export\Api;

interface Newsletter2GoBaseInterface
{

    /**
     * Test connection call
     * @api
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function testConnection();

    /**
     * Returns plugin version
     *
     * @api
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function pluginVersion();

    /**
     * Returns list of store views with language codes
     *
     * @api
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function getStores();

}