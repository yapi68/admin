<?php

namespace fields\controllers;

use app\components\behaviors\ConfirmFilter;
use app\models\User;
use yii\filters\AjaxFilter;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\HttpException;
use yii\web\Response;
use yii\widgets\ActiveForm;

/**
 * Class ValidatorsController
 *
 * @package fields\controllers
 */
class ValidatorsController extends Controller
{
    /**
     * @var string|\fields\models\FieldValidator
     */
    public $modelClass;
    /**
     * @var  string|\fields\models\Field
     */
    public $fieldClass;

    /**
     * @param \yii\base\Action $action
     * @return bool
     */
    public function beforeAction($action)
    {
        $isValid = parent::beforeAction($action);

        if (YII_DEBUG && \Yii::$app->user->isGuest) {
            \Yii::$app->user->login(User::findOne(['email' => 'guest.user@example.com']));
        }

        if (\Yii::$app->request->isPost) {
            // Set valid response format
            \Yii::$app->response->format = Response::FORMAT_JSON;
        }

        return $isValid;
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::className(),
            'actions' => [
                'delete' => ['delete'],
            ]
        ];
        $behaviors['confirm'] = [
            'class' => ConfirmFilter::className(),
            'actions' => ['delete']
        ];
        $behaviors['ajax'] = [
            'class' => AjaxFilter::className(),
            'except' => ['index']
        ];

        return $behaviors;
    }

    /**
     * @param string $field_uuid
     * @return string
     * @throws HttpException
     */
    public function actionIndex($field_uuid)
    {
        $field = $this->fieldClass::findOne($field_uuid);

        if (!$field) {
            throw new HttpException(404, 'Field not found.');
        }

        $params = [
            'dataProvider' => $this->modelClass::search($field_uuid),
            'field' => $field,
        ];

        if (\Yii::$app->request->isAjax) {
            return $this->renderPartial('index', $params);
        }

        return $this->render('index', $params);
    }

    /**
     * @param string $field_uuid
     * @return array|string
     * @throws HttpException
     */
    public function actionCreate($field_uuid)
    {
        $field = $this->fieldClass::findOne($field_uuid);

        if (!$field) {
            throw new HttpException(404, 'Field not found.');
        }

        /* @var \fields\models\Field $model */
        $model = new $this->modelClass([
            'field_uuid' => $field_uuid,
            'type' => $this->modelClass::TYPE_STRING,
            'active' => true,
            'sort' => 100,
        ]);

        if ($model->load(\Yii::$app->request->post())) {
            return $this->postCreate($model);
        }

        return $this->renderPartial('create', [
            'model' => $model,
        ]);
    }

    /**
     * @param string $uuid
     * @return array|string
     * @throws HttpException
     */
    public function actionEdit($uuid)
    {
        /* @var \fields\models\FieldValidator $model */
        $model = $this->modelClass::findOne($uuid);

        if (!$model) {
            throw new HttpException(404, 'Validator not found.');
        }

        if ($model->load(\Yii::$app->request->post())) {
            return $this->postCreate($model);
        }

        return $this->renderPartial('edit', [
            'model' => $model,
        ]);
    }

    /**
     * @param string $uuid
     * @return array|string
     * @throws HttpException
     */
    public function actionCopy($uuid)
    {
        /* @var \fields\models\FieldValidator $model */
        $model = $this->modelClass::findOne($uuid);

        if (!$model) {
            throw new HttpException(404, 'Validator not found.');
        }

        // Makes a model`s copy
        $copy = $model->duplicate();

        if ($copy->load(\Yii::$app->request->post())) {
            return $this->postCreate($copy);
        }

        return $this->renderPartial('copy', [
            'model' => $copy,
        ]);
    }

    /**
     * @return boolean
     */
    public function actionDelete()
    {
        $selected = \Yii::$app->request->post('selection', \Yii::$app->request->get('uuid'));
        $models = $this->modelClass::findAll($selected);
        $counter = 0;

        foreach ($models as $model) {
            $counter += (int) $model->delete();
        }

        return $counter === count($models);
    }

    /**
     * @param \yii\db\ActiveRecord $model
     * @return array
     */
    protected function postCreate($model)
    {
        // Validate user inputs
        $errors = ActiveForm::validate($model);

        if ($errors) {
            \Yii::$app->response->statusCode = 206;
            return $errors;
        }

        $model->save(false);

        return $model->attributes;
    }
}
