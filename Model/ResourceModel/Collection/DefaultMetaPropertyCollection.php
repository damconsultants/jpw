<?php

namespace DamConsultants\JPW\Model\ResourceModel\Collection;

class DefaultMetaPropertyCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * MetaPropertyCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\JPW\Model\DefaultMetaProperty::class,
            \DamConsultants\JPW\Model\ResourceModel\DefaultMetaProperty::class
        );
    }
}
