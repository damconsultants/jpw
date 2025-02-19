<?php

namespace DamConsultants\JPW\Model\ResourceModel\Collection;

class MetaPropertyCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * MetaPropertyCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\JPW\Model\MetaProperty::class,
            \DamConsultants\JPW\Model\ResourceModel\MetaProperty::class
        );
    }
}
