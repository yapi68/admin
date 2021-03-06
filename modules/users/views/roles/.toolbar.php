<?php
/**
 * @var \users\models\User $user
 */

return [
    'group-1' => [
        [
            'label' => '<i class="material-icons">arrow_back</i>',
            'encode' => false,
            'options' => [
                'title' => Yii::t('users', 'Back to users` list'),
                'data-pjax' => 'false'
            ],
            'url' => [
                'users/index',
            ],
        ],
        [
            'label' => Yii::t('users', 'Assign role'),
            'options' => [
                'data-toggle' => 'modal',
                'data-target' => '#roles-modal',
                'data-reload' => 'true',
                'data-persistent' => 'true'
            ],
            'url' => [
                'roles/create',
                'user_uuid' => $user->uuid
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
                    'label' => Yii::t('users', 'Refresh'),
                    'url' => \yii\helpers\Url::current()
                ]
            ]
        ],
    ],
    'selected' => [
        [
            'label' => '<i class="material-icons">delete</i>',
            'encode' => false,
            'url' => ['roles/delete'],
            'options' => [
                'data-http-method' => 'delete',
                'data-confirm' => 'true',
                'data-toggle' => 'action',
                'data-pjax' => 'false',
                'title' => Yii::t('users', 'Delete selected items')
            ],
        ],
    ]
];