<?php

namespace accounts\controllers;

use accounts\models\Account;
use accounts\models\AccountManager;
use app\components\actions\CopyAction;
use app\components\actions\CreateAction;
use app\components\actions\DeleteAction;
use app\components\actions\EditAction;
use app\components\actions\IndexAction;
use app\components\BaseController;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

/**
 * Class ManagersController
 *
 * @package accounts\controllers
 */
class ManagersController extends BaseController
{
    /**
     * @var string
     */
    public $modelClass = AccountManager::class;
    /**
     * @var Account
     */
    private $_account;

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        $isValid = parent::beforeAction($action);

        if ($isValid && in_array($action->id, ['index', 'create'])) {
            if (!($account_uuid = \Yii::$app->request->get('account_uuid'))) {
                throw new BadRequestHttpException();
            }

            if (!($this->_account = Account::findOne(['uuid' => $account_uuid]))) {
                throw new NotFoundHttpException('Account not found.');
            }
        }

        return $isValid;
    }

    /**
     * @return array
     */
    public function actions()
    {
        return [
            'index' => [
                'class' => IndexAction::class,
                'params' => [$this, 'getIndexParams']
            ],
            'create' => [
                'class' => CreateAction::class,
                'modelConfig' => [
                    'account_uuid' => \Yii::$app->request->get('account_uuid'),
                    'sort' => 100,
                ]
            ],
            'edit' => EditAction::class,
            'copy' => CopyAction::class,
            'delete' => DeleteAction::class,
        ];
    }

    /**
     * @return array
     */
    public function getIndexParams()
    {
        return [
            'dataProvider' => AccountManager::search([
                'account_uuid' => $this->_account->uuid
            ]),
            'account' => $this->_account
        ];
    }
}
