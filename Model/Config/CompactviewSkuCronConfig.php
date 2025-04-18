<?php
/**
 * DamConsultants Software.
 *
 * @category  DamConsultants
 * @package   DamConsultants
 * @author    DamConsultants
 */
namespace DamConsultants\JPW\Model\Config;

class CompactviewSkuCronConfig extends \Magento\Framework\App\Config\Value
{
    /**
     * @var \Magento\Framework\App\Config\ValueFactory
     */
    protected $_configValueFactory;
    /**
     * @var string
     */
    protected $_runModelPath = '';
    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param string $runModelPath
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        $runModelPath = '',
        array $data = []
    ) {
        $this->_runModelPath = $runModelPath;
        $this->_configValueFactory = $configValueFactory;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }
    /**
     * After Save
     *
     * @return $this
     * @throws \Exception
     */
    public function afterSave()
    {
        $time = $this->getData('groups/compactview_sku_cron/fields/compactview_sku_time/value');
        $frequency = $this->getData('groups/compactview_sku_cron/fields/compactview_sku_frequency/value');
        $custom_time = $this->getConfigValue();
        $every_min =  \DamConsultants\JPW\Model\Config\Source\Frequency::EVERY_TEN_TIME;
        if ($frequency == $every_min) {
            $cronExprArray = [
                $custom_time, /*Minute*/
                '*', /*Hour*/
                '*', /*Day of the Month*/
                '*', /*Month of the Year*/
                '*', /*Day of the Week*/
            ];
        } else {
            $cronExprArray = [
                (int)$time[1], /*Minute*/
                (int)$time[0], /*Hour*/
                $frequency == \DamConsultants\JPW\Model\Config\Source\Frequency::CRON_MONTHLY ?
                '1' : '*', /*Day of the Month*/
                '*', /*Month of the Year*/
                $frequency == \DamConsultants\JPW\Model\Config\Source\Frequency::CRON_WEEKLY ?
                '1' : '*', /*Day of the Week*/
            ];
        }
        $cronExprString = join(' ', $cronExprArray);
        try {
            $this->_configValueFactory->create()->load(
                'crontab/default/jobs/damConsultants_bynder_add_compactview_sku_from_bynder/schedule/cron_expr',
                'path'
            )->setValue(
                $cronExprString
            )->setPath(
                'crontab/default/jobs/damConsultants_bynder_add_compactview_sku_from_bynder/schedule/cron_expr'
            )->save();
            $this->_configValueFactory->create()->load(
                'crontab/default/jobs/damConsultants_bynder_add_compactview_sku_from_bynder/run/model',
                'path'
            )->setValue(
                $this->_runModelPath
            )->setPath(
                'crontab/default/jobs/damConsultants_bynder_add_compactview_sku_from_bynder/run/model'
            )->save();
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t save the cron expression.'));
        }
        return parent::afterSave();
    }
    /**
     * Get ConfigValue
     *
     * @return $this
     */
    public function getConfigValue()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $scopeConfig = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $storeManager = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);
        return $scopeConfig->getValue(
            "cronimageconfig/compactview_sku_cron/your_min_compactview_sku_cron",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeManager->getStore()->getStoreId()
        );
    }
}
