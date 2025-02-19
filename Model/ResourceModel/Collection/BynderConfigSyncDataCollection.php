<?php

namespace DamConsultants\JPW\Model\ResourceModel\Collection;

class BynderConfigSyncDataCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * BynderConfigSyncDataCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\JPW\Model\BynderConfigSyncData::class,
            \DamConsultants\JPW\Model\ResourceModel\BynderConfigSyncData::class
        );
    }
}
