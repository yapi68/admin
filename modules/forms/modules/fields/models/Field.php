<?php

namespace forms\modules\fields\models;

use app\components\behaviors\PrimaryKeyBehavior;
use app\components\behaviors\WorkflowBehavior;
use app\models\Workflow;
use forms\models\Form;
use forms\modules\fields\validators\JsonValidator;
use forms\modules\fields\validators\UniqueCodeValidator;
use yii\behaviors\SluggableBehavior;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * Class Field
 *
 * @property string $uuid
 * @property string $label
 * @property string $description
 * @property string $code
 * @property integer $type
 * @property bool $multiple
 * @property string $default
 * @property string $options
 * @property bool $active
 * @property bool $list
 * @property integer $sort
 * @property string $form_uuid
 * @property string $workflow_uuid
 *
 * @property Form $form
 * @property FieldValidator[] $fieldValidators
 * @property FieldValue[] $fieldValues
 * @property Workflow $workflow
 *
 * @package forms\modules\fields\models
 */
class Field extends ActiveRecord
{
    /**
     * Constants
     */
    const
        FIELD_TYPE_DEFAULT = 1,
        FIELD_TYPE_STRING = 1,
        FIELD_TYPE_TEXT = 2,
        FIELD_TYPE_SELECT = 3,
        FIELD_TYPE_LIST = 4,
        FIELD_TYPE_FILE = 5;
    /**
     * User authentication service field.
     */
    const FORM_FIELD_AUTH = 'FORM_FIELD_AUTH';
    /**
     * Captcha protection service field.
     */
    const FORM_FIELD_CAPTCHA = 'FORM_FIELD_CAPTCHA';

    /**
     * @return string
     */
    public static function tableName()
    {
        return '{{%forms_fields}}';
    }

    /**
     * @param $message
     * @param array $params
     * @return string
     */
    public static function t($message, $params = [])
    {
        return \Yii::t('fields', $message, $params);
    }

    /**
     * @return array
     */
    public function transactions()
    {
        return [self::SCENARIO_DEFAULT => self::OP_ALL];
    }

    /**
     * @param $form_uuid
     * @return ActiveDataProvider
     */
    public static function search($form_uuid)
    {
        return new ActiveDataProvider([
            'query' => self::prepareSearchQuery($form_uuid),
            'pagination' => ['defaultPageSize' => 10],
            'sort' => [
                'defaultOrder' => ['workflow.modified_date' => SORT_DESC],
                'attributes' => self::getSortAttributes()
            ]
        ]);
    }

    /**
     * @param $form_uuid
     * @return \yii\db\ActiveQuery
     */
    protected static function prepareSearchQuery($form_uuid)
    {
        return self::find()->joinWith('workflow')->where(['form_uuid' => $form_uuid]);
    }

    /**
     * @return array
     */
    protected static function getSortAttributes()
    {
        $attributes = (new self())->attributes();
        $attributes['workflow.modified_date'] = [
            'asc' => ['{{%workflow}}.[[modified_date]]' => SORT_ASC],
            'desc' => ['{{%workflow}}.[[modified_date]]' => SORT_DESC],
        ];

        return $attributes;
    }

    /**
     * @return array
     */
    public function attributeLabels(): array
    {
        $labels = [
            'label' => 'Label',
            'code' => 'Code',
            'type' => 'Type',
            'active' => 'Active',
            'list' => 'In list',
            'sort' => 'Sort',
            'description' => 'Description',
            'multiple' => 'Multiple',
            'default' => 'Default value',
            'options' => 'Extra HTML-attributes',
            'workflow.modified_date' => 'Modified',
        ];

        return array_map('self::t', $labels);
    }

