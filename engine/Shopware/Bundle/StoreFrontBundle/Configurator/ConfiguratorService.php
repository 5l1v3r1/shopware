<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Bundle\StoreFrontBundle\Configurator;

use Shopware\Context\Struct\ShopContext;
use Shopware\Bundle\StoreFrontBundle\Product\BaseProduct;

/**
 * @category  Shopware
 *
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class ConfiguratorService implements ConfiguratorServiceInterface
{
    const CONFIGURATOR_TYPE_STANDARD = 0;
    const CONFIGURATOR_TYPE_SELECTION = 1;
    const CONFIGURATOR_TYPE_PICTURE = 2;

    /**
     * @var \Shopware\Bundle\StoreFrontBundle\Configurator\ProductConfigurationGateway
     */
    private $productConfigurationGateway;

    /**
     * @var \Shopware\Bundle\StoreFrontBundle\Configurator\ConfiguratorGateway
     */
    private $configuratorGateway;

    /**
     * @param \Shopware\Bundle\StoreFrontBundle\Configurator\ProductConfigurationGateway $productConfigurationGateway
     * @param ConfiguratorGateway                                                        $configuratorGateway
     */
    public function __construct(
        ProductConfigurationGateway $productConfigurationGateway,
        ConfiguratorGateway $configuratorGateway
    ) {
        $this->configuratorGateway = $configuratorGateway;
        $this->productConfigurationGateway = $productConfigurationGateway;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductsConfigurations($products, ShopContext $context)
    {
        $configuration = $this->productConfigurationGateway->getList($products, $context->getTranslationContext());

        return $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductConfigurator(
        BaseProduct $product,
        ShopContext $context,
        array $selection
    ) {
        $configurator = $this->configuratorGateway->get($product, $context->getTranslationContext());
        $combinations = $this->configuratorGateway->getProductCombinations($product);

        $media = [];
        if ($configurator->getType() === self::CONFIGURATOR_TYPE_PICTURE) {
            $media = $this->configuratorGateway->getConfiguratorMedia(
                $product,
                $context->getTranslationContext()
            );
        }

        $onlyOneGroup = count($configurator->getGroups()) === 1;

        foreach ($configurator->getGroups() as $group) {
            $group->setSelected(
                isset($selection[$group->getId()])
            );

            foreach ($group->getOptions() as $option) {
                $option->setSelected(
                    in_array($option->getId(), $selection)
                );

                $isValid = $this->isCombinationValid(
                    $group,
                    $combinations[$option->getId()],
                    $selection
                );

                $option->setActive(
                    $isValid
                    ||
                    ($onlyOneGroup && isset($combinations[$option->getId()]))
                );

                if (isset($media[$option->getId()])) {
                    $option->setMedia(
                        $media[$option->getId()]
                    );
                }
            }
        }

        return $configurator;
    }

    /**
     * Checks if the passed combination is compatible with the provided customer configurator
     * selection.
     *
     * @param ConfiguratorGroup $group
     * @param array             $combinations
     * @param $selection
     *
     * @return bool
     */
    private function isCombinationValid(ConfiguratorGroup $group, $combinations, $selection)
    {
        if (empty($combinations)) {
            return false;
        }

        foreach ($selection as $selectedGroup => $selectedOption) {
            if (!in_array($selectedOption, $combinations) && $selectedGroup !== $group->getId()) {
                return false;
            }
        }

        return true;
    }
}