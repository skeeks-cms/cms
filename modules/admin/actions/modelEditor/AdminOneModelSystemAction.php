<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.05.2015
 */
namespace skeeks\cms\modules\admin\actions\modelEditor;

use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\behaviors\HasFiles;
use skeeks\cms\models\behaviors\HasRelatedProperties;
use skeeks\cms\models\behaviors\TimestampPublishedBehavior;
use skeeks\cms\models\Search;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\filters\AdminAccessControl;
use skeeks\cms\modules\admin\widgets\ControllerActions;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\validators\HasBehavior;
use skeeks\cms\validators\HasBehaviorsOr;
use skeeks\sx\validate\Validate;
use yii\authclient\AuthAction;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\helpers\Inflector;
use yii\web\Application;
use yii\web\ViewAction;
use \skeeks\cms\modules\admin\controllers\AdminController;

/**
 * Class AdminOneModelSystemAction
 * @package skeeks\cms\modules\admin\actions\modelEditor
 */
class AdminOneModelSystemAction extends AdminOneModelUpdateAction
{
    public $modelValidate = false;


    public function init()
    {
        parent::init();

        //Для работы с любой моделью нужно как минимум иметь привилегию CmsManager::PERMISSION_ALLOW_MODEL_UPDATE
        $this->controller->attachBehavior('accessAdvanced',
        [
            'class'         => AdminAccessControl::className(),
            'only'          => [$this->id],
            'rules'         =>
            [
                [
                    'allow'         => true,
                    'matchCallback' => [$this, 'checkAdvancedAccess']
                ],
            ],
        ]);
    }

    /**
     * Renders a view
     *
     * @param string $viewName view name
     * @return string result of the rendering
     */
    protected function render($viewName)
    {
        $this->viewParams =
        [
            'model' => $this->controller->model
        ];

        return $this->controller->render("@skeeks/cms/modules/admin/views/base-actions/system", (array) $this->viewParams);
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        if (!parent::isVisible())
        {
            return false;
        }

        if (!Validate::validate(new HasBehaviorsOr([
            TimestampBehavior::className(),
            TimestampPublishedBehavior::className(),
            BlameableBehavior::className()
        ]), $this->controller->model)->isValid())
        {
            return false;
        }

        return $this->checkAdvancedAccess();
    }


    public function checkAdvancedAccess()
    {
        if (Validate::validate(new HasBehavior(BlameableBehavior::className()), $this->controller->model)->isValid())
        {
            //Если такая привилегия заведена, нужно ее проверять.
            if ($permission = \Yii::$app->authManager->getPermission(CmsManager::PERMISSION_ALLOW_MODEL_UPDATE_ADVANCED))
            {
                if (!\Yii::$app->user->can($permission->name, [
                    'model' => $this->controller->model
                ]))
                {
                    return false;
                }
            }
        } else
        {
            //Если такая привилегия заведена, нужно ее проверять.
            if ($permission = \Yii::$app->authManager->getPermission(CmsManager::PERMISSION_ALLOW_MODEL_UPDATE_ADVANCED))
            {
                if (!\Yii::$app->user->can($permission->name))
                {
                    return false;
                }
            }
        }

        return true;
    }

}