<?php
/**
 * @var \yii\web\View $this
 * @var \yii\data\ActiveDataProvider $dataProvider
 * @var \accounts\models\Account $account
 * @var \fields\models\Property[] $properties
 */

$this->title = sprintf('%s — %s: %s',
    Yii::t('app', 'Control panel'),
    Yii::t('accounts', \accounts\Module::$title),
    Yii::t('accounts/properties', 'Custom fields')
);
?>
<?= $this->render('@fields/views/properties/index', [
    'dataProvider' => $dataProvider,
    'properties' => $properties,
    'returnUrl' => ['accounts/index'],
    'editUrl' => ['edit', 'account_uuid' => $account->uuid, 'field_uuid' => null],
    'title' => $account->title
]); ?>