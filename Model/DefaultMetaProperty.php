<?php

namespace DamConsultants\JPW\Model;

class DefaultMetaProperty extends \Magento\Framework\Model\AbstractModel
{
    protected const CACHE_TAG = 'DamConsultants_JPW';

    /**
     * @var $_cacheTag
     */
    protected $_cacheTag = 'DamConsultants_JPW';

    /**
     * @var $_eventPrefix
     */
    protected $_eventPrefix = 'DamConsultants_JPW';

    /**
     * Meta Property
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(\DamConsultants\JPW\Model\ResourceModel\DefaultMetaProperty::class);
    }
}
