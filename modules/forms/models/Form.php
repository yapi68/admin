<?php

namespace forms\models;

use app\components\behaviors\PrimaryKeyBehavior;
use app\components\behaviors\WorkflowBehavior;
use app\models\Workflow;
use forms\modules\fields\models\Field;
use yii\behaviors\SluggableBehavior;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * Class Form
 * @property string $uuid
 * @property string $code
 * @property string $title
 * @property string $description
 * @property string $template
 * @property boolean $template_active
 * @property boolean $active
 * @property string $active_from_date
 * @property string $active_to_date
 * @property integer $sort
 * @property string $workflow_uuid
 *
 * @property Workflow $workflow
 * @property FormResult[] $results
 * @property FormStatus[] $statuses
 * @property Field[] $fields
 *
 * @package forms\models
 */
class Form extends ActiveRecord
{
    /**
     * @var array
     */
    public $active_dates = [];
    /**
     * @var array
     */
    private $_delete = [];
    /**
     * @var \mail\models\Type
     */
    private $_event;

    /**
     * @return string
     */
    public static function tableName()
    {
        return '{{%forms}}';
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'active' => 'In use',
            'active_from_date' => 'Enable from',
            'active_to_date' => 'Enable to',
            'title' => 'Title',
            'description' => 'Description',
            'code' => 'Code',
            'template' => 'Template code',
            'template_active' => 'Use form template',
            'sort' => 'Sort',
            'workflow.modified_date' => 'Modified',
            'results' => 'Results',
            'event' => 'Mail event type',
            'mail_template_uuid' => 'Mail template',
        ];

