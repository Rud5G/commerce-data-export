<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurableProductDataExporter\Model\Provider\Product;

use Magento\ConfigurableProductDataExporter\Model\Provider\Product\ProductVariants\ConfigurableId;
use Magento\ProductVariantDataExporter\Model\Provider\ProductVariants\IdFactory;
use Magento\ProductVariantDataExporter\Model\Provider\ProductVariants\OptionValueFactory;
use Magento\ProductVariantDataExporter\Model\Provider\ProductVariantsProviderInterface;
use Magento\ConfigurableProductDataExporter\Model\Query\ProductVariantsQuery;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Configurable product variants provider
 */
class ProductVariants implements ProductVariantsProviderInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductVariantsQuery
     */
    private $variantsOptionValuesQuery;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigurableOptionValueUid
     */
    private $optionValueUid;

    /**
     * @var OptionValueFactory
     */
    private $optionValueFactory;

    /**
     * @var IdFactory
     */
    private $idFactory;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ProductVariantsQuery $variantsOptionValuesQuery
     * @param ConfigurableOptionValueUid $optionValueUid
     * @param OptionValueFactory $optionValueFactory
     * @param IdFactory $idFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductVariantsQuery $variantsOptionValuesQuery,
        ConfigurableOptionValueUid $optionValueUid,
        OptionValueFactory $optionValueFactory,
        IdFactory $idFactory,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->variantsOptionValuesQuery = $variantsOptionValuesQuery;
        $this->logger = $logger;
        $this->optionValueUid = $optionValueUid;
        $this->optionValueFactory = $optionValueFactory;
        $this->idFactory = $idFactory;
    }

    /**
     * @inheritDoc
     *
     * @throws UnableRetrieveData
     */
    public function get(array $values): array
    {
        $output = [];
        $parentIds = [];
        foreach ($values as $value) {
            $parentIds[$value['parent_id']] = $value['parent_id'];
        }

        try {
            $variants = $this->getVariants($parentIds);
            foreach ($variants as $id => $optionValues) {
                $output[] = [
                    'id' => $id,
                    'option_values' => $optionValues['optionValues'],
                    'parent_id' => $optionValues['parentId'],
                ];
            }
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            throw new UnableRetrieveData('Unable to retrieve configurable product variants data');
        }
        return $output;
    }

    /**
     * Get configurable product variants
     *
     * @param array $parentIds
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    private function getVariants(array $parentIds): array
    {
        $variants = [];
        $idResolver = $this->idFactory->get('configurable');
        $optionValueResolver = $this->optionValueFactory->get('configurable');

        $cursor = $this->resourceConnection->getConnection()->query(
            $this->variantsOptionValuesQuery->getQuery($parentIds)
        );
        while ($row = $cursor->fetch()) {
            $id = $idResolver->resolve([
                ConfigurableId::PARENT_ID_KEY => $row['parentId'],
                ConfigurableId::CHILD_ID_KEY => $row['childId']
            ]);
            $optionValueUid = ($this->optionValueUid->resolve(
                $row['attributeId'],
                $row['attributeValue']
            ));
            $optionValue = $optionValueResolver->resolve($row['parentId'], $row['attributeCode'], $optionValueUid);
            $variants[$id]['parentId'] = $row['parentId'];
            $variants[$id]['optionValues'][] = $optionValue;
        }
        return $variants;
    }
}
