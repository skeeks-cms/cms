<?php
/**
 * ControllerActions
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\modules\admin\widgets;

use skeeks\cms\App;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\helpers\Action;
use skeeks\cms\modules\admin\controllers\helpers\ActionManager;
use skeeks\cms\modules\admin\controllers\helpers\ActionModel;
use skeeks\cms\modules\admin\widgets\tree\Asset;
use skeeks\cms\validators\db\IsSame;
use skeeks\sx\validate\Validate;
use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/**
 * Class ControllerActions
 * @package skeeks\cms\modules\admin\widgets
 */
class Tree
    extends Widget
{
    /**
     * @var array
     */
    public $containerOptions =
    [
        "class" => "sx-tree"
    ];

    /**
     * @var array ноды для которых строить дерево.
     */
    public $models      = [];
    /**
     * @var string
     */
    public $activeRequestName      = "a";
    public $openedRequestName      = "o";

    public function init()
    {
        parent::init();
        $this->_ensure();

    }

    /**
     * Парочка проверок, для целостности
     * @throws InvalidConfigException
     */
    protected function _ensure()
    {}


    protected $_activeTmp = [];
    protected $_openedTmp = [];
    protected $_countTmp = 0;
    /**
     * TODO: учитывать приоритет
     * @return string
     */
    public function run()
    {
        $openedModels = [];

        if (\Yii::$app->request->getQueryParam('setting-open-all'))
        {
            \skeeks\cms\models\Tree::find()->where([]);

            return \Yii::$app->response->redirect(UrlHelper::construct("cms/admin-tree/index"));
        }


        if ($opened = \Yii::$app->request->getQueryParam($this->openedRequestName))
        {
            $openedModels = \skeeks\cms\models\Tree::find()->where(["id" => $opened])->all();
        }

        $this->_openedTmp = $openedModels;

        $this->registerAssets();
        return Html::tag('div',

            Html::tag("div",
                Html::tag("div", $this->renderNodes($this->models), $this->containerOptions)
            , ['class' => "sx-container-tree col-md-6"]) .

            Html::tag("div",
                Html::a("Открыть все разделы", UrlHelper::construct("cms/admin-tree/index")->set('setting-open-all', 'true'), ['class' => 'btn btn-primary btn-sm']) .
                Html::a("Закрыть все разделы", UrlHelper::construct("cms/admin-tree/index"), ['class' => 'btn btn-primary btn-sm'])
            , ['class' => "sx-container-controlls col-md-2"])

            ,['class' => 'row-fluid']
        );
    }


    public function renderNodes($models)
    {
        $options["item"] = function($model)
        {
            $isOpen = false;
            $isActive = false;

            $controller = App::moduleCms()->createControllerByID("admin-tree");
            $controller->setModel($model);

            $child = "";
            foreach ($this->_openedTmp as $activeNode)
            {
                if (Validate::validate(new IsSame($activeNode), $model)->isValid())
                {
                    $isOpen = true;
                    break;
                }
            }

            if ($isOpen && $model->hasChildrens())
            {
                $child = $this->renderNodes($model->findChildrens()->all());
            }




            $openCloseLink = "";
            $currentLink = "";
            if ($model->hasChildrens())
            {
                ;
                $openedIds = (array) \Yii::$app->request->getQueryParam($this->openedRequestName);

                if ($isOpen)
                {
                    $newOptionsOpen = [];
                    foreach ($openedIds as $id)
                    {
                        if ($id != $model->id)
                        {
                            $newOptionsOpen[] = $id;
                        }
                    }

                    $urlOptionsOpen = array_unique($newOptionsOpen);
                    $params = \Yii::$app->request->getQueryParams();
                    $params[$this->openedRequestName] = $urlOptionsOpen;

                    $currentLink = UrlHelper::construct("cms/admin-tree/index")->setData($params);
                    $openCloseLink = Html::a(
                        Html::tag("span", "" ,["class" => "glyphicon glyphicon-minus", "title" => "Свернуть"]),
                        $currentLink,
                        ['class' => 'btn btn-xs btn-default']
                    );
                } else
                {
                    $urlOptionsOpen = array_unique(array_merge($openedIds, [$model->id]));
                    $params = \Yii::$app->request->getQueryParams();
                    $params[$this->openedRequestName] = $urlOptionsOpen;
                    $currentLink = UrlHelper::construct("cms/admin-tree/index")->setData($params);
                    $openCloseLink = Html::a(
                        Html::tag("span", "" ,["class" => "glyphicon glyphicon-plus", "title" => "Развернуть"]),
                        $currentLink,
                        ['class' => 'btn btn-xs btn-default']
                    );
                }

                $openCloseLink = Html::tag("div", $openCloseLink, ["class" => "sx-node-open-close"]);
            }




            return Html::tag("li",
                        Html::tag("div",
                            $openCloseLink .
                            Html::tag("div",
                                Html::a($model->name, $currentLink),
                                [
                                    "class" => "sx-label-node"
                                ]
                            ) .
                            Html::tag("div",
                                DropdownControllerActions::begin([
                                    "controller"    => $controller,
                                ])->run(),
                                [
                                    "class" => "sx-controll-node"
                                ]
                            )
                        , ["class" => "row"])
                        . $child ,
                        [
                            "class" => "sx-tree-node " . ($isActive ? " active" : "") . ($isOpen ? " open" : "")
                        ]
            );
        };

        $ul = Html::ul($models, $options);

        return $ul;
    }


    public function registerAssets()
    {
        Asset::register($this->getView());
        $this->getView()->registerCss(<<<CSS

.sx-tree
{
    margin-left: 15px;
}
.sx-tree ul
{
    padding-left: 0px;
}

    .sx-tree ul li.sx-tree-node
    {
        list-style-type: none;
        padding-left: 15px;
        margin: 2px 0px;
    }

    .sx-tree ul li.sx-tree-node.open
    {}

    .sx-tree ul li.sx-tree-node.active
    {}

        .sx-tree ul li.sx-tree-node .row
        {
            margin: 0 !important;
        }



    .sx-tree ul li.sx-tree-node .sx-node-open-close
    {
        float: left;
        width: 23px;
        margin-left: -23px;
    }

        .sx-tree ul li.sx-tree-node .sx-node-open-close > a
        {
            font-size: 6px;
            color: #000000;
            background: white;
            padding: 2px 4px;
        }


    .sx-tree ul li.sx-tree-node .sx-controll-node
    {
        width: 50px;
        float: left;
        margin-left: 10px;
    }

        .sx-tree ul li.sx-tree-node .sx-controll-node > .dropdown button
        {
            font-size: 6px;
            color: #000000;
            background: white;
            padding: 2px 4px;
        }



    .sx-tree ul li.sx-tree-node .sx-label-node
    {
        float: left;
        padding-left: 23px;
    }

        .sx-tree ul li.sx-tree-node .sx-label-node > a
        {
            font-size: 12px;
            font-weight: bold;
            color: #000000;
        }
CSS
);
    }
}