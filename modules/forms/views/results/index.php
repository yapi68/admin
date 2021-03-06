<?php
/**
 * @var \yii\web\View $this
 * @var \yii\data\ActiveDataProvider $dataProvider
 * @var \forms\modules\admin\models\Form $form
 */

$this->title = sprintf('%s — %s',
    Yii::t('forms', \forms\Module::$title),
    Yii::t('forms/results', 'Results')
);

// Registering assets
\forms\assets\ResultsAsset::register($this);

?>
<div class="section-title">
    <?= Yii::t('forms/results', 'Results for form'); ?> "<?= $form->title; ?>"
</div>
<div id="results-pjax" data-pjax-container="true">

    <?= \app\widgets\Toolbar::widget([
        'buttons' => require_once ".toolbar.php",
    ]); ?>

    <?= \app\widgets\grid\GridView::widget([
        'id' => 'results-grid',
        'dataProvider' => $dataProvider,
        'columns' => require_once ".grid.php",
        'tableOptions' => ['class' => implode(' ', [
            'grid-view__table',
            'grid-view__table_fixed'
        ])],
    ]); ?>

</div>

<div class="modal modal_warning" id="confirm-modal" role="dialog" data-persistent="true">
    <?= $this->render('@app/views/layouts/confirm'); ?>
</div>