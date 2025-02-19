<?php

namespace DamConsultants\JPW\Model\ResourceModel\Collection;

class BynderMediaTableCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * BynderConfigSyncDataCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\JPW\Model\BynderMediaTable::class,
            \DamConsultants\JPW\Model\ResourceModel\BynderMediaTable::class
        );
    }
}
