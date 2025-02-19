<?php

namespace DamConsultants\JPW\Model;

class Bynder extends \Magento\Framework\Model\AbstractModel
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
     * JPW
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(\DamConsultants\JPW\Model\ResourceModel\Bynder::class);
    }
}
