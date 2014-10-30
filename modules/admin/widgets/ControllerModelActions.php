<?php
/**
 * Фишка этого виджета в том что он делит действия, на общие действия контроллера и действия по управлению сущьностью
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\modules\admin\widgets;

use skeeks\cms\db\ActiveRecord;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Class ControllerActions
 * @package skeeks\cms\modules\admin\widgets
 */
class ControllerModelActions
    extends ControllerActions
{
    /**
     * @var AdminModelEditorController
     */
    public $controller = null;
    /**
     * @var ActiveRecord объет модели
     */
    public $model = null;
    /**
     * Парочка проверок, для целостности
     * @throws InvalidConfigException
     */
    protected function _ensure()
    {
        parent::_ensure();

        if (!$this->controller instanceof AdminModelEditorController)
        {
            throw new InvalidConfigException("Класс контроллера должен быть AdminEntityEditorController");
        }

        if (!$this->model)
        {
            throw new InvalidConfigException("Model не найдена");
        }

        if (!$this->model instanceof ActiveRecord)
        {
            throw new InvalidConfigException("Model должны быть наследована от: " . ActiveRecord::className());
        }
    }

    /**
     * @return string
     */
    public function run()
    {
        $actions = $this->controller->getModelActions();

        if (!$actions)
        {
            return "";
        }

        $result = $this->renderListLi();
        return Html::tag("ul", implode($result), $this->ulOptions);
    }

    /**
     * @return array
     */
    public function renderListLi()
    {
        $actions = $this->controller->getModelActions();
        $result = [];

        foreach ($actions as $code => $data)
        {
            $label = ArrayHelper::getValue($data, "label");

            $linkOptions["data-method"]         = ArrayHelper::getValue($data, "data-method");
            $linkOptions["data-confirm"]        = ArrayHelper::getValue($data, "data-confirm");

            $result[] = Html::tag("li",
                Html::a($label, [$code, "id" => $this->model->getPrimaryKey(), UrlRule::ADMIN_PARAM_NAME => UrlRule::ADMIN_PARAM_VALUE], $linkOptions),
                ["class" => $this->currentAction == $code ? "active" : ""]
            );
        }

        return $result;
    }
}