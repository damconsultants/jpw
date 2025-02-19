<?php

namespace DamConsultants\JPW\Model\ResourceModel;

class BynderConfigSyncData extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Bynder Syc Data
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init('bynder_config_sync_data', 'id');
    }
}
