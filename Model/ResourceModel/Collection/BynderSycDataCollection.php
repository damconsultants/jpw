<?php

namespace DamConsultants\JPW\Model\ResourceModel\Collection;

class BynderSycDataCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * BynderSycDataCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\JPW\Model\BynderSycData::class,
            \DamConsultants\JPW\Model\ResourceModel\BynderSycData::class
        );
    }
}
