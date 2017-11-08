<?php

namespace Newsletter2Go\Export\Model\Config\Source;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class TokenResetButton extends Field
{

    /**
     * Retrieve element HTML markup
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setType('button');
        $element->setData('value', 'Reset Authorization Token');
        $element->setData('onclick', 'n2goTokenReset();');

        return $element->getElementHtml();
    }
}