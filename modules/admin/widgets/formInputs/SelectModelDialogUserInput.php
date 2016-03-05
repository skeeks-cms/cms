<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.03.2015
 */
namespace skeeks\cms\modules\admin\widgets\formInputs;

use skeeks\cms\Exception;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\Publication;
use skeeks\cms\modules\admin\Module;
use skeeks\sx\validate\Validate;
use yii\db\ActiveRecord;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\Application;
use yii\widgets\InputWidget;
use Yii;

/**
 * @property $modelData
 *
 * Class SelectModelDialogInput
 * @package skeeks\cms\modules\admin\widgets\formInputs
 */
class SelectModelDialogUserInput extends SelectModelDialogInput
{
    /**
     * @var string
     */
    public $baseRoute = 'cms/tools/select-cms-user';

    /**
     * @var string
     */
    public $viewFile  = 'select-model-dialog-user-input';

    /**
     * @return CmsUser
     */
    public function getModelData()
    {
        if ($id = $this->model->{$this->attribute})
        {
            return CmsUser::findOne($id);
        }
    }
}