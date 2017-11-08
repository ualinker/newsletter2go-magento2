<?php

namespace Newsletter2Go\Export\Model\Api;

use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\Request;
use Magento\Newsletter\Model\Subscriber;
use Newsletter2Go\Export\Api\Data\ResponseInterfaceFactory;
use Newsletter2Go\Export\Api\Newsletter2GoCustomerInterface;
use Magento\Framework\Webapi\Rest\Response;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ObjectManager;

class Newsletter2GoCustomer extends AbstractNewsletter2Go implements Newsletter2GoCustomerInterface
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
     * @var ObjectManager
     */
    private $om;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * Newsletter2GoCustomer constructor.
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $config
     * @param Request $request
     * @param Response $response
     * @param ResponseInterfaceFactory $responseFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $config,
        Request $request, Response $response,
        ResponseInterfaceFactory $responseFactory)
    {
        parent::__construct($responseFactory);
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->om = ObjectManager::getInstance();
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Customer export method
     * @api
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function getCustomers()
    {
        $group = $this->request->getParam('group');
        $hours = $this->request->getParam('hours');
        $subscribed = $this->request->getParam('subscribed');
        $fields = $this->request->getParam('fieldIds');
        $limit = $this->request->getParam('limit');
        $offset = $this->request->getParam('offset');
        $emails = $this->request->getParam('emails');
        $storeId = $this->request->getParam('storeId');

        try {
            $billingAdded = false;
            if (empty($fields)) {
                $customerFields = $this->getCustomerFields();
                $fields = array_keys($customerFields->getData());
            } else if (!in_array('default_billing', $fields)) {
                $fields[] = 'default_billing';
                $billingAdded = true;
            }

            if ($group === 'subscribers-only') {
                if ($billingAdded) {
                    $index = array_search('default_billing', $fields);
                    unset($fields[$index]);
                }

                return $this->getOnlySubscribers($fields, $subscribed, $limit, $offset, $emails, $storeId);
            }

            $subscribedCond = 1;
            if ($subscribed) {
                $subscribedCond = 'ns.subscriber_status = ' . Subscriber::STATUS_SUBSCRIBED;
            }

            /** @var \Magento\Customer\Model\ResourceModel\Customer\Collection $collection */
            $collection = $this->om->get('Magento\Customer\Model\ResourceModel\Customer\Collection');
            $collection->addAttributeToSelect('*');

            //Join with subscribers
            if ($subscribed || in_array('subscriber_status', $fields)) {
                $collection->joinTable(['ns' => 'newsletter_subscriber'], 'customer_id=entity_id', ['subscriber_status'],
                    'ns.subscriber_status = ' . Subscriber::STATUS_SUBSCRIBED, 'left');
            }

            if ($group !== null) {
                $collection->addAttributeToFilter('group_id', $group);
            }

            if ($storeId !== null) {
                $collection->addAttributeToFilter('store_id', $storeId);
            }

            if (!empty($emails)) {
                $collection->addAttributeToFilter('email', ['in' => $emails]);
            }

            if ($hours && is_numeric($hours)) {
                $ts = date('Y-m-d H:i:s', time() - 3600 * $hours);
                $collection->addAttributeToFilter('updated_at', ['gteq' => $ts]);
            }

            $collection->groupByAttribute('entity_id');
            $collection->getSelect()->where($subscribedCond);
            if ($limit) {
                $offset = $offset ?: 0;
                $collection->getSelect()->limit($limit, $offset);
            }

            $customers = $collection->load()->toArray($fields);
            /** @var \Magento\Customer\Model\Address $addressModel */
            $addressModel = $this->om->get('Magento\Customer\Model\Address');
            foreach ($customers as &$customer) {
                $addressModel->load($customer['default_billing']);
                if (array_key_exists('telephone', $customer)) {
                    $customer['telephone'] = $addressModel->getTelephone();
                }

                if (array_key_exists('subscriber_status', $customer) && $customer['subscriber_status'] === null) {
                    $customer['subscriber_status'] = 0;
                }

                if (!$billingAdded && isset($customer['default_billing'])) {
                    $customer['default_billing'] =
                        json_encode($addressModel->toArray());
                } else {
                    unset($customer['default_billing']);
                }

                if (isset($customer['default_shipping'])) {
                    $customer['default_shipping'] =
                        json_encode($addressModel->load($customer['default_shipping'])->toArray());
                }

                if (isset($customer['gender'])) {
                    $customer['gender'] = ($customer['gender'] == 1 ? 'm' : 'f');
                }
            }

            return $this->generateSuccessResponse($customers);

        }catch(\Exception $e){
            return $this->generateErrorResponse($e->getMessage());
        }
    }

    /**
     * Retrieves only subscribers that are not registered as customers
     *
     * @param array $fields
     * @param boolean $subscribed
     * @param int $limit
     * @param int $offset
     * @param array $emails
     * @param int $storeId
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function getOnlySubscribers($fields, $subscribed, $limit, $offset = 0, $emails = [], $storeId = null)
    {
        /** @var \Magento\Newsletter\Model\ResourceModel\Subscriber\Collection $collection */
        $collection = $this->om->get('Magento\Newsletter\Model\ResourceModel\Subscriber\Collection');
        $collection->addFieldToFilter('customer_id', 0);
        $collection->addFieldToSelect('subscriber_email', 'email');
        $collection->addFieldToSelect('store_id');
        $collection->addFieldToSelect('subscriber_status');
        if ($storeId !== null) {
            $collection->addStoreFilter($storeId);
        }

        if (!empty($emails)) {
            $collection->addFieldToFilter('subscriber_email', ['in' => $emails]);
        }

        if ($subscribed) {
            $collection->useOnlySubscribed();
        }

        if ($limit) {
            $offset = $offset ?: 0;
            $collection->getSelect()->limit($limit, $offset);
        }

        $subscribers = $collection->load()->toArray($fields);

        return $this->generateSuccessResponse($subscribers['items']);
    }

    /**
     * Customer groups export method
     * @api
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function getCustomerGroups()
    {
        /** @var \Magento\Customer\Model\ResourceModel\Group\Collection $groups */
        $groups = $this->om->get('Magento\Customer\Model\ResourceModel\Group\Collection');
        $result = [
            [
                'id' => 'subscribers-only',
                'name' => 'Subscribers only',
                'description' => 'Customers that subscribed to newsletter and didn\'t register as customers on system.',
            ],
        ];

        /** @var \Magento\Customer\Model\Group $group */
        foreach ($groups as $group) {
            $result[] = [
                'id' => $group->getId(),
                'name' => $group->getCode(),
                'description' => '',
            ];
        }

        return $this->generateSuccessResponse($result);
    }

    /**
     * Customer count export method
     * @api
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     * @throws Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCustomerCount()
    {
        $groupId = $this->request->getParam('groupId');
        $subscribed = $this->request->getParam('subscribed');
        $storeId = $this->request->getParam('storeId');

        if ($groupId === null || $groupId === '') {
            $this->response->setStatusCode(400);

            return $this->generateErrorResponse('Group Id. parameter must be set');
        }

        if ($groupId === 'subscribers-only') {
            /** @var \Magento\Newsletter\Model\ResourceModel\Subscriber\Collection $collection */
            $collection = $this->om->get('Magento\Newsletter\Model\ResourceModel\Subscriber\Collection');
            $collection->addFieldToFilter('customer_id', 0);
            if ($subscribed) {
                $collection->useOnlySubscribed();
            }
        } else {
            /** @var \Magento\Customer\Model\ResourceModel\Customer\Collection $collection */
            $collection = $this->om->get('Magento\Customer\Model\ResourceModel\Customer\Collection');
            $collection->addAttributeToFilter('group_id', $groupId);
            if ($subscribed) {
                $collection->joinTable(['ns' => 'newsletter_subscriber'], 'customer_id=entity_id', ['subscriber_status'], 'ns.subscriber_status=1');
            }
        }

        if ($storeId) {
            $collection->addAttributeToFilter('store_id', $storeId);
        }

        return $this->generateSuccessResponse($collection->count());
    }

    /**
     * Changes customer newsletter status
     * @api
     * @param string $email
     * @param string $status
     * @param string $storeId
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     * @internal param null $store
     */
    public function changeSubscriberStatus($email, $status = '0', $storeId = '1')
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->response->setStatusCode(400);

            return $this->generateErrorResponse('Email parameter is missing or invalid (' . $email . ')!');
        }

        $status = filter_var($status, FILTER_VALIDATE_INT);
        if ($status === false) {
            $this->response->setStatusCode(400);

            return $this->generateErrorResponse('Status parameter must be a number!');
        }

        $storeId = filter_var($status, FILTER_VALIDATE_INT);
        if ($storeId === false) {
            $this->response->setStatusCode(400);

            return $this->generateErrorResponse('Store Id parameter must be a number!');
        }

        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $this->om->get('Magento\Customer\Model\Customer');
        $customer->setWebsiteId($this->storeManager->getWebsite()->getId())->loadByEmail($email);

        /** @var \Magento\Newsletter\Model\Subscriber $subscriber */
        $subscriber = $this->om->get('Magento\Newsletter\Model\Subscriber');
        $subscriber->loadByEmail($email);
        $subscriber->setCustomerId($customer->getId() ?: 0);
        if ($status && !$subscriber->getId()) {
            if (!$customer->getId()) {
                $this->response->setStatusCode(404);

                return $this->generateErrorResponse('No customer or subscriber found with email: ' . $email);
            }

            /** @var \Magento\Newsletter\Model\Subscriber $subscriber */
            $subscriber = $this->om->create('Magento\Newsletter\Model\Subscriber');
            $subscriber->setCustomerId($customer->getId() ?: 0);
            $subscriber->setEmail($email);
            $subscriber->setSubscriberConfirmCode($subscriber->randomSequence());
            $subscriber->setStoreId($storeId);
        } else if (!$status && !$subscriber->getId()) {
            $this->response->setStatusCode(404);

            return $this->generateErrorResponse('No customer or subscriber found with email: ' . $email);
        }

        $subscriber->setSubscriberStatus($status ? Subscriber::STATUS_SUBSCRIBED : Subscriber::STATUS_UNSUBSCRIBED);
        $subscriber->save();

        return $this->generateSuccessResponse($subscriber->toArray());
    }

    /**
     * Customer fields export method
     * @api
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function getCustomerFields()
    {
        $result = [];
        $result['entity_id'] = $this->createArray('entity_id', 'Customer Id.', 'Unique customer number', 'Integer');
        $result['website_id'] = $this->createArray('website_id', 'Website Id.', 'Unique website number', 'Integer');
        $result['email'] = $this->createArray('email', 'E-mail', 'E-mail address', 'String');
        $result['group_id'] = $this->createArray('group_id', 'Group Id.', 'Unique group number', 'Integer');
        $result['created_at'] = $this->createArray('created_at', 'Created at', 'Timestamp of creation', 'Date');
        $result['updated_at'] = $this->createArray('updated_at', 'Updated at', 'Timestamp of last update', 'Date');
        $result['dob'] = $this->createArray('dob', 'Date of birth', 'Date of birth', 'Date');
        $result['disable_auto_group_change'] = $this->createArray('disable_auto_group_change', 'Disable auto group change', 'Disable auto group change', 'Boolean');
        $result['created_in'] = $this->createArray('created_in', 'Created in', 'Place it was created admin side or by registration', 'String');
        $result['suffix'] = $this->createArray('suffix', 'Suffix', 'suffix', 'String');
        $result['prefix'] = $this->createArray('prefix', 'Prefix', 'Prefix', 'String');
        $result['firstname'] = $this->createArray('firstname', 'Firstname', 'Firstname', 'String');
        $result['middlename'] = $this->createArray('middlename', 'Middlename', 'middlename', 'String');
        $result['lastname'] = $this->createArray('lastname', 'Lastname', 'lastname', 'String');
        $result['taxvat'] = $this->createArray('taxvat', 'Tax VAT', 'Tax VAT', 'String');
        $result['store_id'] = $this->createArray('store_id', 'Store Id.', 'Unique store number', 'Integer');
        $result['gender'] = $this->createArray('gender', 'Gender', 'Gender', 'Integer');
        $result['is_active'] = $this->createArray('is_active', 'Is active', 'Is Active', 'Boolean');
        $result['subscriber_status'] = $this->createArray('subscriber_status', 'Subscriber status', 'Subscriber status', 'Integer');
        $result['default_billing'] = $this->createArray('default_billing', 'Default billing address', 'Default billing address', 'Object');
        $result['default_shipping'] = $this->createArray('default_shipping', 'Default shipping address', 'Default shipping address', 'Object');
        $result['telephone'] = $this->createArray('telephone', 'Telephone number', 'Telephone number', 'String');

        return $this->generateSuccessResponse($result);
    }

}