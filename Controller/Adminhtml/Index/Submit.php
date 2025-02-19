<?php

namespace DamConsultants\JPW\Controller\Adminhtml\Index;

use DamConsultants\JPW\Model\ResourceModel\Collection\MetaPropertyCollectionFactory;
use DamConsultants\JPW\Model\ResourceModel\Collection\DefaultMetaPropertyCollectionFactory;

class Submit extends \Magento\Backend\App\Action
{
    /**
     * @var $_helperData
     */
    protected $_helperData;
    /**
     * @var $metaProperty
     */
    protected $metaProperty;
    /**
     * @var $metaPropertyCollectionFactory
     */
    protected $metaPropertyCollectionFactory;
    /**
     * @var $_defaultMetaPropertyCollectionFactory
     */
    protected $_defaultMetaPropertyCollectionFactory;
    /**
     * @var $resultPageFactory
     */
    protected $resultPageFactory;

    /**
     * Submit.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \DamConsultants\JPW\Helper\Data $helperData
     * @param \DamConsultants\JPW\Model\MetaPropertyFactory $metaProperty
     * @param MetaPropertyCollectionFactory $metaPropertyCollectionFactory
     * @param DefaultMetaPropertyCollectionFactory $DefaultMetaPropertyCollectionFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \DamConsultants\JPW\Helper\Data $helperData,
        \DamConsultants\JPW\Model\MetaPropertyFactory $metaProperty,
        MetaPropertyCollectionFactory $metaPropertyCollectionFactory,
        DefaultMetaPropertyCollectionFactory $DefaultMetaPropertyCollectionFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->_helperData = $helperData;
        $this->metaProperty = $metaProperty;
        $this->metaPropertyCollectionFactory = $metaPropertyCollectionFactory;
        $this->_defaultMetaPropertyCollectionFactory = $DefaultMetaPropertyCollectionFactory;
        $this->resultPageFactory = $resultPageFactory;
    }
    /**
     * Execute
     *
     * @return $this
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $properites_system_slug = $this->getRequest()->getParam('system_slug');
            $select_meta_tag = $this->getRequest()->getParam('select_meta_tag');
            $collection = $this->metaPropertyCollectionFactory->create();
            $defaultCollection = $this->_defaultMetaPropertyCollectionFactory->create();
            $meta = [];
            $properties_details = [];
            $all_properties_slug = [];
            
            $get_collection_data = $collection->getData();
            if (count($get_collection_data) > 0) {
                foreach ($get_collection_data as $metacollection) {
                    $properties_details[$metacollection['system_slug']] = [
                        "id" => $metacollection['id'],
                        "property_name" => $metacollection['property_name'],
                        "property_id" => $metacollection['property_id'],
                        "magento_attribute" => $metacollection['magento_attribute'],
                        "attribute_id" => $metacollection['attribute_id'],
                        "bynder_property_slug" => $metacollection['bynder_property_slug'],
                        "system_slug" => $metacollection['system_slug'],
                        "system_name" => $metacollection['system_name'],
                    ];
                }
                
                $all_properties_slug = array_keys($properties_details);
                foreach ($defaultCollection as $default) {
                    foreach ($properites_system_slug as $key => $form_system_slug) {
                        if (in_array($form_system_slug, $all_properties_slug)) {
                            /* update data */
                            $pro_id = $properties_details[$form_system_slug]["id"];
                            $model = $this->metaProperty->create()->load($pro_id);
                        } else {
                            /* insert data */
                            $model = $this->metaProperty->create();
                        }
                        if ($select_meta_tag[$key] == $default['bynder_property_slug']) {
                            $model->setData('property_name', $default['property_name']);
                            $model->setData('property_id', $default['property_id']);
                            $model->setData('bynder_property_slug', $select_meta_tag[$key]);
                            $model->setData('system_slug', $form_system_slug);
                            $model->setData('system_name', $form_system_slug);
                            $model->save();
                        }
                    }
                }
            } else {
                /* insert all data */
                foreach ($defaultCollection as $default) {
                    foreach ($properites_system_slug as $key => $form_system_slug) {
                        if ($select_meta_tag[$key] == $default['bynder_property_slug']) {
                            $model = $this->metaProperty->create();
                            $model->setData('property_name', $default['property_name']);
                            $model->setData('property_id', $default['property_id']);
                            $model->setData('bynder_property_slug', $select_meta_tag[$key]);
                            $model->setData('system_slug', $form_system_slug);
                            $model->setData('system_name', $form_system_slug);
                            $model->save();
                        }
                        
                    }
                }
            }
            $message = __('Submited MetaProperty...!');
            $this->messageManager->addSuccessMessage($message);
            $this->resultPageFactory->create();
            return $resultRedirect->setPath('bynder/index/metaproperty');
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t submit your request, Please try again.'));
        }
    }
}
