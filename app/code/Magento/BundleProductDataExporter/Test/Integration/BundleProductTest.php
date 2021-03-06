<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\BundleProductDataExporter\Test\Integration;

use Magento\CatalogDataExporter\Test\Integration\AbstractProductTestHelper;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test for bundle product export
 */
class BundleProductTest extends AbstractProductTestHelper
{
    /**
     * @var ArrayUtils
     */
    private $arrayUtils;

    /**
     * @inheritDoc
     */
    protected function setUp() : void
    {
        $this->arrayUtils = Bootstrap::getObjectManager()->create(ArrayUtils::class);

        parent::setUp();
    }

    /**
     * Validate bundle product options data
     *
     * @param array $bundleProductOptionsDataProvider
     *
     * @magentoDataFixture Magento/Bundle/_files/product_1.php
     * @dataProvider getBundleProductOptionsDataProvider
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     *
     * @return void
     */
    public function testBundleProductOptions(array $bundleProductOptionsDataProvider) : void
    {
        $extractedProduct = $this->getExtractedProduct('bundle-product', 'default');
        $this->assertNotEmpty($extractedProduct, 'Feed data must not be empty');

        foreach ($bundleProductOptionsDataProvider as $key => $expectedData) {
            $diff = $this->arrayUtils->recursiveDiff($expectedData, $extractedProduct[$key]);
            self::assertEquals([], $diff, 'Actual feed data doesn\'t equal to expected data');
        }
    }

    /**
     * Get bundle product options data provider
     *
     * @return array
     */
    public function getBundleProductOptionsDataProvider() : array
    {
        return [
            'bundleProduct' => [
                'item' => [
                    'feedData' => [
                        'sku' => 'bundle-product',
                        'storeViewCode' => 'default',
                        'name' => 'Bundle Product',
                        'type' => 'bundle',
                        'productOptions' => [
                            [
                                'type' => 'bundle',
                                'render_type' => 'select',
                                'required' => true,
                                'label' => 'Bundle Product Items',
                                'sort_order' => 0,
                                'values' => [
                                    [
                                        'sort_order' => 0,
                                        'label' => 'Simple Product',
                                        'qty' => 1,
                                        'default' => false,
                                        'qty_mutability' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