        return array_map('self::t', $labels);
    }

    /**
     * @return array
     */
    public function attributeHints()
    {
        $hints = [
            'active' => 'Specifies whether users can fill out the form. The parameter value is more important than the activity dates.',
            'active_from_date' => 'Specifies the date after that users can fill out the form. If no value are set the form is available for filling immediate after publishing.',
            'active_to_date' => 'Specifies the date after that the form will became blocked. If no value are set the form never wont became blocked by dates.',
            'title' => 'Up to 250 characters length.',
            'description' => 'Describe a purpose of the form.',
            'code' => 'Valid characters is latin letters, numbers and underscore. Will be autogenerated if no value provided.',
            'template' => 'Provide valid HTML layout. Field codes must be enclosed in double curly braces.',
            'template_active' => 'Use form template instead of default one.',
            'sort' => 'The numerical value that determines the position of the form in various lists. Default value is `100`.'
        ];

        return array_map('self::t', $hints);
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            // Title validation rules
            ['title', 'required', 'message' => self::t('{attribute} is required.')],
            ['title', 'string', 'max' => 255, 'tooLong' => self::t('Maximum {max, number} characters allowed.')],
            // Code validation rules
            ['code', 'required', 'message' => self::t('{attribute} is required.')],
            ['code', 'string', 'max' => 50, 'tooLong' => self::t('Maximum {max, number} characters allowed.')],
            ['code', 'match', 'pattern' => '/^[a-z_\-\d]*$/i'],
            ['code', 'unique', 'message' => self::t('Web-form with code `{value}` is already exists.')],
            // Boolean rules
            [['active', 'template_active'], 'boolean'],
            // Text fields
            [['description', 'template'], 'safe'],
            ['template', 'validateTemplate'],
            // Sort field
            [
                'sort',
                'integer',
                'min' => 0,
                'tooSmall' => self::t('Value must be greater than 0.'),
                'message' => self::t('Value must be a integer.')
            ],
            // Date fields
            ['active_dates', 'each', 'rule' => [
                'date',
                'format' => \Yii::$app->formatter->datetimeFormat,
                'timestampAttribute' => 'active_dates',
                'message' => self::t('Invalid date format.')
            ]],
            ['active_dates', 'validateDateRange'],
        ];

        $className = '\mail\models\Type';
        if (class_exists($className)) {
            $rules[] = [
                'event',
                'exist',
                'targetClass' => $className,
                'targetAttribute' => 'uuid'
            ];
            $rules[] = [
                'mail_template_uuid',
                'exist',
                'targetClass' => '\mail\models\Template',
                'targetAttribute' => 'uuid'
            ];
        }

        return $rules;
    }

    /**
     * @param string $attribute
     */
    public function validateDateRange($attribute)
    {
        $value = $this->$attribute;
        if (!empty($value['active_to_date'])
            && ($value['active_from_date'] > $value['active_to_date'])
        ) {
            $this->addError($attribute . '[active_to_date]', self::t('This date must be greater than first one.'));
        }
    }

    /**
     * Check fields` presence by their code in the form template.
     * @param string $attribute
     */
    public function validateTemplate($attribute)
    {
        $value = $this->$attribute;
        $diff = [];

        // Search for field codes
        if (preg_match_all('/{{([\w]+)}}/', $value, $matches)) {
            // List all form fields
            $fields = ArrayHelper::getColumn($this->fields, 'code');

            // Service fields
            $fields[] = Field::FORM_FIELD_AUTH;
            $fields[] = Field::FORM_FIELD_CAPTCHA;

            $diff = array_diff($matches[1], $fields);
        }

        if (count($diff) > 0) {
            $this->addError($attribute, self::t('Template contains wrong field codes.'));
        }
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
            if (is_array($this->active_dates)) {
                $this->parseActiveDates();
            }

            if ($this->hasAttribute('mail_template_uuid')) {
                $this->{'mail_template_uuid'} = $this->{'mail_template_uuid'} && $this->_event ? $this->{'mail_template_uuid'} : null;
            }

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

        if ($insert) {
            if (class_exists('\forms\models\FormEvent')) {
                $this->insertEvent();
            }
        }
        else {
            /* @var ActiveRecord $className */
            $className = '\forms\models\FormEvent';
            if (class_exists($className)) {
                $className::deleteAll(['form_uuid' => $this->uuid]);
                if ($this->_event) {
                    (new $className([
                        'form_uuid' => $this->uuid,
                        'type_uuid' => $this->_event
                    ]))->{'insert'}();
                }
            }
        }
    }

    /**
     * Inserts a new mail type for current form.
     */
    protected function insertEvent()
    {
        $className = '\mail\models\Type';
        if (class_exists($className)) {
            /* @var \mail\models\Type $type */
            $type = new $className([
                'code' => 'MAIL_TYPE_' . $this->code,
                'title' => sprintf('Web form `%s` mail event', $this->code),
            ]);

            if ($type->save()) {
                $className = '\forms\models\FormEvent';
                (new $className([
                    'form_uuid' => $this->uuid,
                    'type_uuid' => $type->uuid
                ]))->{'insert'}();
            }

            $this->_event = $type->uuid;
        }
    }

    /**
     * Using `FROM_UNIXTIME()` MySQL function to sets date attributes.
     */
    protected function parseActiveDates()
    {
        foreach ($this->active_dates as $name => $date) {
            if (is_int($date)) {
                $expression = new Expression("FROM_UNIXTIME(:$name)", [":$name" => $date]);
                $this->setAttribute($name, $expression);
            }
            else {
                $this->setAttribute($name, null);
            }
        }
    }

    /**
     * @param $message
     * @param array $params
     * @return string
     */
    public static function t($message, $params = [])
    {
        return \Yii::t('forms', $message, $params);
    }

    /**
     * @return array
     */
    public function transactions()
    {
        return [self::SCENARIO_DEFAULT => self::OP_ALL];
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors[] = PrimaryKeyBehavior::className();
        $behaviors[] = WorkflowBehavior::className();
        $behaviors[] = [
            'class' => SluggableBehavior::className(),
            'attribute' => 'title',
            'slugAttribute' => 'code',
            'ensureUnique' => true,
            'immutable' => true
        ];

        return $behaviors;
    }

    /**
     * @param FormSettings $settings
     * @return ActiveDataProvider
     */
    public static function search(FormSettings $settings = null)
    {
        $defaultOrder = ['storage.title' => SORT_ASC];
        if ($settings) {
            $defaultOrder = [$settings->sortBy => $settings->sortOrder];
        }

        return new ActiveDataProvider([
            'query' => self::prepareSearchQuery(),
            'sort' => [
                'defaultOrder' => $defaultOrder,
                'attributes' => self::getSortAttributes()
            ]
        ]);
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
     * @param bool $deepCopy
     * @return Form|false
     */
    public function duplicate($deepCopy = false)
    {
        $copy = new self();

        foreach ($this->attributes as $name => $value) {
            if ($copy->isAttributeSafe($name)) {
                $copy->$name = $value;
            }
        }

        $copy->active_from_date = $this->active_from_date;
        $copy->active_to_date = $this->active_to_date;

        $appendixLength = 7;
        if (mb_strlen($copy->code) > (50 - $appendixLength)) {
            $copy->code = mb_substr($copy->code, 0, (50 - $appendixLength));
        }

        $copy->code .= '_' . self::generateCodeAppendix($appendixLength - 1);

        if ($result = $copy->save(false) ) {

            if ($deepCopy) {
                foreach ($this->statuses as $status) {
                    $status->form_uuid = $copy->uuid;
                    $status->duplicate()->save();
                }

                foreach ($this->fields as $field) {
                    $field->form_uuid = $copy->uuid;
                    $field->duplicate();
                }
            }

            return $copy;
        }

        return false;
    }

    /**
     * @return null|ActiveRecord
     */
    public function getDefaultStatus()
    {
        return $this->getStatuses()->andWhere(['default' => true])->one();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFields()
    {
        return $this->hasMany(Field::className(), ['form_uuid' => 'uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getResults()
    {
        return $this->hasMany(FormResult::className(), ['form_uuid' => 'uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatuses()
    {
        return $this->hasMany(FormStatus::className(), ['form_uuid' => 'uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventRelation()
    {
        $className = '\mail\models\Type';
        return $this->hasOne($className, ['uuid' => 'type_uuid'])
            ->viaTable('{{%forms_events}}', ['form_uuid' => 'uuid']);
    }

    /**
     * @return \mail\models\Type
     */
    public function getEvent()
    {
        if ($this->_event === null) {
            $className = '\mail\models\Type';
            if (class_exists($className)) {
                $this->_event = $this->getEventRelation()->one();
            }
        }

        return $this->_event;
    }

    /**
     * @param string $type
     */
    public function setEvent($type)
    {
        $this->_event = $type;
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
    public function isActive()
    {
        $isActive = $this->active === 1;

        if ($this->active_from_date) {
            $isActive = $isActive && (new \DateTime($this->active_from_date))->getTimestamp() < time();
        }

        if ($this->active_to_date) {
            $isActive = $isActive && (new \DateTime($this->active_to_date))->getTimestamp() > time();
        }

        return $isActive;
    }

    /**
     * @param array $dates
     * @param null|string $format
     */
    public function formatDatesArray(array $dates, $format = null)
    {
        foreach ($dates as $attribute) {
            if ($this->$attribute) {
                $this->$attribute = \Yii::$app->formatter->asDatetime($this->$attribute, $format);
            }
        }
    }

    /**
     * @return array
     */
    public static function getSortAttributes()
    {
        $attributes = (new self())->attributes();
        $attributes['workflow.modified_date'] = [
            'asc' => ['{{%workflow}}.[[modified_date]]' => SORT_ASC],
            'desc' => ['{{%workflow}}.[[modified_date]]' => SORT_DESC],
        ];

        return $attributes;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    protected static function prepareSearchQuery()
    {
        /* @var \yii\db\ActiveQuery $query */
        $query = self::find()->joinWith('workflow');

        return $query;
    }

    /**
     * @return bool
     */
    public function beforeDelete()
    {
        $isValid = parent::beforeDelete();

        if ($isValid) {
            // Collect relations` workflow to future delete
            $this->_delete = ArrayHelper::merge(
                ArrayHelper::getColumn($this->statuses, 'workflow_uuid'),
                ArrayHelper::getColumn($this->fields, 'workflow_uuid'),
                ArrayHelper::getColumn($this->results, 'workflow_uuid')
            );
        }

        return $isValid;
    }

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        parent::afterDelete();

        if ($this->_delete) {
            // Delete collected relation`s workflow
            Workflow::deleteAll(['uuid' => array_filter($this->_delete, 'strlen')]);
        }
    }
}