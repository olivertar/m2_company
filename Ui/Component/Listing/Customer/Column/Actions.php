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

namespace Orangecat\Company\Ui\Component\Listing\Customer\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Listing actions for customers in modal
 */
class Actions extends Column
{
    /**
     * Actions constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        private UrlInterface       $urlBuilder,
        array              $components = [],
        array              $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                if (isset($item['entity_id'])) {
                    if ($this->context->getRequestParam('company_admin_id') == $item['entity_id']) {
                        continue;
                    }

                    $item[$name]['edit'] = [
                        'callback' => [
                            [
                                'provider' => 'mycompany_company_form.mycompany_company_form.general'
                                    . '.customer_model',
                                'target' => 'closeModal',
                            ],
                            [
                                'provider' => 'mycompany_company_form.mycompany_company_form.general'
                                    . '.customer_button',
                                'target' => 'updateData',
                                'params' => [
                                    'entityId' => $item['entity_id'],
                                    'options' => [
                                        [
                                            'label' => __('Email'),
                                            'value' => '<a href="mailto:' . htmlspecialchars((string)$item['email'], ENT_QUOTES, 'UTF-8') . '" target="_blank">'
                                                . htmlspecialchars((string)$item['email'], ENT_QUOTES, 'UTF-8') . '</a>'
                                        ],
                                        [
                                            'label' => __('Name'),
                                            'value' => '<a href="' . $this->urlBuilder->getUrl(
                                                'customer/index/edit',
                                                ['id' => (int)$item['entity_id']]
                                            ) . '" target="_blank">' . htmlspecialchars((string)$item['name'], ENT_QUOTES, 'UTF-8') . '</a>'
                                        ]
                                    ]
                                ],
                            ],
                        ],
                        'href' => '#',
                        'label' => __('Assign'),
                        '__disableTmpl' => true,
                    ];
                }
            }
        }
        return $dataSource;
    }
}
