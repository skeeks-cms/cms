<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\BackendModelUpdateAction;
use skeeks\cms\backend\BackendAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\models\CmsCompanyEmail;
use skeeks\cms\models\CmsCompanyLink;
use skeeks\cms\models\CmsUserEmail;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\widgets\Select;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextField;
use yii\helpers\ArrayHelper;

/**
 * Class AdminUserEmailController
 * @package skeeks\cms\controllers
 */
class AdminCmsCompanyLinkController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = "Управление ссылками";
        $this->modelShowAttribute = "url";
        $this->modelClassName = CmsCompanyLink::class;

        $this->permissionName = 'cms/admin-company';
        $this->generateAccessActions = false;

        parent::init();
    }
    
    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = ArrayHelper::merge(parent::actions(), [
            "create" => [
                'fields' => [$this, 'updateFields'],
                'size'           => BackendAction::SIZE_SMALL,
                'buttons'        => ['save'],
                /*"accessCallback" => function ($model) {
            
                    $cmsUserEmail = new CmsUserEmail();
                    $cmsUserEmail->load(\Yii::$app->request->get());
                    
                    if ($model) {
                        return \Yii::$app->user->can("cms/admin-user/update", ['model' => $cmsUserEmail->cmsUser]);
                    }
                    
                    return false;
                },*/
            ],
            "update" => [
                'fields' => [$this, 'updateFields'],
                'size'           => BackendModelUpdateAction::SIZE_SMALL,
                'buttons'        => ['save'],
                /*"accessCallback" => function ($model) {
                    if ($this->model) {
                        return \Yii::$app->user->can("cms/admin-user/update", ['model' => $this->model]);
                    }
                    return false;
                },*/
            ],
            "delete" => [
                /*"accessCallback" => function ($model) {
                    if ($this->model) {
                        return \Yii::$app->user->can("cms/admin-user/update", ['model' => $this->model]);
                    }
                    return false;
                },*/
            ],
        ]);

        return $actions;
    }

    public function updateFields($action)
    {
        $model = $action->model;
        $model->load(\Yii::$app->request->get());

        $result = [
            'link_type' => [
                'class' => SelectField::class,
                'widgetConfig' => [
                    'data' => CmsCompanyLink::getLinkTypes()
                ]

            ],
            [
                'class' => HtmlBlock::class,
                'content' => '<div class="row no-gutters"><div class="col-12" style="max-width: 500px;">'
            ],
            'url',
            [
                'class' => HtmlBlock::class,
                'content' => '</div><div class="col-12" style="max-width: 500px;">'
            ],
            'name' => [
                'class' => TextField::class,
            ],
            [
                'class' => HtmlBlock::class,
                'content' => '</div></div>'
            ],
            [
                'class' => HtmlBlock::class,
                'content' => '<div style="display: none;">'
            ],
            'cms_company_id',
            [
                'class' => HtmlBlock::class,
                'content' => '</div>'
            ],

        ];

        return $result;
    }

}