    /**
     * @return array
     */
    public function attributeHints(): array
    {
        $hints = [
            'active' => 'Whether field is displayed.',
            'multiple' => 'Can accept multiple values.',
            'list' => 'Display field in results` list.',
            'label' => 'Up to 250 characters length.',
            'code' => 'Valid characters is latin letters, numbers and underscore. Will be autogenerated if no value provided.',
            'sort' => 'The numerical value that determines the position of the field in various lists. Default value is `100`.',
            'type' => 'Affects how the field is rendered and validated.',
            'default' => 'Default value is used when no value is set.',
            'options' => 'Provide valid JSON-string, ex. {"name": "value"}.'
        ];

        return array_map('self::t', $hints);
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            [['type', 'label', 'code'], 'required', 'message' => self::t('{attribute} is required.')],
            [['label', 'default'], 'string', 'max' => 250, 'tooLong' => self::t('Maximum {max, number} characters allowed.')],
            [['active', 'multiple', 'list'], 'boolean'],
            [
                'sort',
                'integer',
                'min' => 0,
                'tooSmall' => self::t('Value must be greater than 0.'),
                'message' => self::t('Value must be a integer.')
            ],
            ['description', 'safe'],
            // Code validation rules
            ['code', 'string', 'max' => 50, 'tooLong' => self::t('Maximum {max, number} characters allowed.')],
            ['code', 'validateCode'],
            ['code', 'match', 'pattern' => '/^[a-z_\-\d]*$/i'],
            ['code', UniqueCodeValidator::className(), 'message' => self::t('Field with code `{value}` is already exists.')],
            ['type', 'validateValues'],
            ['list', 'validateList'],
            ['options', JsonValidator::className()]
        ];
    }

    /**
     * @param $attribute
     */
    public function validateCode($attribute)
    {
        $reserved = [self::FORM_FIELD_AUTH, self::FORM_FIELD_CAPTCHA];

        if (in_array($this->$attribute, $reserved)) {
            $this->addError($attribute, self::t('This code is reserved.'));
        }
    }

    /**
     * @param $attribute
     */
    public function validateValues($attribute)
    {
        if ($this->hasValues() && !$this->fieldValues) {
            $this->addError($attribute, self::t('Field values required. Add them on `Values` tab.'));
        }
    }

    /**
     * @return bool
     */
    public function hasValues(): bool
    {
        return in_array($this->type, [self::FIELD_TYPE_LIST, self::FIELD_TYPE_SELECT]);
    }

    /**
     * @param $attribute
     */
    public function validateList($attribute)
    {
        if ($this->$attribute === 1 && $this->type !== self::FIELD_TYPE_STRING) {
            $this->addError($attribute, self::t('Only text fields can be displayed in lists.'));
        }
    }

    /**
     * @return array
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors[] = WorkflowBehavior::className();
        $behaviors[] = PrimaryKeyBehavior::className();
        $behaviors[] = [
            'class' => SluggableBehavior::className(),
            'attribute' => 'label',
            'slugAttribute' => 'code',
            'ensureUnique' => true,
            'uniqueValidator' => ['class' => UniqueCodeValidator::className()],
            'immutable' => true
        ];

        return $behaviors;
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        $isValid = parent::beforeValidate();

        if ($isValid) {
            // Need for valid slug generation
            if (mb_strlen($this->code) > 50) {
                $this->code = mb_substr($this->code, 0, 50);
            }
        }

        return $isValid;
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        $isValid = parent::beforeSave($insert);

        if ($isValid) {
            // Make symbolic code uppercase
            $this->code = mb_strtoupper($this->code);
        }

        return $isValid;
    }

    /**
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        // If field type is not `selectable` remove all related field values.
        if ($this->fieldValues && !in_array($this->type, [self::FIELD_TYPE_LIST, self::FIELD_TYPE_SELECT])) {
            FieldValue::deleteAll(['uuid' => ArrayHelper::getColumn($this->fieldValues, 'uuid')]);
        }

        // If field code was changed update all form results
        if (array_key_exists('code', $changedAttributes)) {
            foreach ($this->form->results as $result) {
                $data = Json::decode($result->data);
                if (isset($data[$changedAttributes['code']])) {
                    $data[$this->code] = $data[$changedAttributes['code']];
                    unset($data[$changedAttributes['code']]);
                }

                $result->updateAttributes(['data' => Json::encode($data)]);
            }
        }
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        $types = [
            self::FIELD_TYPE_STRING => 'String',
            self::FIELD_TYPE_TEXT => 'Text',
            self::FIELD_TYPE_SELECT => 'Select',
            self::FIELD_TYPE_LIST => 'List',
            self::FIELD_TYPE_FILE => 'File',
        ];

        return array_map('self::t', $types);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getForm()
    {
        return $this->hasOne(Form::className(), ['uuid' => 'form_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFieldValidators()
    {
        return $this->hasMany(FieldValidator::className(), ['field_uuid' => 'uuid'])->orderBy(['sort' => SORT_ASC]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFieldValues()
    {
        return $this->hasMany(FieldValue::className(), ['field_uuid' => 'uuid'])->orderBy(['sort' => SORT_ASC]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkflow()
    {
        return $this->hasOne(Workflow::className(), ['uuid' => 'workflow_uuid']);
    }

    /**
     * @return bool
     */
    public function isMultiple(): bool
    {
        return $this->multiple === 1;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active === 1;
    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return in_array(FieldValidator::TYPE_REQUIRED, ArrayHelper::getColumn($this->fieldValidators, 'type'));
    }

    /**
     * @param int $length
     * @return string
     */
    protected static function generateCodeAppendix($length = 6)
    {
        $alphabet = str_split('abcdefghijklmnopqrstuvwxyz');
        $appendix = '';
        for ($i = 0; $i < $length + 1; $i++) {
            $appendix .= $alphabet[random_int(0, count($alphabet) - 1)];
        }

        return $appendix;
    }

    /**
     * @return Field|bool
     */
    public function duplicate()
    {
        $clone = new self([
            'form_uuid' => $this->form_uuid,
            'sort' => 100
        ]);

        foreach ($this->attributes as $attribute => $value) {
            if ($this->isAttributeSafe($attribute)) {
                $clone->$attribute = $value;
            }
        }

        $appendixLength = 7;
        if (mb_strlen($clone->code) > (50 - $appendixLength)) {
            $clone->code = mb_substr($clone->code, 0, (50 - $appendixLength));
        }

        $clone->code .= '_' . self::generateCodeAppendix($appendixLength - 1);
        $clone->type = self::FIELD_TYPE_DEFAULT;

        if ($clone->save()) {

            $clone->updateAttributes([
                'type' => $this->type,
                'multiple' => $this->multiple
            ]);

            foreach (['fieldValues', 'fieldValidators'] as $relation) {
                /* @var FieldRelation $values */
                $values = $this->$relation;
                foreach ($values as $value) {
                    $value->field_uuid = $clone->uuid;
                    $value->duplicate()->save();
                }
            }

            return $clone;
        }

        return false;
    }
}