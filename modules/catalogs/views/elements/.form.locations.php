<?php
/**
 * @var \yii\web\View $this
 * @var \catalogs\models\Element $model
 * @var \yii\widgets\ActiveForm $form
 */

use catalogs\models\ElementTree;
use yii\helpers\Html;

/* @var ElementTree[] $nodes */
$nodes = ElementTree::find()
    ->where(['tree_uuid' => $model->locations])
    ->orderBy(['left' => SORT_ASC])
    ->all();

/* @var ElementTree $root */
$root = $model->catalog;
if ($model->node) {
    $root = ElementTree::find()
        ->where(['root' => $model->node->root, 'level' => 0])
        ->one();
}

$node = ElementTree::findOne(['tree_uuid' => Yii::$app->request->get('tree_uuid')]);
$tree_uuid = $node->isRoot() ? $node->tree_uuid : $node->parents(1)->one()->tree_uuid;
?>

<?= \app\widgets\Toolbar::widget([
    'options' => ['class' => 'toolbar toolbar_light'],
    'buttons' => [
        'group-1' => [
            [
                'label' => Yii::t('catalogs/elements', 'Add'),
                'encode' => false,
                'url' => ['locations/index', 'tree_uuid' => $tree_uuid],
                'options' => [
                    'title' => Yii::t('catalogs/elements', 'Add location'),
                    'class' => 'toolbar-btn toolbar-btn_primary',
                    'data-toggle' => 'modal',
                    'data-target' => '#locations-modal',
                    'data-reload' => 'true',
                ],
            ],
        ],
        'group-2' => [
            [
                'label' => Yii::t('catalogs/elements', 'Root'),
                'encode' => false,
                'url' => ['locations/index', 'tree_uuid' => $tree_uuid],
                'options' => [
                    'title' => Yii::t('catalogs/elements', 'Add catalog`s root'),
                    'class' => 'toolbar-btn',
                    'data-toggle' => 'location-root',
                    'data-location' => $root->tree_uuid
                ],
            ],
        ]
    ],
]); ?>

<div class="form-group field-<?= Html::getInputId($model, 'locations'); ?>">
    <div class="locations">
        <?= Html::activeHiddenInput($model, 'locations[]', ['value' => null, 'id' => false]); ?>
        <?php if ($model->locations): ?>
            <?php foreach ($model->locations as $location): ?>
                <?= Html::activeHiddenInput($model, 'locations[]', ['value' => $location, 'id' => false]); ?>
            <?php endforeach; ?>
        <?php endif; ?>
        <ul class="locations-list">
            <?php foreach ($nodes as $node): ?>
                <li class="locations-list__item">
                    <span class="locations-list__title"><?= $node->isRoot() ? Yii::t('catalogs/elements', 'Catalog root') : $node->element->title; ?></span>
                    <span class="locations-list__comment">
                        <?php if ($node->isRoot()): ?>
                            <?= Yii::t('catalogs/elements', 'An element is placed under catalog`s root'); ?>
                        <?php else: ?>
                            <?= implode(' // ', \yii\helpers\ArrayHelper::merge([Yii::t('catalogs/elements', 'Catalog root')], $node->getCanonicalPath())); ?>
                        <?php endif; ?>
                    </span>
                    <a href="#" class="locations-list__action" title="<?= Yii::t('catalogs/elements', 'Remove location`s link'); ?>" data-toggle="location-remove" data-location="<?= $node->tree_uuid; ?>"><i class="material-icons">clear</i></a>
                </li>
            <?php endforeach; ?>
        </ul>
        <ul class="locations-list_template">
            <li class="locations-list__item">
                <span class="locations-list__title">{title}</span>
                <span class="locations-list__comment">{comment}</span>
                <a href="#" class="locations-list__action" title="<?= Yii::t('catalogs/elements', 'Remove location`s link'); ?>" data-toggle="location-remove" data-location="{tree_uuid}"><i class="material-icons">clear</i></a>
            </li>
        </ul>
    </div>
    <?= Html::error($model, 'locations', ['class' => 'form-group__error']); ?>
</div>