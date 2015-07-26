<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.05.2015
 */

namespace skeeks\cms\modules\admin\traits;

use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\components\Cms;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\validators\db\IsNewRecord;
use skeeks\sx\validate\Validate;
use skeeks\widget\chosen\Chosen;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\Pjax;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\ActiveField;

/**
 * Class AdminActiveFormTrait
 * @package skeeks\cms\modules\admin\traits
 */
trait AdminModelEditorStandartControllerTrait
{

    /**
     * @param $model
     * @param $action
     * @return bool
     */
    public function eachMultiActivate($model, $action)
    {
        try
        {
            $model->active = Cms::BOOL_Y;
            return $model->save(false);
        } catch (\Exception $e)
        {
            return false;
        }
    }

    /**
     * @param $model
     * @param $action
     * @return bool
     */
    public function eachMultiInActivate($model, $action)
    {
        try
        {
            $model->active = Cms::BOOL_N;
            return $model->save(false);
        } catch (\Exception $e)
        {
            return false;
        }
    }

}