<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.05.2015
 */
namespace skeeks\cms\modules\admin\actions\modelEditor;

use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\Search;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\widgets\ControllerActions;
use yii\authclient\AuthAction;
use yii\helpers\Inflector;
use yii\web\Application;
use yii\web\ViewAction;
use \skeeks\cms\modules\admin\controllers\AdminController;

/**
 *
 * Class AdminModelsGridAction
 * @package skeeks\cms\modules\admin\actions
 */
class ModelEditorGridAction extends AdminModelEditorAction
{
    /**
     * @var string
     */
    public $modelSearchClassName = '';

    /**
     * @return string
     */
    public function run()
    {
        $modelSeacrhClass = $this->modelSearchClassName;

        if (!$modelSeacrhClass)
        {
            $search         = new Search($this->controller->modelClassName);
            $dataProvider   = $search->search(\Yii::$app->request->queryParams);
            $searchModel    = $search->getLoadedModel();
        } else
        {
            $searchModel    = new $modelSeacrhClass();
            $dataProvider   = $searchModel->search(\Yii::$app->request->queryParams);
        }

        $this->viewParams =
        [
            'searchModel'   => $searchModel,
            'dataProvider'  => $dataProvider,
            'controller'    => $this->controller,
        ];

        return parent::run();
    }
}