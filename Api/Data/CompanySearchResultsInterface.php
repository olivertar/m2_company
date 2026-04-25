<?php
/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orangecat\Company\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface CompanySearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get Company List
     *
     * @return \Orangecat\Company\Api\Data\CompanyInterface[]
     */
    public function getItems();

    /**
     * Set Company List
     *
     * @param \Orangecat\Company\Api\Data\CompanyInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
