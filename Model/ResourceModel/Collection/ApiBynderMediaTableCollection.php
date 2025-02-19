<?php

namespace DamConsultants\JPW\Model\ResourceModel\Collection;

class ApiBynderMediaTableCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * BynderConfigSyncDataCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\JPW\Model\ApiBynderMediaTable::class,
            \DamConsultants\JPW\Model\ResourceModel\ApiBynderMediaTable::class
        );
    }
}
