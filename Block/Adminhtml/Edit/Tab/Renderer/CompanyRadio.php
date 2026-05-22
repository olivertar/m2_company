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

namespace Orangecat\Company\Block\Adminhtml\Edit\Tab\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\Radio;
use Magento\Framework\DataObject;

class CompanyRadio extends Radio
{
    /**
     * Renders grid column
     *
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        $html = parent::render($row);

        // Manual check logic
        $values = $this->getColumn()->getValues();
        $rowId = $row->getData($this->getColumn()->getIndex());

        if ($values && in_array($rowId, $values)) {
            if (strpos($html, 'checked') === false) {
                // If not checked by parent but should be, add checked attribute
                $html = str_replace('<input ', '<input checked="checked" ', $html);
            }
        }

        return str_replace('<input ', '<input data-form-part="customer_form" ', $html);
    }
}
