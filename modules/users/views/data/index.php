<?php
/**
 * @var \yii\web\View $this
 * @var \users\models\UserData $model
 * @var array $data
 */

$updateUrl = \yii\helpers\Url::to(['data/index', 'user_uuid' => $model->user_uuid]);
$fields = $model->getFields();
?>

<br>

<div id="data-pjax" data-pjax-container="true" data-pjax-url="<?= $updateUrl; ?>">

    <?php if (count($fields) > 0): ?>
    <table class="grid-view__table grid-view__table_dense grid-view__table_light">
        <colgroup>
            <col width="1">
            <col>
        </colgroup>
        <thead>
        <tr>
            <th class="text_right text_nowrap"><?= Yii::t('users', 'Property'); ?></th>
            <th><?= Yii::t('users', 'Value'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($fields as $code => $field): ?>
        <tr>
            <td class="text_right text_nowrap"><?= $field->label; ?></td>
            <td><?= array_key_exists($code, $data) ? $data[$code] : Yii::$app->formatter->nullDisplay; ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p class="text_center">
        <?= \yii\helpers\Html::a(Yii::t('app', 'Change'), ['data/edit', 'user_uuid' => $model->user_uuid], [
            'class' => 'btn btn_default',
            'data-toggle' => 'modal',
            'data-target' => '#data-modal',
            'data-reload' => 'true',
            'data-persistent' => 'true'
        ]); ?>
    </p>
    <?php else: ?>
    <div class="grid-view__empty">
        <div class="grid-view__empty-content">
            <?= $this->render('.index.empty.php', ['updateUrl' => $updateUrl]); ?>
        </div>
    </div>
    <?php endif; ?>
</div>