<?php

namespace DamConsultants\JPW\Model\ResourceModel\Collection;

class BynderTempDataCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * BynderConfigSyncDataCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\JPW\Model\BynderTempData::class,
            \DamConsultants\JPW\Model\ResourceModel\BynderTempData::class
        );
    }
}
