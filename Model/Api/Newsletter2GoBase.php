<?php

namespace Newsletter2Go\Export\Model\Api;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Newsletter2Go\Export\Api\Data\ResponseInterfaceFactory;
use Newsletter2Go\Export\Api\Newsletter2GoBaseInterface;

class Newsletter2GoBase extends AbstractNewsletter2Go implements Newsletter2GoBaseInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * Newsletter2GoBase constructor.
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $config
     * @param ResponseInterfaceFactory $responseFactory
     */
    public function __construct(StoreManagerInterface $storeManager, ScopeConfigInterface $config, ResponseInterfaceFactory $responseFactory)
    {
        parent::__construct($responseFactory);
        $this->storeManager = $storeManager;
        $this->config = $config;
    }

    /**
     * Test connection call
     * @api
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function testConnection()
    {
        return $this->generateSuccessResponse();
    }

    /**
     * Returns plugin version
     *
     * @api
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function pluginVersion()
    {
        $composerPath = __DIR__ . '/../../composer.json';
        $realPath = realpath($composerPath);
        $version = '4000';
        if (file_exists($realPath)) {
            $json = file_get_contents($realPath);
            $jsonArray = json_decode($json, true);
            $version = str_replace('.', '', $jsonArray['version']);
        }

        return $this->generateSuccessResponse($version);
    }

    /**
     * Returns list of store views with language codes
     *
     * @api
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function getStores()
    {
        $result = [];
        $stores = $this->storeManager->getStores();
        $default = $this->storeManager->getDefaultStoreView()->getId();

        foreach ($stores as $store) {
            $language = $this->config->getValue('general/locale/code', 'stores', $store->getCode());
            $result[] = [
                'id' => $store['store_id'],
                'name' => $store['name'],
                'code' => $store['code'],
                'language' => $language,
                'default' => ($store['store_id'] == $default ? 1 : 0),
            ];
        }

        return $this->generateSuccessResponse($result);
    }
}