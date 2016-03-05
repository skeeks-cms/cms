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
class SelectModelDialogInput extends InputWidget
{
    /**
     * @var array
     */
    public $clientOptions = [];

    /**
     * @var string Путь к выбору
     */
    public $selectUrl = '';

    /**
     * @var string
     */
    public $baseRoute;

    /**
     * @var boolean whether to show deselect button on single select
     */
    public $allowDeselect = true;

    /**
     * @var bool
     */
    public $closeWindow = true;


    public $viewFile  = 'select-model-dialog-input';


    public function init()
    {
        parent::init();

        if (!$this->selectUrl)
        {
            $additionalData = [];
            $additionalData['callbackEvent'] = $this->getCallbackEvent();

            $this->selectUrl = \skeeks\cms\helpers\UrlHelper::construct($this->baseRoute, $additionalData)
                                ->setSystemParam(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_EMPTY_LAYOUT, 'true')
                                ->enableAdmin()
                                ->toString();
        }

    }

    /**
     * @return null|static
     */
    public function getModelData()
    {
        if ($id = $this->model->{$this->attribute})
        {
            return [
                'id' => $id
            ];
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        try
        {
            echo $this->render($this->viewFile, [
                'widget' => $this,
            ]);

        } catch (Exception $e)
        {
            echo $e->getMessage();
        }
    }

    /**
     * @return string
     */
    public function getCallbackEvent()
    {
        return $this->id . '-select-dialog';
    }

    /**
     * @return string
     */
    public function getJsonOptions()
    {
        return Json::encode([
            'id'                        => $this->id,
            'callbackEvent'             => $this->getCallbackEvent(),
            'selectUrl'                 => $this->selectUrl,
            'closeWindow'               => (int) $this->closeWindow,
        ]);

    }
}