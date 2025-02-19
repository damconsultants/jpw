<?php

namespace DamConsultants\JPW\Controller\Adminhtml\Index;

use DamConsultants\JPW\Model\ResourceModel\Collection\MetaPropertyCollectionFactory;
use DamConsultants\JPW\Model\ResourceModel\Collection\BynderMediaTableCollectionFactory;

class Psku extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory = false;
    /**
     * @var logger
     */
    protected $logger;
    /**
     * @var bynderMediaTable
     */
    protected $bynderMediaTable;
    /**
     * @var bynderMediaTableCollectionFactory
     */
    protected $bynderMediaTableCollectionFactory;
    /**
     * @var _productRepository
     */
    protected $_productRepository;
    /**
     * @var datahelper
     */
    protected $datahelper;
    /**
     * @var productAction
     */
    protected $productAction;
    /**
     * @var _byndersycData
     */
    protected $_byndersycData;
    /**
     * @var metaPropertyCollectionFactory
     */
    protected $metaPropertyCollectionFactory;
    /**
     * @var storeManagerInterface
     */
    protected $storeManagerInterface;
    /**
     * @var configWriter
     */
    protected $configWriter;
    /**
     * @var resouce
     */
    protected $resouce;
    /**
     * @var collectionFactory
     */
    protected $collectionFactory;
    /**
     * @var bynder
     */
    protected $bynder;
    /**
     * @var _resource
     */
    protected $_resource;
    /**
     * @var resultJsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var product
     */
    protected $product;

    /**
     * Product Sku.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Catalog\Model\Product\Action $action
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \DamConsultants\JPW\Model\BynderConfigSyncDataFactory $byndersycData
     * @param \DamConsultants\JPW\Model\BynderMediaTableFactory $bynderMediaTable
     * @param BynderMediaTableCollectionFactory $bynderMediaTableCollectionFactory
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param MetaPropertyCollectionFactory $metaPropertyCollectionFactory
     * @param \DamConsultants\JPW\Helper\Data $DataHelper
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Catalog\Model\Product\Action $action,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \DamConsultants\JPW\Model\BynderConfigSyncDataFactory $byndersycData,
        \DamConsultants\JPW\Model\BynderMediaTableFactory $bynderMediaTable,
        BynderMediaTableCollectionFactory $bynderMediaTableCollectionFactory,
        \Magento\Catalog\Model\Product $product,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        MetaPropertyCollectionFactory $metaPropertyCollectionFactory,
        \DamConsultants\JPW\Helper\Data $DataHelper,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $jsonFactory;
        $this->productAction = $action;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->metaPropertyCollectionFactory = $metaPropertyCollectionFactory;
        $this->datahelper = $DataHelper;
        $this->_resource = $resource;
        $this->bynderMediaTable = $bynderMediaTable;
        $this->bynderMediaTableCollectionFactory = $bynderMediaTableCollectionFactory;
        $this->_byndersycData = $byndersycData;
        $this->_productRepository = $productRepository;
        $this->product = $product;
    }
    /**
     * Execute
     *
     * @return $this
     */
    public function execute()
    {
        if (!$this->getRequest()->isAjax()) {
            $this->_forward('noroute');
            return '';
        }

        $property_id = null;
        $product_sku = $this->getRequest()->getParam('product_sku');
        $select_attribute = $this->getRequest()->getParam('select_attribute');
        $result = $this->resultJsonFactory->create();
       
        $collection = $this->metaPropertyCollectionFactory->create()->getData();
        $meta_properties = $this->getMetaPropertiesCollection($collection);

        $collection_value = $meta_properties['collection_data_value'];
        $collection_slug_val = $meta_properties['collection_data_slug_val'];

        if (strlen($product_sku) > 0) {
            $productSku = explode(",", trim($product_sku));
            if (count($productSku) > 0) {
                foreach ($productSku as $sku) {
                    if ($sku != "") {
                        $bd_sku = trim(preg_replace('/[^A-Za-z0-9-]/', '_', $sku));
                        $get_data = $this->datahelper->getImageSyncWithProperties($bd_sku, $property_id, $collection_value);
                        $getIsJson = $this->getIsJSON($get_data);
                        if (!empty($get_data) && $getIsJson) {
                            $respon_array = json_decode($get_data, true);
                            if ($respon_array['status'] == 1) {
                                $convert_array = json_decode($respon_array['data'], true);
                                if ($convert_array['status'] == 1) {
                                    $current_sku = $sku;
                                    try {
                                        $this->getDataItem(
                                            $select_attribute,
                                            $convert_array,
                                            $collection_slug_val,
                                            $current_sku
                                        );
                                    } catch (Exception $e) {
                                        $insert_data = [
                                            "sku" => $sku,
                                            "message" => $e->getMessage(),
                                            "data_type" => "",
                                            "lable" => "0"
                                        ];
                                        $this->getInsertDataTable($insert_data);
                                    }
                                    
                                } else {
                                    $insert_data = [
                                        "sku" => $sku,
                                        "message" => $convert_array['data'],
                                        "data_type" => "",
                                        "lable" => "0"
                                    ];
                                    $this->getInsertDataTable($insert_data);
                                    $product_id = $this->product->getIdBySku($sku);
                                    $updated_values = [
                                        'bynder_multi_img' => null,
                                        'bynder_isMain' => null
                                    ];
                                    $storeId = $this->storeManagerInterface->getStore()->getId();
                                    $this->productAction->updateAttributes(
                                        [$product_id],
                                        $updated_values,
                                        $storeId
                                    );
                                }
                            } else {
                                $insert_data = [
                                "sku" => $sku,
                                "message" => 'Please Select The Metaproperty First.....',
                                "data_type" => "",
                                "lable" => "0"
                                ];
                                $this->getInsertDataTable($insert_data);
                                $result_data = $result->setData(
                                    ['status' => 0, 'message' => 'Please check Bynder Synchronization. Action Log.....']
                                );
                                return $result_data;
                            }
                        } else {
                            $result_data = $result->setData(
                                [
                                    'status' => 0,
                                    'message' => 'Something went wrong from API side, Please contact to support team!'
                                ]
                            );
                            return $result_data;
                        }
                    }
                }
            }
            $result_data = $result->setData([
                'status' => 1,
                'message' => 'Data Sync Successfully.Please check Bynder Synchronization Log.!'
            ]);
            return $result_data;
        } else {
            $result_data = $result->setData(['status' => 0, 'message' => 'Please enter atleast one SKU.']);
            return $result_data;
        }
    }

    /**
     * Get Meta Properties Collection
     *
     * @param array $collection
     * @return array $response_array
     */
    public function getMetaPropertiesCollection($collection)
    {
        $collection_data_value = [];
        $collection_data_slug_val = [];
        if (count($collection) >= 1) {
            foreach ($collection as $key => $collection_value) {
                $collection_data_value[] = [
                    'id' => $collection_value['id'],
                    'property_name' => $collection_value['property_name'],
                    'property_id' => $collection_value['property_id'],
                    'magento_attribute' => $collection_value['magento_attribute'],
                    'attribute_id' => $collection_value['attribute_id'],
                    'bynder_property_slug' => $collection_value['bynder_property_slug'],
                    'system_slug' => $collection_value['system_slug'],
                    'system_name' => $collection_value['system_name']
                ];
                $collection_data_slug_val[$collection_value['system_slug']] = [
                    'bynder_property_slug' => $collection_value['bynder_property_slug'],
                ];
            }
        }
        $response_array = [
            "collection_data_value" => $collection_data_value,
            "collection_data_slug_val" => $collection_data_slug_val
        ];
        return $response_array;
    }

    /**
     * Is Json
     *
     * @param string $string
     * @return $this
     */
    public function getIsJSON($string)
    {
        return ((json_decode($string)) === null) ? false : true;
    }
    /**
     * Is Json
     *
     * @param array $insert_data
     * @return $this
     */
    public function getInsertDataTable($insert_data)
    {
        $model = $this->_byndersycData->create();
        $data_image_data = [
            'sku' => $insert_data['sku'],
            'bynder_sync_data' => $insert_data['message'],
            'bynder_data_type' => $insert_data['data_type'],
            'lable' => $insert_data['lable']
        ];
        $model->setData($data_image_data);
        $model->save();
    }
    /**
     * Is Json
     *
     * @param string $sku
     * @param array $m_id
     * @param string $product_ids
     * @param string $storeId
     * @return $this
     */
    public function getInsertMedaiDataTable($sku, $m_id, $product_ids, $storeId)
    {
        $model = $this->bynderMediaTable->create();
        $modelcollection = $this->bynderMediaTableCollectionFactory->create();
        $modelcollection->addFieldToFilter('sku', ['eq' => [$sku]])->load();
        $table_m_id = [];
        if (!empty($modelcollection)) {
            foreach ($modelcollection as $mdata) {
                $table_m_id[] = $mdata['media_id'];
            }
        }
        $media_diff = array_diff($m_id, $table_m_id);
        foreach ($media_diff as $new_data) {
            $new_m_id = trim($new_data);
            $data_image_data = [
                'sku' => $sku,
                'media_id' => $new_m_id,
                'status' => "1",
            ];
            $model->setData($data_image_data);
            $model->save();
        }
        $updated_values = [
            'bynder_delete_cron' => 1
        ];
        $this->productAction->updateAttributes(
            [$product_ids],
            $updated_values,
            $storeId
        );
    }
    /**
     * Is Json
     *
     * @param string $sku
     * @param string $media_id
     * @return $this
     */
    public function getDeleteMedaiDataTable($sku, $media_id)
    {
        $model = $this->bynderMediaTableCollectionFactory->create()->addFieldToFilter('sku', ['eq' => [$sku]])->load();
        foreach ($model as $mdata) {
            if ($mdata['media_id'] != $media_id) {
                $this->bynderMediaTable->create()->load($mdata['id'])->delete();

            }
        }
    }
    /**
     * Get Data Item
     *
     * @param string $select_attribute
     * @param array $convert_array
     * @param array $collection_data_slug_val
     * @param array $current_sku
     */
    public function getDataItem($select_attribute, $convert_array, $collection_data_slug_val, $current_sku)
    {
        $data_arr = [];
        $data_val_arr = [];
		$doc_data_arr = [];
        $result = $this->resultJsonFactory->create();
        if ($convert_array['status'] == 1) {
            foreach ($convert_array['data'] as $k => $data_value) {
                if ($select_attribute == $data_value['type']) {
                    $bynder_media_id = $data_value['id'];
                    $image_data = $data_value['thumbnails'];
                    $bynder_image_role = $image_data['magento_role_options'];
                    $bynder_alt_text = $image_data['img_alt_text'];
                    $sku_slug_name = "property_" . $collection_data_slug_val['sku']['bynder_property_slug'];
                    $data_sku[0] = $current_sku;
                    /*Below code for multiple derivative according to image roll */
                    $images_urls_list = [];
                    $new_magento_role_list = [];
                    $new_bynder_alt_text =[];
                    $new_bynder_mediaid_text = [];
                    $new_image_role = [];
                    if (count($bynder_image_role) > 0) {
                        foreach ($bynder_image_role as $m_bynder_role) {
                            if ($m_bynder_role == 0) {
                                $new_image_role = ['Base', 'Small', 'Thumbnail', 'Swatch'];
                            
                                $alt_text_vl = $data_value["thumbnails"]["img_alt_text"];
                                if (is_array($data_value["thumbnails"]["img_alt_text"])) {
                                    $alt_text_vl = implode(" ", $data_value["thumbnails"]["img_alt_text"]);
                                }
                                if (empty($alt_text_vl)) {
                                    $new_bynder_alt_text[] = "###\n";
                                } else {
                                    $new_bynder_alt_text[] = $alt_text_vl."\n";
                                }
                                /*$new_bynder_alt_text[] = (strlen($alt_text_vl) > 0)?$alt_text_vl."\n":"###\n";*/
                                $new_bynder_mediaid_text[] = $bynder_media_id;
                            } else {
                                $new_magento_role_list[] = "###"."\n";
                                /* this part added because sometime role not avaiable but alt text will be there*/
                                $alt_text_vl = $data_value["thumbnails"]["img_alt_text"];
                                if (!empty($alt_text_vl)) {
                                    $new_bynder_alt_text[] = $alt_text_vl."\n";
                                } else {
                                    $new_bynder_alt_text[] = "###\n";
                                }
                                $new_bynder_mediaid_text[] = $bynder_media_id."\n";
                            }
                        }
                    } else {
                        $new_magento_role_list[] = "###"."\n";
                        /* this part added because sometime role not avaiable but alt text will be there*/
                        $alt_text_vl = $data_value["thumbnails"]["img_alt_text"];
                        if (!empty($alt_text_vl)) {
                            $new_bynder_alt_text[] = $alt_text_vl."\n";
                        } else {
                            $new_bynder_alt_text[] = "###\n";
                        }
                        $new_bynder_mediaid_text[] = $bynder_media_id."\n";
                    }
                    if ($data_value['type'] == "image") {
                        $image_link = isset($data_value['derivatives'][0]['public_url']) ? $data_value['derivatives'][0]['public_url'] : $data_value['original'];
                        array_push($data_arr, $data_sku[0]);
                        $data_p = [
                            "sku" => $data_sku[0],
                            "url" => [$image_link."\n"], /* chagne by kuldip ladola for testing perpose */
                            'magento_image_role' => $new_image_role,
                            'image_alt_text' => $new_bynder_alt_text,
                            'bynder_media_id_new' => $new_bynder_mediaid_text
                        ];
                        array_push($data_val_arr, $data_p);
                    } else {
                        if ($data_value['type'] == 'video') {
                            /*$video_link = $image_data["image_link"] . '@@' . $image_data["webimage"];*/
                            $video_link = $data_value["original"] . '@@' . $image_data["webimage"];
                            array_push($data_arr, $data_sku[0]);
                            $data_p = [
                                "sku" => $data_sku[0],
                                "url" => [$video_link. "\n"],
                                'magento_image_role' => $new_image_role,
                                'image_alt_text' => $new_bynder_alt_text,
                                'bynder_media_id_new' => $new_bynder_mediaid_text,
                                "type" => "video"
                            ];
                            array_push($data_val_arr, $data_p);
    
                        } else {
                            $doc_name = $data_value["name"];
                            $doc_name_with_space = preg_replace("/[^a-zA-Z]+/", "-", $doc_name);
                            $doc_link = $data_value["original"] . '@@' . $doc_name_with_space. "\n";
                            array_push($data_arr, $data_sku[0]);
                            $data_p = [
								"sku" => $data_sku[0],
								"url" => [$doc_link],
								'magento_image_role' => $new_image_role,
                                'image_alt_text' => $new_bynder_alt_text,
                                'bynder_media_id_new' => $new_bynder_mediaid_text
							];
                            array_push($data_val_arr, $data_p);
                        }
    
                    }
                } elseif($select_attribute == 'all_attribute') {
					$bynder_media_id = $data_value['id'];
                    $image_data = $data_value['thumbnails'];
                    $bynder_image_role = $image_data['magento_role_options'];
                    $bynder_alt_text = $image_data['img_alt_text'];
                    $sku_slug_name = "property_" . $collection_data_slug_val['sku']['bynder_property_slug'];
                    $data_sku[0] = $current_sku;
                    /*Below code for multiple derivative according to image roll */
                    $images_urls_list = [];
                    $new_magento_role_list = [];
                    $new_bynder_alt_text =[];
                    $new_bynder_mediaid_text = [];
                    $new_image_role = [];
                    if (count($bynder_image_role) > 0) {
                        foreach ($bynder_image_role as $m_bynder_role) {
                            if ($m_bynder_role == 0) {
                                $new_image_role = ['Base', 'Small', 'Thumbnail', 'Swatch'];
                            
                                $alt_text_vl = $data_value["thumbnails"]["img_alt_text"];
                                if (is_array($data_value["thumbnails"]["img_alt_text"])) {
                                    $alt_text_vl = implode(" ", $data_value["thumbnails"]["img_alt_text"]);
                                }
                                if (empty($alt_text_vl)) {
                                    $new_bynder_alt_text[] = "###\n";
                                } else {
                                    $new_bynder_alt_text[] = $alt_text_vl."\n";
                                }
                                /*$new_bynder_alt_text[] = (strlen($alt_text_vl) > 0)?$alt_text_vl."\n":"###\n";*/
                                $new_bynder_mediaid_text[] = $bynder_media_id;
                            } else {
                                $new_magento_role_list[] = "###"."\n";
                                /* this part added because sometime role not avaiable but alt text will be there*/
                                $alt_text_vl = $data_value["thumbnails"]["img_alt_text"];
                                if (!empty($alt_text_vl)) {
                                    $new_bynder_alt_text[] = $alt_text_vl."\n";
                                } else {
                                    $new_bynder_alt_text[] = "###\n";
                                }
                                $new_bynder_mediaid_text[] = $bynder_media_id."\n";
                            }
                        }
                    } else {
                        $new_magento_role_list[] = "###"."\n";
                        /* this part added because sometime role not avaiable but alt text will be there*/
                        $alt_text_vl = $data_value["thumbnails"]["img_alt_text"];
                        if (!empty($alt_text_vl)) {
                            $new_bynder_alt_text[] = $alt_text_vl."\n";
                        } else {
                            $new_bynder_alt_text[] = "###\n";
                        }
                        $new_bynder_mediaid_text[] = $bynder_media_id."\n";
                    }
                    if ($data_value['type'] == "image") {
                        $image_link = $data_value['derivatives'][0]['public_url'];
                        array_push($data_arr, $data_sku[0]);
                        $data_p = [
                            "sku" => $data_sku[0],
                            "url" => [$image_link."\n"], /* chagne by kuldip ladola for testing perpose */
                            'magento_image_role' => $new_image_role,
                            'image_alt_text' => $new_bynder_alt_text,
                            'bynder_media_id_new' => $new_bynder_mediaid_text
                        ];
                        array_push($data_val_arr, $data_p);
                    } else {
                        if ($data_value['type'] == 'video') {
                            /*$video_link = $image_data["image_link"] . '@@' . $image_data["webimage"];*/
                            $video_link = $data_value["original"] . '@@' . $image_data["webimage"];
                            array_push($data_arr, $data_sku[0]);
                            $data_p = [
                                "sku" => $data_sku[0],
                                "url" => [$video_link. "\n"],
                                'magento_image_role' => $new_image_role,
                                'image_alt_text' => $new_bynder_alt_text,
                                'bynder_media_id_new' => $new_bynder_mediaid_text,
                                "type" => "video"
                            ];
                            array_push($data_val_arr, $data_p);
    
                        } else {
                            $doc_name = $data_value["name"];
                            $doc_name_with_space = preg_replace("/[^a-zA-Z]+/", "-", $doc_name);
                            $doc_link = $data_value["original"] . '@@' . $doc_name_with_space. "\n";
                            array_push($doc_data_arr, $data_sku[0]);
                            $data_p = [
								"sku" => $data_sku[0],
								"url" => [$doc_link],
								'magento_image_role' => $new_image_role,
                                'image_alt_text' => $new_bynder_alt_text,
                                'bynder_media_id_new' => $new_bynder_mediaid_text
							];
                            array_push($data_val_arr, $data_p);
                        }
					}
				}
            }
        }
		
		if (count($data_arr) > 0) {
			$this->getProcessItem($data_arr, $data_val_arr);
		} elseif(count($doc_data_arr) > 0) {
			$this->getProcessItemDoc($doc_data_arr, $data_val_arr);
		} else {
			$result_data = $result->setData(['status' => 0, 'message' => 'No Data Found...']);
			return $result_data;
		}
    }
    /**
     * Get Process Item
     *
     * @param array $data_arr
     * @param array $data_val_arr
     * @return $this
     */
    public function getProcessItem($data_arr, $data_val_arr)
    {
        $result = $this->resultJsonFactory->create();
        $image_value_details_role = [];
        $temp_arr = [];
        foreach ($data_arr as $key => $skus) {
            $temp_arr[$skus][] =  implode("", $data_val_arr[$key]["url"]);
            $image_value_details_role[$skus][] = $data_val_arr[$key]["magento_image_role"];
            $image_alt_text[$skus][] = implode("", $data_val_arr[$key]["image_alt_text"]);
            $byn_md_id_new[$skus][] = implode("", $data_val_arr[$key]["bynder_media_id_new"]);
        }
        foreach ($temp_arr as $product_sku_key => $image_value) {
            $img_json = implode("", $image_value);
            $mg_role = $image_value_details_role[$product_sku_key];
            $image_alt_text_value = implode("", $image_alt_text[$product_sku_key]);
            $this->getUpdateImage(
                $img_json,
                $product_sku_key,
                $mg_role,
                $image_alt_text_value,
                $byn_md_id_new
            );
        }
    }
	/**
     * Get Process Item
     *
     * @param array $data_arr
     * @param array $data_val_arr
     * @return $this
     */
    public function getProcessItemDoc($data_arr, $data_val_arr)
    {
        $result = $this->resultJsonFactory->create();
        $image_value_details_role = [];
        $temp_arr = [];
        foreach ($data_arr as $key => $skus) {
            $temp_arr[$skus][] =  implode("", $data_val_arr[$key]["url"]);
            $image_value_details_role[$skus][] = $data_val_arr[$key]["magento_image_role"];
            $image_alt_text[$skus][] = implode("", $data_val_arr[$key]["image_alt_text"]);
            $byn_md_id_new[$skus][] = implode("", $data_val_arr[$key]["bynder_media_id_new"]);
        }
        foreach ($temp_arr as $product_sku_key => $image_value) {
            $img_json = implode("", $image_value);
            $mg_role = $image_value_details_role[$product_sku_key];
            $image_alt_text_value = implode("", $image_alt_text[$product_sku_key]);
            $this->getUpdateDoc(
                $img_json,
                $product_sku_key,
                $mg_role,
                $image_alt_text_value,
                $byn_md_id_new
            );
        }
    }
	 /**
     * Upate Item
     *
     * @return $this
     * @param string $img_json
     * @param string $product_sku_key
     * @param string $mg_img_role_option
     * @param string $img_alt_text
     * @param string $bynder_media_ids
     */
    public function getUpdateDoc($img_json, $product_sku_key, $mg_img_role_option, $img_alt_text, $bynder_media_ids)
    {
        $result = $this->resultJsonFactory->create();
        $select_attribute = $this->getRequest()->getParam('select_attribute');
        $image_detail = [];
        $video_detail = [];
        $diff_image_detail = [];
        try {
            $storeId = $this->storeManagerInterface->getStore()->getId();
            $_product = $this->_productRepository->get($product_sku_key);
            $product_ids = $_product->getId();
            $image_value = $_product->getBynderMultiImg();
            $doc_values = $_product->getBynderDocument();
            $bynder_media_id = $bynder_media_ids[$product_sku_key];
			if (empty($doc_values)) {
				$new_doc_array = explode(" \n", $img_json);
				$doc_detail = [];
				foreach ($new_doc_array as $vv => $doc_value) {
					//$item_url = explode("?", $doc_value);
					$doc_name = explode("@@", $doc_value);
					$media_doc_explode = explode("/", $doc_name[0]);
					$doc_detail[] = [
						"item_url" => $doc_name[0],
						"item_type" => 'DOCUMENT',
						"doc_name" => $doc_name[1],
						"bynder_md_id" => $bynder_media_id[$vv]
					];
					$data_doc_value = [
								'sku' => $product_sku_key,
								'message' => $doc_name[0],
								'data_type' => '2',
								'media_id' => $bynder_media_id[$vv],
								'lable' => 1
							];
							$this->getInsertDataTable($data_doc_value);
				}
				$new_value_array = json_encode($doc_detail, true);
				
				$this->productAction->updateAttributes(
					[$product_ids],
					['bynder_document' => $new_value_array],
					$storeId
				);
			} else {
				$item_old_value = json_decode($doc_values, true);
				if (is_array($item_old_value)) {
					if (count($item_old_value) > 0) {
						foreach ($item_old_value as $doc) {
							if ($doc['item_type'] == 'DOCUMENT') {
								$all_item_url[] = $doc['item_url'];
								$b_id[] = $doc['bynder_md_id'];
							}
						}
					}
				}
				$new_doc_array = explode("\n", $img_json);
				$doc_detail = [];
				foreach ($new_doc_array as $vv => $doc_value) {
					if(!empty($doc_value)){
						$item_url = explode("?", $doc_value);
						$doc_name = explode("@@", $doc_value);
						$media_doc_explode = explode("/", $item_url[0]);
						if(!in_array($bynder_media_id[$vv], $b_id)) {
							$doc_detail[] = [
								"item_url" => $doc_name[0],
								"item_type" => 'DOCUMENT',
								"doc_name" => $doc_name[1],
								"bynder_md_id" => $bynder_media_id[$vv],
							];
							$data_doc_value = [
								'sku' => $product_sku_key,
								'message' => $doc_name[0],
								'data_type' => '2',
								'media_id' => $bynder_media_id[$vv],
								'lable' => 1
							];
							$this->getInsertDataTable($data_doc_value);
						}
						
					}
				}
				$array_merg = array_merge($item_old_value, $doc_detail);
				$new_value_array = json_encode($array_merg, true);
				$this->productAction->updateAttributes(
					[$product_ids],
					['bynder_document' => $new_value_array],
					$storeId
				);
			}
		} catch (\Exception $e) {
            return $result->setData(['message' => $e->getMessage()]);
        }
    }
    /**
     * Upate Item
     *
     * @return $this
     * @param string $img_json
     * @param string $product_sku_key
     * @param string $mg_img_role_option
     * @param string $img_alt_text
     * @param string $bynder_media_ids
     */
    public function getUpdateImage($img_json, $product_sku_key, $mg_img_role_option, $img_alt_text, $bynder_media_ids)
    {
        $result = $this->resultJsonFactory->create();
        $select_attribute = $this->getRequest()->getParam('select_attribute');
        $image_detail = [];
        $video_detail = [];
        $diff_image_detail = [];
        try {
            $storeId = $this->storeManagerInterface->getStore()->getId();
            $_product = $this->_productRepository->get($product_sku_key);
            $product_ids = $_product->getId();
            $image_value = $_product->getBynderMultiImg();
            $doc_value = $_product->getBynderDocument();
            $bynder_media_id = $bynder_media_ids[$product_sku_key];
            if ($select_attribute == "image") {
                if (!empty($image_value)) {
                    $new_image_array = explode("\n", $img_json);
                    $new_alttext_array = explode("\n", $img_alt_text);
                    $new_magento_role_option_array = $mg_img_role_option;
                    $all_item_url = [];
                    $item_old_value = json_decode($image_value, true);
                    if (count($item_old_value) > 0) {
                        foreach ($item_old_value as $img) {
                            $all_item_url[] = $img['item_url'];
                        }
                    }
                    foreach ($new_image_array as $vv => $new_image_value) {
                        if (trim($new_image_value) != "" && $new_image_value != "no image") {
                            $item_url = explode("?", $new_image_value);
                            $media_image_explode = explode("/", $item_url[0]);
                            $img_altText_val = "";
                            if (isset($new_alttext_array[$vv])) {
                                if ($new_alttext_array[$vv] != "###" && strlen(trim($new_alttext_array[$vv])) > 0) {
                                    $img_altText_val = $new_alttext_array[$vv];
                                }
                            }
                            $curt_img_role = [];
                            if ($new_magento_role_option_array[$vv] != "###") {
                                $curt_img_role = $new_magento_role_option_array[$vv];
                            }
                            $find_video = strpos($new_image_value, "@@");
                            if (!$find_video) {
                                $image_detail[] = [
                                    "item_url" => $new_image_value,
                                    "alt_text" => $img_altText_val,
                                    "image_role" => $curt_img_role,
                                    "item_type" => 'IMAGE',
                                    "thum_url" => $item_url[0],
                                    "bynder_md_id" => $bynder_media_id[$vv],
                                    "is_import" => 0
                                ];
                                if (!in_array($item_url[0], $all_item_url)) {
                                    $diff_image_detail[] = [
                                        "item_url" => $new_image_value,
                                        "alt_text" => $img_altText_val,
                                        "image_role" => $curt_img_role,
                                        "item_type" => 'IMAGE',
                                        "thum_url" => $new_image_value,
                                        "bynder_md_id" => $bynder_media_id[$vv],
                                        "is_import" => 0
                                    ];
                                    if (count($item_old_value) > 0) {
                                        foreach ($item_old_value as $kv => $img) {
                                            if ($img['item_type'] == "IMAGE") {
                                                /* here changes by me but not tested */
                                                if ($new_magento_role_option_array[$vv] != "###") {
                                                    $new_mg_role_array = (array)$new_magento_role_option_array[$vv];
                                                    if (count($img["image_role"])>0 && count($new_mg_role_array)>0) {
                                                        $result_val=array_diff($img["image_role"], $new_mg_role_array);
                                                        $item_old_value[$kv]["image_role"] = $result_val;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    $total_new_value = count($diff_image_detail);
                                    if ($total_new_value > 1) {
                                        foreach ($diff_image_detail as $nn => $n_img) {
                                            if ($n_img['item_type'] == "IMAGE" && $nn != ($total_new_value - 1)) {
                                                if ($new_magento_role_option_array[$vv] != "###") {
                                                    $new_mg_role_array = (array)$new_magento_role_option_array[$vv];
                                                    if (count($n_img["image_role"])>0 && count($new_mg_role_array)>0) {
                                                        $result_val=array_diff($n_img["image_role"], $new_mg_role_array);
                                                        $diff_image_detail[$nn]["image_role"] = $result_val;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $d_img_roll = "";
                    $d_media_id = [];
                    if (count($diff_image_detail) > 0) {
                        foreach ($diff_image_detail as $d_img) {
                            $d_img_roll = $d_img['image_role'];
                            $d_media_id[] =  $d_img['bynder_md_id'];
                        }
                        $this->getInsertMedaiDataTable($product_sku_key, $d_media_id, $product_ids, $storeId);
                    }
                    if (count($image_detail) > 0) {
                        foreach ($image_detail as $img) {
                            $image[] = $img['item_url'];
                        }
                    }
                    $old_video_detail = [];
                    $new_image_detail = [];
                    foreach ($item_old_value as $key1 => $img) {
                        if ($img['item_type'] == 'IMAGE') {
                            if (in_array($img['item_url'], $image)) {
                                $item_key = array_search($img['item_url'], array_column($image_detail, "item_url"));
                                if (isset($d_img_roll)) {
                                    $roll = $image_detail[$item_key]['image_role'];
                                } else {
                                    $roll = $img['image_role'];
                                }
                                $new_image_detail[] = [
                                    "item_url" => $img['item_url'],
                                    "alt_text" => $image_detail[$item_key]['alt_text'],
                                    "image_role" => $roll,
                                    "item_type" => $img['item_type'],
                                    "thum_url" => $img['thum_url'],
                                    "bynder_md_id" => $img['bynder_md_id'],
                                    "is_import" => $img['is_import']
                                ];
                            }
                            $total_new_value = count($new_image_detail);
                            if ($total_new_value > 1) {
                                foreach ($new_image_detail as $nn => $n_img) {
                                    if ($n_img['item_type'] == "IMAGE" && $nn != ($total_new_value - 1)) {
                                        if ($new_magento_role_option_array[$item_key] != "###") {
                                            $new_mg_role_array = (array)$new_magento_role_option_array[$item_key];
                                            if (count($n_img["image_role"]) > 0 && count($new_mg_role_array) > 0) {
                                                $result_val=array_diff($n_img["image_role"], $new_mg_role_array);
                                                $new_image_detail[$nn]["image_role"] = $result_val;
                                            }
                                        }
                                    }
                                }
                            }
                        } elseif ($img['item_type'] == 'VIDEO') {
                            $old_video_detail[] = [
                                "item_url" => $img['item_url'],
                                "image_role" => null,
                                "item_type" => $img['item_type'],
                                "thum_url" => $img['thum_url'],
                                "bynder_md_id" => $img['bynder_md_id']
                            ];
                        }
                    }
                    $array_merge = array_merge($new_image_detail, $diff_image_detail);
                    $final_array_merge = array_merge($array_merge, $old_video_detail);
                    $type = [];
                    $image = [];
                    $media_id = [];
                    foreach ($final_array_merge as $img) {
                        $type[] = $img['item_type'];
                        $image[] = $img['item_url'];
                        $media_id[] = $img['bynder_md_id'];
                        $this->getDeleteMedaiDataTable($product_sku_key, $img['bynder_md_id']);
                    }
                    $this->getInsertMedaiDataTable($product_sku_key, $media_id, $product_ids, $storeId);
                    $image_value_array = implode(',', $image);
                    $flag = 0;
                    if (in_array("IMAGE", $type) && in_array("VIDEO", $type)) {
                        $flag = 1;
                    } elseif (in_array("IMAGE", $type)) {
                        $flag = 2;
                    } elseif (in_array("VIDEO", $type)) {
                        $flag = 3;
                    }
                    $new_value_array = json_encode($final_array_merge, true);
                    $data_image_data = [
                        'sku' => $product_sku_key,
                        'message' => $image_value_array,
                        'data_type' => '1',
                        "lable" => "1"
                    ];
                    $this->getInsertDataTable($data_image_data);
                    $updated_values = [
                        'bynder_multi_img' => $new_value_array,
                        'bynder_isMain' => $flag,
						'use_bynder_cdn' => 1
                    ];
                    $this->productAction->updateAttributes(
                        [$product_ids],
                        $updated_values,
                        $storeId
                    );
                } else {
                    $new_image_array = explode("\n", $img_json);
                    $new_alttext_array = explode("\n", $img_alt_text);
                    $new_magento_role_option_array = $mg_img_role_option;
                    foreach ($new_image_array as $vv => $image_value) {
                        if (trim($image_value) != "" && $image_value != "no image") {
                            $img_altText_val = "";
                            if (isset($new_alttext_array[$vv])) {
                                if ($new_alttext_array[$vv] != "###" && strlen(trim($new_alttext_array[$vv])) > 0) {
                                    $img_altText_val = $new_alttext_array[$vv];
                                }
                            }
                            $curt_img_role = [];
                            if ($new_magento_role_option_array[$vv] != "###") {
                                $curt_img_role = $new_magento_role_option_array[$vv];
                            }
                            $find_video = strpos($image_value, "@@");
                            if (!$find_video) {
                                $image_detail[] = [
                                    "item_url" => $image_value,
                                    "alt_text" => $img_altText_val,
                                    "image_role" => $curt_img_role,
                                    "item_type" => 'IMAGE',
                                    "thum_url" => $image_value,
                                    "bynder_md_id" => $bynder_media_id[$vv],
                                    "is_import" => 0
                                ];
                            }
                            $total_new_value = count($image_detail);
                            if ($total_new_value > 1) {
                                foreach ($image_detail as $nn => $n_img) {
                                    if ($n_img['item_type'] == "IMAGE" && $nn != ($total_new_value - 1)) {
                                        if ($new_magento_role_option_array[$vv] != "###") {
                                            $new_mg_role_array = (array)$new_magento_role_option_array[$vv];
                                            if (count($n_img["image_role"]) > 0 && count($new_mg_role_array) > 0) {
                                                $result_val=array_diff($n_img["image_role"], $new_mg_role_array);
                                                $image_detail[$nn]["image_role"] = $result_val;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $media_id = [];
                    $image = [];
                    foreach ($image_detail as $img) {
                        $type[] = $img['item_type'];
                        $image[] = $img['item_url'];
                        $media_id[] = $img['bynder_md_id'];
                    }
                    $this->getInsertMedaiDataTable($product_sku_key, $media_id, $product_ids, $storeId);
                    $image_value_array = implode(',', $image);
                    $flag = 0;
                    if (in_array("IMAGE", $type) && in_array("VIDEO", $type)) {
                        $flag = 1;
                    } elseif (in_array("IMAGE", $type)) {
                        $flag = 2;
                    } elseif (in_array("VIDEO", $type)) {
                        $flag = 3;
                    }
                    $data_image_data = [
                        'sku' => $product_sku_key,
                        'message' => $image_value_array,
                        'data_type' => '1',
                        "lable" => "1"
                    ];
                    $this->getInsertDataTable($data_image_data);
                    $new_value_array = json_encode($image_detail, true);
                    
                    $updated_values = [
                        'bynder_multi_img' => $new_value_array,
                        'bynder_isMain' => $flag,
						'use_bynder_cdn' => 1
                    ];
                    $this->productAction->updateAttributes(
                        [$product_ids],
                        $updated_values,
                        $storeId
                    );
                }
            } elseif ($select_attribute == "video") {
                if (!empty($image_value)) {
                    $new_video_array = explode(" \n", $img_json);
                    $old_value_array = json_decode($image_value, true);
                    $old_item_url = [];
                    $old_image_details = [];
                    if (!empty($old_value_array)) {
                        foreach ($old_value_array as $value) {
                            $old_item_url[] = $value['item_url'];
                        }
                    }
                    foreach ($new_video_array as $vv => $video_value) {
                        $item_url = explode("@@", $video_value);
                        $thum_url = explode("@@", $video_value);
                        $media_video_explode = explode("/", $item_url[0]);
                        $find_video = strpos($video_value, "@@");
                        if ($find_video) {
                            if (!in_array($item_url[0], $old_item_url)) {
                                $video_detail[] = [
                                    "item_url" => $item_url[0],
                                    "image_role" => null,
                                    "item_type" => 'VIDEO',
                                    "thum_url" => $thum_url[1],
                                    "bynder_md_id" => $bynder_media_id[$vv]
                                ];
                            }
                        }
                    }
                    $array_merge = array_merge($old_value_array, $video_detail);
                    $v_m_id = [];
                    foreach ($array_merge as $img) {
                        $type[] = $img['item_type'];
                        $v_m_id[] = $img['bynder_md_id'];
                        $this->getDeleteMedaiDataTable($product_sku_key, $img['bynder_md_id']);
                    }
                    $this->getInsertMedaiDataTable($product_sku_key, $v_m_id, $product_ids, $storeId);
                    $flag = 0;
                    if (in_array("IMAGE", $type) && in_array("VIDEO", $type)) {
                        $flag = 1;
                    } elseif (in_array("IMAGE", $type)) {
                        $flag = 2;
                    } elseif (in_array("VIDEO", $type)) {
                        $flag = 3;
                    }
                    $new_value_array = json_encode($array_merge, true);
                    $data_video_data = [
                        'sku' => $product_sku_key,
                        'message' => $new_value_array,
                        'data_type' => '3',
                        "lable" => "1"
                    ];
                    $this->getInsertDataTable($data_video_data);
                    $updated_values = [
                        'bynder_multi_img' => $new_value_array,
                        'bynder_isMain' => $flag,
						'use_bynder_cdn' => 1
                    ];
                    $this->productAction->updateAttributes(
                        [$product_ids],
                        $updated_values,
                        $storeId
                    );
                } else {
                    $new_video_array = explode(" \n", $img_json);
                    $video_detail = [];
                    foreach ($new_video_array as $vv => $video_value) {
                        $find_video = strpos($video_value, "@@");
                        if ($find_video) {
                            $item_url = explode("@@", $video_value);
                            $thum_url = explode("@@", $video_value);
                            $media_video_explode = explode("/", $item_url[0]);
                            $video_detail[] = [
                                "item_url" => $item_url[0],
                                "image_role" => null,
                                "item_type" => 'VIDEO',
                                "thum_url" => $thum_url[1],
                                "bynder_md_id" => $bynder_media_id[$vv]
                            ];
                        }
                        
                    }
                    $video_m_id = [];
                    foreach ($video_detail as $img) {
                        $type[] = $img['item_type'];
                        $video_m_id[] = $img['bynder_md_id'];
                    }
                    $this->getInsertMedaiDataTable($product_sku_key, $video_m_id, $product_ids, $storeId);
                    $flag = 0;
                    if (in_array("IMAGE", $type) && in_array("VIDEO", $type)) {
                        $flag = 1;
                    } elseif (in_array("IMAGE", $type)) {
                        $flag = 2;
                    } elseif (in_array("VIDEO", $type)) {
                        $flag = 3;
                    }
                    $new_value_array = json_encode($video_detail, true);
                    $data_video_data = [
                        'sku' => $product_sku_key,
                        'message' => $new_value_array,
                        'data_type' => '3',
                        "lable" => "1"
                    ];
                    $this->getInsertDataTable($data_video_data);
                    $updated_values = [
                        'bynder_multi_img' => $new_value_array,
                        'bynder_isMain' => $flag,
						'use_bynder_cdn' => 1
                    ];
                    $this->productAction->updateAttributes(
                        [$product_ids],
                        $updated_values,
                        $storeId
                    );
                }
            } elseif ($select_attribute == "document") {
                if (empty($doc_value)) {
                    $new_doc_array = explode(" \n", $img_json);
                    $doc_detail = [];
                    foreach ($new_doc_array as $vv => $doc_value) {
                        //$item_url = explode("?", $doc_value);
						$doc_name = explode("@@", $doc_value);
                        $media_doc_explode = explode("/", $doc_name[0]);
                        $doc_detail[] = [
                            "item_url" => $doc_name[0],
                            "item_type" => 'DOCUMENT',
							"doc_name" => $doc_name[1],
                            "bynder_md_id" => $bynder_media_id[$vv]
                        ];
                    }
                    $new_value_array = json_encode($doc_detail, true);
                    $data_doc_value = [
                        'sku' => $product_sku_key,
                        'message' => $new_value_array,
                        'data_type' => '2',
                        "lable" => "1"
                    ];
                    $this->productAction->updateAttributes(
                        [$product_ids],
                        ['bynder_document' => $new_value_array],
                        $storeId
                    );
                } else {
                    $item_old_value = json_decode($doc_values, true);
                    if (is_array($item_old_value)) {
                        if (count($item_old_value) > 0) {
                            foreach ($item_old_value as $doc) {
                                if ($doc['item_type'] == 'DOCUMENT') {
                                    $all_item_url[] = $doc['item_url'];
                                    $b_id[] = $doc['bynder_md_id'];
                                }
                            }
                        }
                    }
                    $new_doc_array = explode("\n", $img_json);
                    $bynder_media_id = $bynder_media_ids[$product_sku_key];
                    $doc_detail = [];
                    foreach ($new_doc_array as $vv => $doc_value) {
                        if(!empty($doc_value)){
                            $item_url = explode("?", $doc_value);
                            $doc_name = explode("@@", $doc_value);
                            $media_doc_explode = explode("/", $item_url[0]);
                            if(!in_array($bynder_media_id[$vv], $b_id)) {
                                $doc_detail[] = [
                                    "item_url" => $doc_name[0],
                                    "item_type" => 'DOCUMENT',
                                    "doc_name" => $doc_name[1],
                                    "bynder_md_id" => $bynder_media_id[$vv],
                                ];
                                $data_doc_value = [
                                    'sku' => $product_sku_key,
                                    'message' => $doc_name[0],
                                    'data_type' => '2',
                                    'media_id' => $bynder_media_id[$vv],
                                    'lable' => 1
                                ];
                                $this->getInsertDataTable($data_doc_value);
                            }
                            
                        }
                    }
                    $array_merg = array_merge($item_old_value, $doc_detail);
                    $new_value_array = json_encode($array_merg, true);
                    $this->productAction->updateAttributes(
                        [$product_ids],
                        ['bynder_document' => $new_value_array],
                        $storeId
                    );
                }
            } elseif ($select_attribute == "all_attribute") {
				if (!empty($image_value)) {
                    $new_image_array = explode("\n", $img_json);
                    $new_alttext_array = explode("\n", $img_alt_text);
                    $new_magento_role_option_array = $mg_img_role_option;
                    $all_item_url = [];
					$all_video_url = [];
                    $item_old_value = json_decode($image_value, true);
                    if (count($item_old_value) > 0) {
                        foreach ($item_old_value as $img) {
                            if ($img['item_type'] == 'IMAGE') {
								$all_item_url[] = $img['item_url'];
							} else {
								$all_video_url[] = $img['item_url'];
							}
                        }
                    }
                    foreach ($new_image_array as $vv => $new_image_value) {
                        if (trim($new_image_value) != "" && $new_image_value != "no image") {
                            $item_url = explode("?", $new_image_value);
                            $media_image_explode = explode("/", $item_url[0]);
                            $img_altText_val = "";
                            if (isset($new_alttext_array[$vv])) {
                                if ($new_alttext_array[$vv] != "###" && strlen(trim($new_alttext_array[$vv])) > 0) {
                                    $img_altText_val = $new_alttext_array[$vv];
                                }
                            }
                            $curt_img_role = [];
                            if ($new_magento_role_option_array[$vv] != "###") {
                                $curt_img_role = $new_magento_role_option_array[$vv];
                            }
                            $find_video = strpos($new_image_value, "@@");
                            if (!$find_video) {
                                $image_detail[] = [
                                    "item_url" => $new_image_value,
                                    "alt_text" => $img_altText_val,
                                    "image_role" => $curt_img_role,
                                    "item_type" => 'IMAGE',
                                    "thum_url" => $item_url[0],
                                    "bynder_md_id" => $bynder_media_id[$vv],
                                    "is_import" => 0
                                ];
                                if (!in_array($item_url[0], $all_item_url)) {
                                    $diff_image_detail[] = [
                                        "item_url" => $new_image_value,
                                        "alt_text" => $img_altText_val,
                                        "image_role" => $curt_img_role,
                                        "item_type" => 'IMAGE',
                                        "thum_url" => $new_image_value,
                                        "bynder_md_id" => $bynder_media_id[$vv],
                                        "is_import" => 0
                                    ];
                                    if (count($item_old_value) > 0) {
                                        foreach ($item_old_value as $kv => $img) {
                                            if ($img['item_type'] == "IMAGE") {
                                                /* here changes by me but not tested */
                                                if ($new_magento_role_option_array[$vv] != "###") {
                                                    $new_mg_role_array = (array)$new_magento_role_option_array[$vv];
                                                    if (count($img["image_role"])>0 && count($new_mg_role_array)>0) {
                                                        $result_val=array_diff($img["image_role"], $new_mg_role_array);
                                                        $item_old_value[$kv]["image_role"] = $result_val;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    $total_new_value = count($diff_image_detail);
                                    if ($total_new_value > 1) {
                                        foreach ($diff_image_detail as $nn => $n_img) {
                                            if ($n_img['item_type'] == "IMAGE" && $nn != ($total_new_value - 1)) {
                                                if ($new_magento_role_option_array[$vv] != "###") {
                                                    $new_mg_role_array = (array)$new_magento_role_option_array[$vv];
                                                    if (count($n_img["image_role"])>0 && count($new_mg_role_array)>0) {
                                                        $result_val=array_diff($n_img["image_role"], $new_mg_role_array);
                                                        $diff_image_detail[$nn]["image_role"] = $result_val;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            } else {
								$item_url = explode("@@", $new_image_value);
								$video_detail_diff = [];
								$video_detail = [];
                                if (!empty($new_image_value)) {
                                    $video_detail[] = [
                                        "item_url" => $item_url[0],
                                        "image_role" => null,
                                        "item_type" => 'VIDEO',
                                        "thum_url" => $item_url[1],
                                        "bynder_md_id" => $bynder_media_id[$vv]
                                    ];
                                    if (!in_array($item_url[0], $all_video_url)) {
                                        $video_detail_diff[] = [
                                            "item_url" => $item_url[0],
                                            "image_role" => null,
                                            "item_type" => 'VIDEO',
                                            "thum_url" => $item_url[1],
                                            "bynder_md_id" => $bynder_media_id[$vv]
                                        ];
                                        $data_video_data = [
                                            'sku' => $product_sku_key,
                                            'message' => $item_url[0],
                                            'data_type' => '3',
                                            'media_id' => $bynder_media_id[$vv],
                                            'lable' => 1
                                        ];
                                        $this->getInsertDataTable($data_video_data);
                                    }
                                }
							}
                        }
                    }
					$merge_both = array_merge($image_detail, $video_detail);
                    $merge_both_diff = array_merge($diff_image_detail, $video_detail_diff);
                    $d_img_roll = "";
                    $d_media_id = [];
					$image_dataa = [];
					$images = [];
                    if (count($merge_both_diff) > 0) {
                        foreach ($merge_both_diff as $d_img) {
                            if($d_img['item_type'] == "IMAGE") {
                                $d_img_roll = $d_img['image_role'];
                            }
                            $d_media_id[] =  $d_img['bynder_md_id'];
                        }
						foreach ($merge_both as $img) {
							if ($img['item_type'] == "IMAGE") {
								$image_dataa[] = $img;
								$images[] = $img['item_url'];
							} else {
								$video_data[] = $img;
								$video[] = $img['item_url'];
							}
						}
                        $this->getInsertMedaiDataTable($product_sku_key, $d_media_id, $product_ids, $storeId);
						$new_image_details = [];
						$new_image_detail_videos = [];
						if (is_array($item_old_value)) {
							foreach ($item_old_value as $img) {
								if ($img['item_type'] == 'IMAGE') {
									$item_img_url = $img['item_url'];
								
									if (in_array($item_img_url, $images)) {
										$item_key = array_search($img['item_url'], array_column($image_dataa, "item_url"));
										$new_image_details[] = [
											"item_url" => $item_img_url,
											"alt_text" => $img['alt_text'],
											"image_role" => $image_detail[$item_key]['image_role'],
											"item_type" => $img['item_type'],
											"thum_url" => $img['thum_url'],
											"bynder_md_id" => $img['bynder_md_id'],
											"is_import" => $img['is_import']
										];
									} 
								} 
								if ($img['item_type'] == 'VIDEO') {
									$item_video_url = $img['item_url'];
									if (in_array($item_video_url, $video)) {
										$new_image_detail_videos[] = [
											"item_url" => $img['item_url'],
											"image_role" => $img['image_role'],
											"item_type" => $img['item_type'],
											"thum_url" => $img['thum_url'],
											"bynder_md_id" => $img['bynder_md_id']
										];
									}
								}
							}
						}
						
						if (!empty($new_image_details) && !empty($new_image_detail_videos)) {
							$new_data = array_merge($new_image_details, $new_image_detail_videos);
							$array_merge = array_merge($new_data, $merge_both_diff);
						} else {
							$array_merge = array_merge($item_old_value, $merge_both_diff);
						}
                        
                    } else {
                        $new_image_detail = [];
                        $new_image_detail_image = [];
                        $new_image_detail_video = [];
                        $image = [];
                        $video = [];
                        $image_data = [];
                        $video_data = [];
                        if (count($merge_both) > 0) {
                            foreach ($merge_both as $img) {
                                if ($img['item_type'] == "IMAGE") {
                                    $image_data[] = $img; // Store the full data
                                    $image[] = $img['item_url'];
                                } else {
                                    $video_data[] = $img; // Store the full data
                                    $video[] = $img['item_url'];
                                }
                            }
                            if (is_array($item_old_value)) {
                                if (is_array($all_video_url) && count($all_video_url) == 0 && count($all_item_url) > 0) {
                                    foreach ($item_old_value as $img) {
                                        if ($img['item_type'] == 'IMAGE') {
                                            $item_img_url = $img['item_url'];
                                        }
                                        if (in_array($item_img_url, $image)) {
                                            $item_key = array_search($img['item_url'], array_column($image_data, "item_url"));
											if (isset($d_img_roll)) {
												$roll = $image_detail[$item_key]['image_role'];
											} else {
												$roll = $img['image_role'];
											}
                                            $new_image_detail[] = [
                                                "item_url" => $item_img_url,
                                                "alt_text" => $img['alt_text'],
                                                "image_role" => $roll,
                                                "item_type" => $img['item_type'],
                                                "thum_url" => $img['thum_url'],
                                                "bynder_md_id" => $img['bynder_md_id'],
                                                "is_import" => $img['is_import']
                                            ];
                                        }
                                    }
                                    if(!empty($new_image_detail)) {
                                        $array_merge = array_merge($new_image_detail, $video_data);
                                    } else {
                                        $array_merge = array_merge($item_old_value, $video_data);
                                    }
                                } elseif (count($all_video_url) > 0 && count($all_item_url) == 0) {
                                    foreach ($item_old_value as $img) {
                                        if ($img['item_type'] == 'VIDEO') {
                                            $item_video_url = $img['item_url'];
                                        }
                                        if (in_array($item_video_url, $video)) {
                                            $new_image_detail_video[] = [
                                                "item_url" => $item_video_url,
                                                "image_role" => $img['image_role'],
                                                "item_type" => $img['item_type'],
                                                "thum_url" => $img['thum_url'],
                                                "bynder_md_id" => $img['bynder_md_id']
                                            ];
                                        }
                                    }
                                    if(!empty($new_image_detail_video)) {
                                        $array_merge = array_merge($new_image_detail_video, $image_data);
                                    } else {
                                        $array_merge = array_merge($item_old_value, $image_data);
                                    }
                                } elseif (count($all_video_url) > 0 && count($all_item_url) > 0) {
                                    foreach ($item_old_value as $img) {
                                        if ($img['item_type'] == 'IMAGE') {
                                            $item_img_url = $img['item_url'];
                                            if (in_array($item_img_url, $image)) {
                                                $item_key = array_search($img['item_url'], array_column($image_data, "item_url"));
                                                if (isset($d_img_roll)) {
													$roll = $image_detail[$item_key]['image_role'];
												} else {
													$roll = $img['image_role'];
												}
                                                $new_image_detail_image[] = [
                                                    "item_url" => $item_img_url,
                                                    "alt_text" => $img['alt_text'],
                                                    "image_role" => $roll,
                                                    "item_type" => $img['item_type'],
                                                    "thum_url" => $img['thum_url'],
                                                    "bynder_md_id" => $img['bynder_md_id'],
                                                    "is_import" => $img['is_import'],
                                                ];
                                            }
                                        }
										if ($img['item_type'] == 'VIDEO') {
                                            $item_video_url = $img['item_url'];
                                            if (in_array($item_video_url, $video)) {
                                                $new_image_detail_video[] = [
                                                    "item_url" => $img['item_url'],
                                                    "image_role" => $img['image_role'],
                                                    "item_type" => $img['item_type'],
                                                    "thum_url" => $img['thum_url'],
                                                    "bynder_md_id" => $img['bynder_md_id'],
                                                ];
                                            }
                                        }
                                    }
                                    if (!empty($new_image_detail_image) && !empty($new_image_detail_video)) {
                                        $array_merge = array_merge($new_image_detail_image, $new_image_detail_video);
                                    } else {
                                        $array_merge = array_merge($item_old_value, $merge_both);
                                    }
                                }
                            }
                        }
                    }
                    $m_id = [];
                    foreach ($array_merge as $img) {
                        $type[] = $img['item_type'];
                        $m_id[] = $img['bynder_md_id'];
                        $this->getDeleteMedaiDataTable($product_sku_key, $img['bynder_md_id']);
                    }
                    $this->getInsertMedaiDataTable($product_sku_key, $m_id, $product_ids, $storeId);
                    
                    $flag = 0;
                    if (in_array("IMAGE", $type) && in_array("VIDEO", $type)) {
                        $flag = 1;
                    } elseif (in_array("IMAGE", $type)) {
                        $flag = 2;
                    } elseif (in_array("VIDEO", $type)) {
                        $flag = 3;
                    }
					$new_value_array = json_encode($array_merge, true);
                    $updated_values = [
                        'bynder_multi_img' => $new_value_array,
                        'bynder_isMain' => $flag,
						'use_bynder_cdn' => 1
                    ];
                    $this->productAction->updateAttributes(
                        [$product_ids],
                        $updated_values,
                        $storeId
                    );
                } else {
                    $new_image_array = explode("\n", $img_json);
                    $new_alttext_array = explode("\n", $img_alt_text);
                    $new_magento_role_option_array = $mg_img_role_option;
                    foreach ($new_image_array as $vv => $image_value) {
                        if (trim($image_value) != "" && $image_value != "no image") {
                            $img_altText_val = "";
                            if (isset($new_alttext_array[$vv])) {
                                if ($new_alttext_array[$vv] != "###" && strlen(trim($new_alttext_array[$vv])) > 0) {
                                    $img_altText_val = $new_alttext_array[$vv];
                                }
                            }
                            $curt_img_role = [];
                            if ($new_magento_role_option_array[$vv] != "###") {
                                $curt_img_role = $new_magento_role_option_array[$vv];
                            }
                            $find_video = strpos($image_value, "@@");
                            if (!$find_video) {
                                $image_detail[] = [
                                    "item_url" => $image_value,
                                    "alt_text" => $img_altText_val,
                                    "image_role" => $curt_img_role,
                                    "item_type" => 'IMAGE',
                                    "thum_url" => $image_value,
                                    "bynder_md_id" => $bynder_media_id[$vv],
                                    "is_import" => 0
                                ];
								$data_image_data = [
                                    'sku' => $product_sku_key,
                                    'message' => $image_value,
                                    'data_type' => '1',
                                    'media_id' => $bynder_media_id[$vv],
                                    'lable' => 1
                                ];
                                $this->getInsertDataTable($data_image_data);
								$total_new_value = count($image_detail);
								if ($total_new_value > 1) {
									foreach ($image_detail as $nn => $n_img) {
										if ($n_img['item_type'] == "IMAGE" && $nn != ($total_new_value - 1)) {
											if ($new_magento_role_option_array[$vv] != "###") {
												$new_mg_role_array = (array)$new_magento_role_option_array[$vv];
												if (count($n_img["image_role"]) > 0 && count($new_mg_role_array) > 0) {
													$result_val=array_diff($n_img["image_role"], $new_mg_role_array);
													$image_detail[$nn]["image_role"] = $result_val;
												}
											}
										}
									}
								}
                            } else {
								if (!empty($image_value)) {
                                    $item_url = explode("@@", $image_value);
                                    $media_video_explode = explode("/", $item_url[0]);
                                    $video_detail[] = [
                                        "item_url" => $item_url[0],
                                        "image_role" => null,
                                        "item_type" => 'VIDEO',
                                        "thum_url" => $item_url[1],
                                        "bynder_md_id" => $bynder_media_id[$vv]
                                    ];
                                    $data_video_data = [
                                        'sku' => $product_sku_key,
                                        'message' => $item_url[0],
                                        'data_type' => '3',
                                        'media_id' => $media_video_explode[5],
                                        'lable' => 1
                                    ];
                                    $this->getInsertDataTable($data_video_data);
                                }
							}
                            
                        }
                    }
                    $media_id = [];
                    $image = [];
					$type = [];
					$both_merge = array_merge($image_detail, $video_detail);
                    foreach ($both_merge as $img) {
                        $type[] = $img['item_type'];
                        $image[] = $img['item_url'];
                        $media_id[] = $img['bynder_md_id'];
                    }
                    $this->getInsertMedaiDataTable($product_sku_key, $media_id, $product_ids, $storeId);
                    $image_value_array = implode(',', $image);
                    $flag = 0;
                    if (in_array("IMAGE", $type) && in_array("VIDEO", $type)) {
                        $flag = 1;
                    } elseif (in_array("IMAGE", $type)) {
                        $flag = 2;
                    } elseif (in_array("VIDEO", $type)) {
                        $flag = 3;
                    }
                    $new_value_array = json_encode($both_merge, true);
                    
                    $updated_values = [
                        'bynder_multi_img' => $new_value_array,
                        'bynder_isMain' => $flag,
						'use_bynder_cdn' => 1
                    ];
                    $this->productAction->updateAttributes(
                        [$product_ids],
                        $updated_values,
                        $storeId
                    );
                }
			}
        } catch (\Exception $e) {
            return $result->setData(['message' => $e->getMessage()]);
        }
    }
}
