<?php
/**
 * Map
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 22.01.2015
 * @since 1.0.0
 */
namespace skeeks\cms\widgets\formInputs\yandex;

use skeeks\cms\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;
use Yii;

/**
 * Class Map
 * @package skeeks\cms\widgets\formInputs\yandex
 */
class Map extends InputWidget
{
    /**
     * @var array the options for the Bootstrap File Input plugin. Default options have exporting enabled.
     * Please refer to the Bootstrap File Input plugin Web page for possible options.
     * @see http://plugins.krajee.com/file-input#options
     */
    public $clientOptions = [];

    public $fieldNameLat        = 'lat';
    public $fieldNameLng        = 'lng';
    public $fieldNameAddress    = 'address';

    public $showAddressField     = true;

    public $yandexMapStyles     = 'width: 100%; height: 400px;';

    /**
     * Берем поведения модели
     *
     */
    private function _initAndValidate()
    {
        if (!$this->hasModel())
        {
            throw new Exception("Этот файл рассчитан только для форм с моделью");
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        try
        {
            $this->_initAndValidate();

            $this->registerClientScript();

            $id         = "sx-id-" . Yii::$app->security->generateRandomString(6);
            $idMap      = "sx-id-" . Yii::$app->security->generateRandomString(6);

            $clientOptions = ArrayHelper::merge($this->clientOptions, [
                'idMap' => $idMap,

                'yandex' =>
                [
                    'zoom' => 10,
                    'center' =>
                    [
                        55.75241746329202,
                        37.62104013208003
                    ],
                    //'autoFitToViewport' => "always",
                    'controls' =>
                        [
                            //'smallMapDefaultSet',
                            'routeEditor',
                            'rulerControl',
                            'typeSelector',
                            'fullscreenControl',
                            'zoomControl'
                        ]
                ],

                'fieldNameLng'      => Html::getInputId($this->model, $this->fieldNameLng),
                'fieldNameLat'      => Html::getInputId($this->model, $this->fieldNameLat),
                'fieldNameAddress'  => $this->showAddressField ? Html::getInputId($this->model, $this->fieldNameAddress) : '',
            ]);

            return $this->render('map', [
                'widget'            => $this,
                'id'                => $id,
                'idMap'             => $idMap,
                'model'             => $this->model,
                'clientOptions'     => $clientOptions,
                'yandexMapStyles'   => $this->yandexMapStyles,
            ]);

        } catch (Exception $e)
        {
            echo $e->getMessage();
        }

    }

    /**
     * Registers Bootstrap File Input plugin
     */
    public function registerClientScript()
    {
        $view = $this->getView();
        Asset::register($view);
    }
}
