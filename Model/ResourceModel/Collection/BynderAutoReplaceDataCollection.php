<?php

namespace DamConsultants\JPW\Model\ResourceModel\Collection;

class BynderAutoReplaceDataCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * BynderConfigSyncDataCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\JPW\Model\BynderAutoReplaceData::class,
            \DamConsultants\JPW\Model\ResourceModel\BynderAutoReplaceData::class
        );
    }
}
