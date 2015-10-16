<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.05.2015
 */
namespace skeeks\cms\modules\admin\actions\modelEditor;

use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\Search;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\filters\AdminAccessControl;
use skeeks\cms\modules\admin\widgets\ControllerActions;
use skeeks\cms\modules\admin\widgets\GridViewStandart;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\validators\HasBehavior;
use skeeks\sx\validate\Validate;
use yii\authclient\AuthAction;
use yii\base\View;
use yii\behaviors\BlameableBehavior;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\helpers\Json;
use yii\web\Application;
use yii\web\ViewAction;
use \skeeks\cms\modules\admin\controllers\AdminController;

/**
 * @property AdminModelEditorController    $controller
 *
 * Class AdminModelsGridAction
 * @package skeeks\cms\modules\admin\actions
 */
class AdminMultiModelEditAction extends AdminModelEditorAction
{
    public function init()
    {
        parent::init();

        $this->method   = 'post';
        $this->request  = 'ajax';
    }

    /**
     * @var array
     */
    public $models = [];

    /**
     * Обработчик каждогой модели
     * @var callable
     */
    public $eachCallback = null;


    public function run()
    {
        $rr = new RequestResponse();

        $pk             = \Yii::$app->request->post($this->controller->requestPkParamName);
        $modelClass     = $this->controller->modelClassName;

        $this->models   = $modelClass::find()->where([
            $this->controller->modelPkAttribute => $pk
        ])->all();

        if (!$this->models)
        {
            $rr->success = false;
            $rr->message = \Yii::t('app',"No records found");
            return (array) $rr;
        }

        $data = [];
        foreach ($this->models as $model)
        {
            $raw = [];
            if ($this->eachExecute($model))
            {
                $data['success'] = ArrayHelper::getValue($data, 'success', 0) + 1;
            } else
            {
                $data['errors'] = ArrayHelper::getValue($data, 'errors', 0) + 1;
            }
        }

        $rr->success    = true;
        $rr->message    = \Yii::t('app',"Mission complete");
        $rr->data       = $data;
        return (array) $rr;
    }

    /**
     * @param $model
     * @return bool
     */
    public function eachExecute($model)
    {
        if ($this->eachCallback && is_callable($this->eachCallback))
        {
            $callback = $this->eachCallback;
            return $callback($model, $action);
        }

        return true;
    }

    /**
     * @param GridView $grid
     * @return string
     */
    public function registerForGrid(GridViewStandart $grid)
    {

        $clientOptions = Json::encode($this->getClientOptions());

        $grid->view->registerJs(<<<JS
(function(sx, $, _)
{
    new sx.classes.grid.MultiAction({$grid->gridJsObject}, '{$this->id}' ,{$clientOptions});
})(sx, sx.$, sx._);
JS
);
        return "";
    }


    public function getClientOptions()
    {
        return [
            "id"                => $this->id,
            "url"               => (string) $this->url,
            "confirm"           => $this->confirm,
            "method"            => $this->method,
            "request"           => $this->request,
        ];
    }
}