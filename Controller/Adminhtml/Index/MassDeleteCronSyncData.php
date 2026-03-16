<?php
namespace DamConsultants\JPW\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use DamConsultants\JPW\Model\ResourceModel\Collection\BynderSycDataCollectionFactory;
use Magento\Framework\AuthorizationInterface;

class MassDeleteCronSyncData extends Action
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
    protected $authorization;
    /**
     * Mass Delete
     *
     * @param Context $context
     * @param Filter $filter
     * @param BynderSycDataCollectionFactory $collectionFactory
     * @param \DamConsultants\JPW\Model\BynderSycDataFactory $bynderFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        BynderSycDataCollectionFactory $collectionFactory,
        AuthorizationInterface $authorization,
        \DamConsultants\JPW\Model\BynderSycDataFactory $bynderFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->bynderFactory = $bynderFactory;
        $this->authorization = $authorization;
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
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('bynder/index/grid');
    }
    /**
     * Is Allowed
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('DamConsultants_JPW::cron_massdelete');
    }
}
