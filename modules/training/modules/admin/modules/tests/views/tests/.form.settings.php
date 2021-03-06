<?php
/**
 * @var \yii\web\View $this
 * @var \training\modules\admin\models\Test $model
 * @var \app\models\Workflow $workflow
 * @var \app\widgets\form\ActiveForm $form
 */

use app\models\WorkflowStatus;

?>

<div class="grid">
    <div class="grid__item">
        <?= $form->field($model, 'questions_random')->switch(); ?>
    </div>
    <div class="grid__item">
        <?= $form->field($model, 'answers_random')->switch(); ?>
    </div>
</div>

<?= $form->field($workflow, 'status')->dropDownList(WorkflowStatus::getList()); ?>