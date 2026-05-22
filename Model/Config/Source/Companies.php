<?php

/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Orangecat\Company\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Orangecat\Company\Model\ResourceModel\Company\CollectionFactory;

class Companies implements OptionSourceInterface
{
    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        private CollectionFactory $collectionFactory
    ) {
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return arrayFormat: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray(): array
    {
        $options = [];
        $collection = $this->collectionFactory->create();

        foreach ($collection as $company) {
            $options[] = [
                'value' => $company->getId(),
                'label' => $company->getName()
            ];
        }

        return $options;
    }
}
