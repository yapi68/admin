<?php
/**
 * @var \mail\models\TypeSettings $settings
 */
use mail\models\Type;
use yii\helpers\Html;

return [
    [
        'class' => \app\widgets\grid\CheckboxColumn::className(),
        'options' => ['width' => 72],
        'checkboxOptions' => function (Type $model) {
            return ['value' => $model->uuid];
        }
    ],
    [
        'attribute' => 'title',
        'format' => 'raw',
        'value' => function (Type $data) use ($settings) {
            $description = null;
            $title = Html::a($data->title, ['edit', 'uuid' => $data->uuid], [
                'title' => Yii::t('forms', 'View & edit type properties'),
                'class' => 'type__title',
                'data-toggle' => 'modal',
                'data-target' => '#types-modal',
                'data-pjax' => 'false',
                'data-reload' => 'true',
                'data-persistent' => 'true'
            ]);

            if ($settings->showDescription) {
                $description = Html::tag('span', strip_tags($data->description), [
                    'class' => 'type__description'
                ]);
            }

            return $title . $description;
        }
    ],
    [
        'attribute' => 'code',
        'options' => ['width' => 200],
    ],
    [
        'attribute' => 'workflow.modified_date',
        'format' => 'datetime',
        'options' => ['width' => 200]
    ],
    [
        'class' => \app\widgets\grid\ActionColumn::className(),
        'items' => function (
            /** @noinspection PhpUnusedParameterInspection */ $model
        ) {
            return require '.context.php';
        },
        'options' => ['width' => 72],
        'contentOptions' => ['class' => 'action-column action-column_right']
    ],
];