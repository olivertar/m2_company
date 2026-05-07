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
use Orangecat\Company\Model\ResourceModel\Role\CollectionFactory;

class CompanyRole implements OptionSourceInterface
{
    /**
     * @var array|null
     */
    private ?array $options = null;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        private CollectionFactory $collectionFactory
    ) {
    }

    /**
     * ToOptionArray
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $this->options = [];
            $collection = $this->collectionFactory->create();
            foreach ($collection as $role) {
                $this->options[] = [
                    'value' => $role->getId(),
                    'label' => $role->getRoleName()
                ];
            }
        }
        return $this->options;
    }
}
