<?php
namespace DamConsultants\JPW\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use DamConsultants\JPW\Model\ResourceModel\Collection\BynderConfigSyncDataCollectionFactory;

class MassDeleteSyncData extends Action
{
    /**
     * @var $collectionFactory
     */
    public $collectionFactory;
    /**
     * @var $filter
     */
    public $filter;
    /**
     * @var bynderFactory.
     *
     */
    protected $bynderFactory;
    /**
     * Mass Delete
     *
     * @param Context $context
     * @param Filter $filter
     * @param BynderConfigSyncDataCollectionFactory $collectionFactory
     * @param \DamConsultants\JPW\Model\BynderConfigSyncDataFactory $bynderFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        BynderConfigSyncDataCollectionFactory $collectionFactory,
        \DamConsultants\JPW\Model\BynderConfigSyncDataFactory $bynderFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->bynderFactory = $bynderFactory;
        parent::__construct($context);
    }
    /**
     * Execute
     */
    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $count = 0;
            foreach ($collection as $model) {
                $model = $this->bynderFactory->create()->load($model->getId());
                $model->delete();
                $count++;
            }
            $this->messageManager->addSuccess(__('A total of %1 data(s) have been deleted.', $count));
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('bynder/index/sync');
    }
    /**
     * Is Allowed
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('DamConsultants_JPW::delete');
    }
}
