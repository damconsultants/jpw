<?php

namespace DamConsultants\JPW\Model\ResourceModel\Collection;

class BynderDeleteDataCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * BynderConfigSyncDataCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\JPW\Model\BynderDeleteData::class,
            \DamConsultants\JPW\Model\ResourceModel\BynderDeleteData::class
        );
    }
}
