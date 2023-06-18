<?php
/**
 * AdminUserController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\controllers;

use common\models\User;
use skeeks\cms\actions\backend\BackendModelMultiActivateAction;
use skeeks\cms\actions\backend\BackendModelMultiDeactivateAction;
use skeeks\cms\backend\actions\BackendGridModelRelatedAction;
use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\backend\actions\BackendModelUpdateAction;
use skeeks\cms\backend\BackendAction;
use skeeks\cms\backend\BackendController;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\ViewBackendAction;
use skeeks\cms\base\DynamicModel;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\grid\DateTimeColumnData;
use skeeks\cms\grid\ImageColumn2;
use skeeks\cms\helpers\Image;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsContractor;
use skeeks\cms\models\CmsContractorMap;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsTree;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\CmsUserLog;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModel;
use skeeks\cms\queryfilters\filters\modes\FilterModeEq;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\shop\models\ShopBonusTransaction;
use skeeks\cms\shop\models\ShopOrder;
use skeeks\cms\widgets\ActiveForm;
use skeeks\cms\widgets\GridView;
use skeeks\yii2\dadataClient\models\PartyModel;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\WidgetField;
use Yii;
use yii\base\Event;
use yii\base\Exception;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\rbac\Item;
use yii\web\Response;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminUserLogController extends BackendController
{
    public function init()
    {
        $this->name = "Действия пользователей";

        parent::init();
    }

    public function actions()
    {
        $actions = ArrayHelper::merge(parent::actions(), [

            "index" => [
                'class' => ViewBackendAction::class
            ],
        ]);


        return $actions;
    }



}
