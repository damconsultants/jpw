<?php
namespace DamConsultants\JPW\Model\Resolver\Product;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Catalog\Api\ProductRepositoryInterface;

class CustomLabel implements ResolverInterface
{
	protected $productRepository;
	
	public function __construct(
        ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
    }
    public function resolve(
        $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $product = $value['model'];
		
		$fullProduct = $this->productRepository->getById($product->getId());

        return [
            'bynder_multi_img' => $fullProduct->getData('bynder_multi_img'),
            'use_bynder_both_image' => $fullProduct->getData('use_bynder_both_image'),
            'use_bynder_cdn' => $fullProduct->getData('use_bynder_cdn')
        ];
    }
}