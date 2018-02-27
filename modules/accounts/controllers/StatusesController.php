<?php

namespace accounts\controllers;

use accounts\models\Account;
use accounts\models\AccountStatus;
use app\components\behaviors\ConfirmFilter;
use app\models\User;
use yii\filters\AjaxFilter;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\HttpException;
use yii\web\Response;
use yii\widgets\ActiveForm;

/**
 * Class StatusesController
 *
 * @package accounts\controllers
 */
class StatusesController extends Controller
{
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
            'class' => VerbFilter::class,
            'actions' => [
                'delete' => ['delete'],
            ]
        ];
        $behaviors['confirm'] = [
            'class' => ConfirmFilter::class,
            'actions' => ['delete']
        ];
        $behaviors['ajax'] = [
            'class' => AjaxFilter::class,
            'except' => ['index']
        ];

        return $behaviors;
    }

    /**
     * @param string $account_uuid
     * @return string
     * @throws HttpException
     */
    public function actionIndex($account_uuid)
    {
        $account = Account::findOne($account_uuid);

        if (!$account) {
            throw new HttpException(404, 'Account not found.');
        }

        $params = [
            'dataProvider' => AccountStatus::search($account_uuid),
            'account' => $account
        ];

        if (\Yii::$app->request->isAjax) {
            return $this->renderPartial('index', $params);
        }

        return $this->render('index', $params);
    }

    /**
     * @param string $account_uuid
     * @return array|string
     * @throws HttpException
     */
    public function actionCreate($account_uuid)
    {
        /* @var Account $account */
        $account = Account::findOne($account_uuid);

        if (!$account) {
            throw new HttpException(404, 'Account not found.');
        }

        $model = new AccountStatus([
            'account_uuid' => $account_uuid,
            'dates' => ['issue_date' => \Yii::$app->formatter->asDatetime(date('Y-m-d H:i:s'))],
        ]);

        if ($model->load(\Yii::$app->request->post())) {
            return $this->postCreate($model);
        }

        // Format dates into human readable format
        $model->formatDatesArray(['issue_date', 'expire_date']);

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
        /* @var AccountStatus $model */
        $model = AccountStatus::findOne($uuid);

        if (!$model) {
            throw new HttpException(404, 'Account status not found.');
        }

        if ($model->load(\Yii::$app->request->post())) {
            return $this->postCreate($model);
        }

        // Format dates into human readable format
        $model->formatDatesArray(['issue_date', 'expire_date']);

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
        /* @var AccountStatus $model */
        $model = AccountStatus::findOne($uuid);

        if (!$model) {
            throw new HttpException(404, 'Account status not found.');
        }

        // Makes a status copy
        $copy = $model->duplicate();

        if ($copy->load(\Yii::$app->request->post())) {
            return $this->postCreate($copy);
        }

        // Format dates into human readable format
        $copy->formatDatesArray(['issue_date', 'expire_date']);

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
        $models = AccountStatus::findAll($selected);
        $counter = 0;

        foreach ($models as $model) {
            $counter += (int) $model->delete();
        }

        return $counter === count($models);
    }

    /**
     * @param AccountStatus $model
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
