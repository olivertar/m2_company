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

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class Status extends AbstractSource
{
    public const PENDING = 0;
    public const APPROVED = 1;
    public const REJECTED = 3;
    public const SUSPENDED = 2;

    /**
     * GetAllOptions
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['value' => self::PENDING, 'label' => __('Pending')],
                ['value' => self::APPROVED, 'label' => __('Approved')],
                ['value' => self::REJECTED, 'label' => __('Rejected')],
                ['value' => self::SUSPENDED, 'label' => __('Suspended')],
            ];
        }
        return $this->_options;
    }
}
