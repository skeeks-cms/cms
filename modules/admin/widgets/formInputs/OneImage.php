<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.03.2015
 */
namespace skeeks\cms\modules\admin\widgets\formInputs;

use skeeks\cms\Exception;
use skeeks\cms\models\Publication;
use skeeks\cms\modules\admin\Module;
use skeeks\cms\validators\HasBehavior;
use skeeks\sx\validate\Validate;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\Application;
use yii\widgets\InputWidget;
use Yii;

/**
 * Class OneImage
 * @package skeeks\cms\modules\admin\widgets\formInputs
 */
class OneImage extends InputWidget
{
    /**
     * @var array
     */
    public $clientOptions = [];

    /**
     * @var string Путь к выбору файлов
     */
    public $selectFileUrl = '';

    /**
     * @var null
     */
    public $filesModel = null;

    public function init()
    {
        parent::init();

        if (!$this->selectFileUrl)
        {
            $additionalData = [];

            $modelForFile = $this->model;

            if ($this->filesModel)
            {
                $modelForFile = $this->filesModel;
            }

            /*if (Validate::isValid(new HasBehavior(HasFiles::className()), $modelForFile) && !$modelForFile->isNewRecord)
            {
                $additionalData = $modelForFile->getRef()->toArray();
            }*/

            $additionalData['callbackEvent'] = $this->getCallbackEvent();

            $this->selectFileUrl = \skeeks\cms\helpers\UrlHelper::construct('cms/tools/select-file', $additionalData)
                                        ->setSystemParam(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_EMPTY_LAYOUT, 'true')
                                        ->enableAdmin()
                                        ->toString();
        }
    }
    /**
     * @inheritdoc
     */
    public function run()
    {
        try
        {
            echo $this->render('one-image', [
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
        return $this->id . '-select-file';
    }

    /**
     * @return string
     */
    public function getJsonOptions()
    {
        return Json::encode([
            'id'                        => $this->id,
            'callbackEvent'             => $this->getCallbackEvent(),
            'selectFileUrl'             => $this->selectFileUrl,
        ]);

    }
}