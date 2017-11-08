<?php

namespace Newsletter2Go\Export\Model\Api;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Webapi\Request;
use Magento\Framework\Webapi\Rest\Response;
use Newsletter2Go\Export\Api\Newsletter2GoItemInterface;
use Newsletter2Go\Export\Api\Data\ResponseInterfaceFactory;
use Magento\Framework\App\ObjectManager;

class Newsletter2GoItem extends AbstractNewsletter2Go implements Newsletter2GoItemInterface
{
    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * Newsletter2GoItem constructor.
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $config
     * @param Request $request
     * @param Response $response
     * @param ResponseInterfaceFactory $responseFactory
     * @param ProductFactory $productFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $config,
        Request $request, Response $response,
        ResponseInterfaceFactory $responseFactory,
        ProductFactory $productFactory
    )
    {
        parent::__construct($responseFactory);
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->om = ObjectManager::getInstance();
        $this->request = $request;
        $this->response = $response;
        $this->productFactory = $productFactory;
    }

    /**
     * Retrieves product by id or sku
     * @api
     * @param string $itemId
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function getItem($itemId)
    {
        try {
            $storeId = $this->request->getParam('storeId');
            $fields = $this->request->getParam('fieldIds', array_keys($this->getItemFields()->getData()));
            /** @var Store $store */
            $store = $this->om->get('Magento\Store\Model\Store');

            $product = $this->productFactory->create();
            $productId = $itemId;
            if (filter_var($itemId, FILTER_VALIDATE_INT) === false) {
                $productId = $this->om->get('Magento\Catalog\Model\ResourceModel\Product')->getIdBySku($itemId);
            }

            if ($storeId !== null) {
                $product->setData('store_id', $storeId);
            } else {
                $storeId = $this->storeManager->getDefaultStoreView()->getId();
            }

            $store->load($storeId);
            $product->load($productId);
            if (!$product->getId()) {
                return $this->generateErrorResponse('Product with id or sku (' . $itemId . ') not found!');
            }

            $productArray = $product->toArray($fields);
            $this->reformatFields($product, $productArray, $store);

            return $this->generateSuccessResponse([$productArray]);
        } catch (\Exception $e) {
            return $this->generateErrorResponse($e->getMessage());
        }
    }

    /**
     * Retrieves product fields
     * @api
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function getItemFields()
    {
        $result = [];
        $result['images'] = $this->createArray('images', 'Images', 'Product images', 'Array');
        $result['vat'] = $this->createArray('vat', 'VAT', 'Value Added Tax', 'Float');
        $result['newPrice'] = $this->createArray('newPrice', 'New price', '', 'Float');
        $result['newPriceNet'] = $this->createArray('newPriceNet', 'New price net', '', 'Float');
        $result['oldPriceNet'] = $this->createArray('oldPriceNet', 'Old price net', '', 'Float');
        $result['oldPrice'] = $this->createArray('oldPrice', 'Old price net', '', 'Float');
        $result['entity_id'] = $this->createArray('entity_id', 'Product Id.', 'Product unique identificator', 'Integer');
        $result['type_id'] = $this->createArray('type_id', 'Type Id.', 'Type unique identificator');
        $result['sku'] = $this->createArray('sku', 'SKU', 'Stock Keeping Unit');
        $result['name'] = $this->createArray('name', 'Name', 'Product name');
        $result['meta_title'] = $this->createArray('meta_title', 'Meta title');
        $result['meta_description'] = $this->createArray('meta_description', 'Meta Description');
        $result['url_key'] = $this->createArray('url_key', 'Link');
        $result['shop_url'] = $this->createArray('shop_url', 'Shop url');
        $result['custom_design'] = $this->createArray('custom_design', 'Custom Design');
        $result['page_layout'] = $this->createArray('page_layout', 'Page Layout');
        $result['country_of_manufacture'] = $this->createArray('country_of_manufacture', 'Country of Manufacture');
        $result['status'] = $this->createArray('status', '', '', 'Integer');
        $result['visibility'] = $this->createArray('visibility', '', '', 'Integer');
        $result['tax_class_id'] = $this->createArray('tax_class_id', '', '', 'Integer');
        $result['description'] = $this->createArray('description', 'Description');
        $result['short_description'] = $this->createArray('short_description', 'Short Description');
        $result['meta_keyword'] = $this->createArray('meta_keyword', 'Meta Keywords');
        $result['msrp'] = $this->createArray('msrp', 'MSRP', 'Manufacturer\'s suggested retail price', 'Float');
        $result['news_from_date'] = $this->createArray('news_from_date', '', '', 'Date');
        $result['news_to_date'] = $this->createArray('news_to_date', '', '', 'Date');
        $result['custom_design_from'] = $this->createArray('custom_design_from', '', '', 'Date');
        $result['custom_design_to'] = $this->createArray('custom_design_to', '', '', 'Date');
        $result['is_in_stock'] = $this->createArray('is_in_stock', 'Is in stock', '', 'Boolean');
        $result['qty'] = $this->createArray('qty', 'Quantity', '', 'Integer');
        $result['price'] = $this->createArray('price', 'Price', '', 'Float');
        $result['special_price'] = $this->createArray('special_price', 'Special Price', '', 'Float');
        $result['special_from_date'] = $this->createArray('special_from_date', 'Special Price From Date', '', 'Date');
        $result['special_to_date'] = $this->createArray('special_to_date', 'Special Price To Date', '', 'Date');
        $result['weight'] = $this->createArray('weight', 'Weight', '', 'Float');
        $result['is_salable'] = $this->createArray('is_salable', 'Is Salable', '', 'Boolean');
        $result['sale'] = $this->createArray('sale', 'On Sale', 'Is product on sale', 'Boolean');


        return $this->generateSuccessResponse($result);
    }

    /**
     * @param Product $product
     * @param array $productArray
     * @param Store $store
     */
    protected function reformatFields(Product $product, array &$productArray, Store $store)
    {
        /** @var \Magento\Tax\Helper\Data $taxHelper */
        $taxHelper = $this->om->get('Magento\Tax\Helper\Data');
        $taxIncluded = $taxHelper->priceIncludesTax($store);

        /** @var \Magento\Tax\Model\Calculation $taxCalculation */
        $taxCalculation = $this->om->get('Magento\Tax\Model\Calculation');
        $request = $taxCalculation->getRateRequest(null, null, null, $store);
        $taxClassId = $product->getTaxClassId();
        $percent = $taxCalculation->getRate($request->setProductClassId($taxClassId));
        $vat = $percent;

        foreach ($productArray as $key => &$value) {
            switch ($key) {
                case 'url_key';
                    $url = $this->getParentProductUrl($product);
                    $parts = parse_url(str_replace($store->getBaseUrl(), '', $url));
                    $value = $parts['path'] . '?___store=' . $store->getCode();
                    break;
                case 'shop_url':
                    $value = $store->getBaseUrl();
                    break;
                case 'vat':
                    $value = number_format($vat * 0.01, 2);
                    break;
                case 'newPrice':
                    $value = $this->calculatePrice($product->getFinalPrice(), $vat, $taxIncluded);
                    break;
                case 'newPriceNet':
                    $value = $this->calculateNetPrice($product->getFinalPrice(), $vat, $taxIncluded);
                    break;
                case 'oldPrice':
                    $value = $this->calculatePrice($product->getPrice(), $vat, $taxIncluded);
                    break;
                case 'oldPriceNet':
                    $value = $this->calculateNetPrice($product->getPrice(), $vat, $taxIncluded);
                    break;
                case 'qty':
                    $value = $product->getQty();
                    break;
                case 'is_in_stock':
                    $value = $product->isInStock();
                    break;
                case 'images':
                    $value = [];
                    /** @var \Magento\Framework\DataObject $image */
                    foreach ($product->getMediaGalleryImages() as $image) {
                        $value[] = $image->getData('url');
                    }
                    break;
            }
        }
    }

    /**
     * @param $price
     * @param $vat
     * @param $taxIncluded
     * @return string
     */
    protected function calculateNetPrice($price, $vat, $taxIncluded)
    {
        return number_format($taxIncluded ? $price / (1 + $vat * 0.01) : $price, 2);
    }

    /**
     * @param $price
     * @param $vat
     * @param bool $taxIncluded
     * @return string
     */
    protected function calculatePrice($price, $vat, $taxIncluded = true)
    {
        return number_format($taxIncluded ? $price : $price * (1 + $vat * 0.01), 2);
    }

    /**
     * Retrieves parent product id if product is configurable
     * @param Product $product
     * @return mixed
     */
    protected function getParentProductUrl($product)
    {
        $parents = $this->om->create('Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable')
            ->getParentIdsByChild($product->getId());
        if (!empty($parents)) {
            /** @var Product $parent */
            $parent = $this->om->get('Magento\Catalog\Model\Product')->load($parents[0]);

            return $parent->getUrlModel()->getProductUrl($parent);
        }

        return $product->getUrlModel()->getProductUrl($product);
    }
}