<?php

namespace DamConsultants\JPW\Model\ResourceModel\Collection;

class BynderTempDocDataCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * BynderConfigSyncDataCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\JPW\Model\BynderTempDocData::class,
            \DamConsultants\JPW\Model\ResourceModel\BynderTempDocData::class
        );
    }
}
