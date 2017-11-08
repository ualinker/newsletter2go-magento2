<?php

namespace Newsletter2Go\Export\Model\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Validator\Exception;
use Magento\Framework\Phrase;
use Magento\Integration\Model\Oauth\Token;

class RegisterIntegration implements ObserverInterface
{

    const NEWSLETTER2GO_URL = 'https://www.newsletter2go.de/';

    /**
     * RegisterIntegration constructor.
     * @param ScopeConfigInterface $scope
     * @param ObjectManagerInterface $om
     */
    public function __construct(ScopeConfigInterface $scope, ObjectManagerInterface $om)
    {
        $this->config = $scope;
        $this->om = $om;
    }

    /**
     * @param Observer $observer
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        $tokenString = $this->config->getValue('newsletter2go/general/token');
        if (!$tokenString) {
            throw new Exception(new Phrase("Reset current API token because token must not be empty!"));
        }

        $tokenModel = $this->getToken($tokenString);
        if (!$tokenModel->getId()) {
            $this->createNewToken($tokenString);
            $this->revokePreviousTokens($tokenString);
        } else if ($tokenModel->getRevoked()) {
            throw new Exception(new Phrase("Reset current API token because token ($tokenString) is revoked!"));
        }
    }

    /**
     * Returns admin token that is used for api authentication.
     * Token is either fetched if it exists and is not revoked or new token is created.
     *
     * @param $currentToken
     * @return Token
     */
    protected function getToken($currentToken)
    {
        /** @var Token $tokenModel */
        $tokenModel = $this->om->get('Magento\Integration\Model\Oauth\Token');

        return $tokenModel->loadByToken($currentToken);
    }

    /**
     * Creates new token
     * @param $token
     */
    protected function createNewToken($token)
    {
        /** @var \Magento\Backend\Model\Auth\Session $adminSession */
        $adminSession = $this->om->get('Magento\Backend\Model\Auth\Session');
        $adminId = $adminSession->getUser()->getData('user_id');

        /** @var Token $tokenModel */
        $tokenModel = $this->om->create('Magento\Integration\Model\Oauth\Token');
        $tokenModel->createAdminToken($adminId);
        $tokenModel->setToken($token);
        $tokenModel->setCallbackUrl(self::NEWSLETTER2GO_URL);
        $tokenModel->save();
    }

    /**
     * Revokes all previous tokens
     * @param $activeToken
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function revokePreviousTokens($activeToken)
    {
        /** @var \Magento\Integration\Model\ResourceModel\Oauth\Token $resource */
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */
        $resource = $this->om->get('Magento\Integration\Model\ResourceModel\Oauth\Token');
        $connection = $resource->getConnection();
        if (!$connection) {
            throw new Exception(new Phrase('Unable to fetch db connection!'));
        }

        $callback = self::NEWSLETTER2GO_URL;
        $where = "token != '$activeToken' AND callback_url = '$callback' AND revoked = 0";

        return $connection->update($resource->getMainTable(), ['revoked' => 1], $where);
    }

}