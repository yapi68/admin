<?php
/**
 * @var \yii\web\View $this
 * @var \training\modules\admin\models\Test $model
 * @var \app\models\Workflow $workflow
 */
?>

<?= $this->render('.form.php', ['model' => $model, 'title' => 'Copy test', 'workflow' => $workflow]); ?>