<?php
/**
 * @var \yii\web\View $this
 * @var \yii\data\ActiveDataProvider $dataProvider
 * @var boolean $isFiltered
 */

$this->title = sprintf('%s — %s',
    Yii::t('app', 'Control panel'),
    Yii::t('users', 'Users')
);

// Registering assets
\users\assets\UsersAsset::register($this);

?>
<div id="users-pjax" data-pjax-container="true">

    <?= \app\widgets\Toolbar::widget([
        'buttons' => require_once ".toolbar.php",
        'isFiltered' => $isFiltered
    ]); ?>

    <?= \app\widgets\grid\GridView::widget([
        'id' => 'users-grid',
        'dataProvider' => $dataProvider,
        'columns' => require_once ".grid.php",
        'tableOptions' => ['class' => implode(' ', [
            'grid-view__table',
            'grid-view__table_fixed'
        ])],
    ]); ?>

</div>

<div class="modal" id="confirm-modal" role="dialog" data-persistent="true">
    <?= $this->render('@app/views/layouts/confirm'); ?>
</div>