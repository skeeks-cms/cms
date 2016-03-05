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
use skeeks\sx\validate\Validate;
use yii\authclient\AuthAction;
use yii\base\View;
use yii\behaviors\BlameableBehavior;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\helpers\Json;
use yii\web\Application;
use yii\web\ViewAction;
use \skeeks\cms\modules\admin\controllers\AdminController;

/**
 * Class AdminMultiDialogModelEditAction
 * @package skeeks\cms\modules\admin\actions\modelEditor
 */
class AdminMultiDialogModelEditAction extends AdminMultiModelEditAction
{
    public $viewDialog      = "";
    public $dialogOptions   = [
        'style' => 'min-height: 500px; min-width: 600px;'
    ];

    /**
     * @param GridView $grid
     * @return string
     */
    public function registerForGrid(GridViewStandart $grid)
    {
        $dialogId = $this->getGridActionId($grid);

        $clientOptions = Json::encode(ArrayHelper::merge($this->getClientOptions(), [
            'dialogId' => $dialogId
        ]));

        $grid->view->registerJs(<<<JS
(function(sx, $, _)
{

    sx.createNamespace('sx.classes.grid', sx);

    sx.classes.grid.MultiDialogAction = sx.classes.grid.MultiAction.extend({

        _onDomReady: function()
        {
            var self = this;

            this.jDialog = $( '#' + this.get('dialogId') );
            $('form', this.jDialog).on('submit', function()
            {
                var data = _.extend(self.Grid.getDataForRequest(), {
                    'formData' : $(this).serialize()
                });

                var ajax = self.createAjaxQuery(data);
                ajax.onComplete(function()
                {
                    $.fancybox.close();
                });
                ajax.execute();
                return false;
            });

        },

        _go: function()
        {
            var self = this;

            var link = $("<a>", {
                'href' : '#' + this.get('dialogId'),
            }).hide().text('auto').appendTo('body').fancybox();

            link.click();

            //Надо делать ajax запрос
            if (this.get("request") == 'ajax')
            {
                //return this.executeAjax(self.Grid.getDataForRequest());
            }
        },

    });

    new sx.classes.grid.MultiDialogAction({$grid->gridJsObject}, '{$this->id}' ,{$clientOptions});
})(sx, sx.$, sx._);
JS
);
        $content = '';
        if ($this->viewDialog)
        {
            $content = $this->controller->view->render($this->viewDialog, [
                'action' => $this->id,
            ]);
        }

        return Html::tag('div', $content, ArrayHelper::merge($this->dialogOptions, [
            'id' => $dialogId
        ]));

    }


    /**
     * @param GridViewStandart $grid
     * @return string
     */
    public function getGridActionId(GridViewStandart $grid)
    {
        return $grid->id . "-" . $this->id;
    }



}