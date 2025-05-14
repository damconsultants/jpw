<?php

namespace DamConsultants\JPW\Model\ResourceModel\Collection;

class MagentoSkuCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * MagentoSkuCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\JPW\Model\MagentoSku::class,
            \DamConsultants\JPW\Model\ResourceModel\MagentoSku::class
        );
    }
}
