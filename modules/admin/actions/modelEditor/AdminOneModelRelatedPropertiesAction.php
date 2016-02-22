<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.05.2015
 */
namespace skeeks\cms\modules\admin\actions\modelEditor;

use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\behaviors\HasRelatedProperties;
use skeeks\cms\models\Search;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\widgets\ControllerActions;
use skeeks\cms\validators\HasBehavior;
use skeeks\sx\validate\Validate;
use yii\authclient\AuthAction;
use yii\helpers\Inflector;
use yii\web\Application;
use yii\web\ViewAction;
use \skeeks\cms\modules\admin\controllers\AdminController;

/**
 * Class AdminOneModelRelatedPropertiesAction
 * @package skeeks\cms\modules\admin\actions\modelEditor
 */
class AdminOneModelRelatedPropertiesAction extends AdminOneModelEditAction
{

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

        return $this->controller->render("@admin/views/base-actions/related-properties", (array) $this->viewParams);
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

        return Validate::validate(new HasBehavior(HasRelatedProperties::className()), $this->controller->model)->isValid();
    }
}