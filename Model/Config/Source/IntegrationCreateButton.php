<?php

namespace Newsletter2Go\Export\Model\Config\Source;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class IntegrationCreateButton extends Field
{

    const NEWSLETTER2GO_CONNECT_URL = "https://ui.newsletter2go.com/integrations/connect/MAG2/?version=4000&token=<token>&url=";

    /**
     * Retrieve element HTML markup
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setType('button');
        $element->setData('value', 'Connect to Newsletter2Go');

        if (!$this->_scopeConfig->getValue('newsletter2go/general/token')) {
            $element->setDisabled('true');
        }

        // add shop url parameter
        $shopUrl = $this->_storeManager->getStore()->getBaseUrl();
        $url = self::NEWSLETTER2GO_CONNECT_URL . urlencode($shopUrl);

        // add language parameter
        $languageCode = $this->_scopeConfig->getValue('general/locale/code', 'stores');
        $parts = explode('_', $languageCode);
        $url .= '&language=' . $parts[0];

        $element->setData('onclick', "n2goConnect('$url');");

        return $element->getElementHtml();
    }
}