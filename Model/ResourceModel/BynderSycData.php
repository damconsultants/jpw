<?php

namespace DamConsultants\JPW\Model\ResourceModel;

class BynderSycData extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Bynder Syc Data
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init('bynder_cron_data', 'id');
    }
}
