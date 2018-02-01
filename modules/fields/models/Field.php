<?php

namespace fields\models;

use app\components\behaviors\PrimaryKeyBehavior;
use app\components\behaviors\WorkflowBehavior;
use app\models\Workflow;
use fields\validators\JsonValidator;
use yii\behaviors\SluggableBehavior;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\validators\UniqueValidator;

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
 * @property string $workflow_uuid
 *
 * @property FieldValidator[] $fieldValidators
 * @property FieldValue[] $fieldValues
 * @property Workflow $workflow
 *
 * @package fields\models
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
        return '{{%' . \Yii::$app->controller->module->module->id . '_fields}}';
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
     * @param array $params
     * @return ActiveDataProvider
     */
    public static function search($params = [])
    {
        return new ActiveDataProvider([
            'query' => self::prepareSearchQuery($params),
            'sort' => [
                'defaultOrder' => ['workflow.modified_date' => SORT_DESC],
                'attributes' => self::getSortAttributes()
            ]
        ]);
    }

    /**
     * @param array $params
     * @return \yii\db\ActiveQuery
     */
    protected static function prepareSearchQuery($params)
    {
        $query = self::find()->joinWith('workflow');

        if ($params) {
            $query->andFilterWhere($params);
        }

        return $query;
    }

    /**
     * @return array
     */
    protected static function getSortAttributes()
    {
        $attributes = (new static())->attributes();
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
            'validators' => 'Validators',
            'values' => 'Values',
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
                'tooSmall' => self::t('Value must be greater or equal than {min, number}.'),
                'message' => self::t('Value must be a integer.')
            ],
            ['description', 'safe'],
            // Code validation rules
            ['code', 'string', 'max' => 50, 'tooLong' => self::t('Maximum {max, number} characters allowed.')],
            ['code', 'validateCode'],
            ['code', 'match', 'pattern' => '/^[a-z_\-\d]*$/i', 'message' => self::t('Invalid code.')],
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
        if (!$this->hasValues() && $this->multiple) {
            $this->addError($attribute, self::t('{type} can not be assigned to `multiple` fields.'));
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
        $behaviors['wf'] = WorkflowBehavior::className();
        $behaviors['pk'] = PrimaryKeyBehavior::className();
        $behaviors['sg'] = [
            'class' => SluggableBehavior::className(),
            'attribute' => 'label',
            'slugAttribute' => 'code',
            'ensureUnique' => true,
            'uniqueValidator' => ['class' => UniqueValidator::className()],
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
            $values = $this->fieldValues;
            /* @var ActiveRecord $className */
            $className = get_class(array_shift($values));
            $className::deleteAll(['uuid' => ArrayHelper::getColumn($this->fieldValues, 'uuid')]);
        }
    }

    /**
     * @return array
     */
    public static function getTypes()
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
//
//    /**
//     * @param int $length
//     * @return string
//     */
//    protected static function generateCodeAppendix($length = 6)
//    {
//        $alphabet = str_split('abcdefghijklmnopqrstuvwxyz');
//        $appendix = '';
//        for ($i = 0; $i < $length + 1; $i++) {
//            $appendix .= $alphabet[random_int(0, count($alphabet) - 1)];
//        }
//
//        return $appendix;
//    }

    /**
     * @return Field|bool
     */
    public function duplicate()
    {
        $clone = new static();

        foreach ($this->attributes as $attribute => $value) {
            if ($this->isAttributeSafe($attribute)) {
                $clone->$attribute = $value;
            }
        }

        $clone->code = null;

        return $clone;
    }
}