<?php

/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orangecat\Company\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Orangecat\Company\Model\ResourceModel\Role\CollectionFactory;

class CompanyRole implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var array
     */
    private $options;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
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
