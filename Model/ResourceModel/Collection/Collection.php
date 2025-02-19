<?php

namespace DamConsultants\JPW\Model\ResourceModel\Collection;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Collection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\JPW\Model\Bynder::class,
            \DamConsultants\JPW\Model\ResourceModel\Bynder::class
        );
    }
}
