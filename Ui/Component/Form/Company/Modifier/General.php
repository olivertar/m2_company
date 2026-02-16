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

namespace Orangecat\Company\Ui\Component\Form\Company\Modifier;

use Orangecat\Company\Model\Company\Locator;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;

/**
 * Provide the current company data
 */
class General implements ModifierInterface
{
    /**
     * @param Locator $locator
     */
    public function __construct(
        private Locator $locator
    ) {
    }

    /**
     * @inheritDoc
     */
    public function modifyData(array $data): array
    {
        $company = $this->locator->getCompany();
        $entityId = $company->getEntityId();

        if ($entityId) {
            $data[$entityId] = $company->getData();
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function modifyMeta(array $meta): array
    {
        return $meta;
    }
}
