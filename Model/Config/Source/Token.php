<?php

namespace Newsletter2Go\Export\Model\Config\Source;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Token extends Field
{
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setReadonly('true');

        return parent::_getElementHtml($element);
    }

}