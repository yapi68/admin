<?php
/**
 * @var \yii\web\View $this
 * @var \forms\models\FormStatus $model
 * @var string $title
 */

use app\widgets\form\ActiveForm;
use yii\helpers\Html;

?>
<div class="modal__container">

    <?php $form = ActiveForm::begin([
        'options' => [
            'id' => 'statuses-form',
            'data-type' => 'active-form',
        ]
    ]); ?>

    <div class="modal__header">
        <div class="modal__heading"><?= Yii::t('forms/statuses', $title); ?></div>
    </div>
    <div class="modal__body">

        <?php $widget = \app\widgets\Tabs::begin([
            'items' => require_once ".form.tabs.php"
        ]); ?>
            <?php foreach ($widget->items as $index => $item): ?>
                <?= Html::beginTag('div', [
                    'class' => 'tabs-pane' . ($item['active'] === true ? ' active' : ''),
                    'id' => $item['id'],
                    'data-remote' => isset($item['options']['data-remote']) ? $item['id'] : null
                ]); ?>
                    <?= $this->render('.form.' . $item['id'] . '.php', [
                        'model' => $model,
                        'form' => $form,
                    ]); ?>
                <?= Html::endTag('div'); ?>
            <?php endforeach; ?>
        <?php \app\widgets\Tabs::end(); ?>

    </div>
    <div class="modal__footer">
        <div class="grid__item text_small">
            <?= Yii::t('app', 'Fields marked with * are mandatory'); ?>
        </div>
        <div class="grid__item text_right">
            <button type="submit" class="btn btn_primary"><?= Yii::t('app', $model->isNewRecord ? 'Create' : 'Update'); ?></button>
            <button type="button" class="btn btn_default" data-dismiss="modal"><?= Yii::t('app', 'Close'); ?></button>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>