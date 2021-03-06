<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */

Yii::setAlias('@fields', '@app/modules/fields');
Yii::setAlias('@forms', '@app/modules/forms');

return [
    'uuid' => \Ramsey\Uuid\Uuid::uuid3(\Ramsey\Uuid\Uuid::NAMESPACE_URL, 'form-field-' . $index)->toString(),
    'label' => $faker->text(50),
    'description' => $faker->text(),
    'code' => 'FORM_FIELD_' . $index,
    'type' => \forms\modules\admin\modules\fields\models\Field::FIELD_TYPE_LIST,
    'multiple' => false,
    'default' => '',
    'options' => '',
    'active' => true,
    'list' => false,
    'sort' => 100,
    'form_uuid' => \Ramsey\Uuid\Uuid::uuid3(\Ramsey\Uuid\Uuid::NAMESPACE_URL, 'form-0')->toString(),
    'workflow_uuid' => \Ramsey\Uuid\Uuid::uuid3(\Ramsey\Uuid\Uuid::NAMESPACE_URL, 'workflow-' . $index)->toString()
];