<?php
/**
 * @var \fields\models\Field $field
 * @var string $returnUrl
 */

return [
    'group-1' => [
        [
            'label' => '<i class="material-icons">arrow_back</i>',
            'encode' => false,
            'options' => [
                'title' => Yii::t('fields/validators', 'Back to fields` list'),
                'data-pjax' => 'false'
            ],
            'url' => [
                isset($returnUrl) ? $returnUrl : 'fields/index',
            ],
        ],
        [
            'label' => Yii::t('fields/validators', 'Create'),
            'options' => [
                'data-toggle' => 'modal',
                'data-target' => '#validators-modal',
                'data-reload' => 'true',
                'data-pjax' => 'false',
                'data-persistent' => 'true'
            ],
            'url' => [
                'create', 'field_uuid' => $field->uuid
            ],
        ]
    ],
    'group-2' => [
        [
            'label' => '<i class="material-icons">more_vert</i>',
            'encode' => false,
            'menuOptions' => ['class' => 'dropdown dropdown_right'],
            'items' => [
                [
                    'label' => Yii::t('fields/validators', 'Refresh'),
                    'url' => \yii\helpers\Url::current()
                ]
            ]
        ],
    ],
    'selected' => [
        [
            'label' => '<i class="material-icons">delete</i>',
            'encode' => false,
            'url' => ['delete'],
            'options' => [
                'data-http-method' => 'delete',
                'data-confirm' => 'true',
                'data-toggle' => 'action',
                'data-pjax' => 'false',
                'title' => Yii::t('fields/validators', 'Delete selected items')
            ],
        ],
    ],
];