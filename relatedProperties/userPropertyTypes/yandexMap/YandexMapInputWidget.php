<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.03.2016
 */
namespace skeeks\cms\relatedProperties\userPropertyTypes\yandexMap;

use skeeks\cms\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;
use Yii;

/**
 * Class YandexMapInputWidget
 * @package skeeks\cms\relatedProperties\userPropertyTypes\yandexMap
 */
class YandexMapInputWidget extends InputWidget
{
    /**
     * @var array
     */
    public $clientOptions = [];

    public $fieldNameLat        = null;
    public $fieldNameLng        = null;
    public $fieldNameAddress    = null;

    public $yandexMapStyles     = 'width: 100%; height: 400px;';

    /**
     * @throws Exception
     */
    private function _initAndValidate()
    {
        if (!$this->hasModel())
        {
            throw new Exception(\Yii::t('app','This file is intended only for forms model'));
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
            $idMap      = $id . "-map";

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
                'fieldNameAddress'  => Html::getInputId($this->model, $this->fieldNameAddress),
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

}
