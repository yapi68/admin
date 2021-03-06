<?php
/**
 * @var string $returnUrl
 */

return [
    'group-1' => [
        [
            'label' => '<i class="material-icons">arrow_back</i>',
            'encode' => false,
            'url' => $returnUrl,
            'options' => [
                'title' => Yii::t('fields/properties', 'Back to elements` list'),
                'class' => 'toolbar-btn toolbar-btn_back',
                'data-pjax' => 'false'
            ],
        ],
        [
            'label' => $title,
            'options' => [
                'class' => 'toolbar-btn toolbar-btn_title',
            ],
        ]
    ],
];