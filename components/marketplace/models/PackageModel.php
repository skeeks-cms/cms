<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.06.2015
 */
namespace skeeks\cms\components\marketplace\models;
use skeeks\yii2\curl\Curl;
use yii\base\Component;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * @property integer $id
 * @property integer $created_by
 * @property integer $created_at
 * @property string $name
 * @property string $packagistCode
 * @property string $image
 * @property string $url
 * @property string $authorName
 * @property string $authorImage
 *
 * Class PackageModel
 * @package skeeks\cms\components\marketplace
 */
class PackageModel extends Model
{
    public $apiData = [];

    /**
     * Установленные пакеты
     *
     * @return static[]
     */
    static public function fetchInstalls()
    {
        //Коды установленных пакетов
        $extensionCodes = ArrayHelper::map(\Yii::$app->extensions, 'name', 'name');

        $result = \Yii::$app->cmsMarkeplace->get(['packages', [
            //'packages' => $extensionCodes
            'per-page' => 200
        ]]);

        $items = ArrayHelper::getValue($result, 'items');

        $resultModels = [];

        if ($items)
        {
            foreach ($items as $data)
            {
                $model = new static([
                    'apiData' => $data
                ]);

                $resultModels[$model->getPackagistCode()] = $model;
            }
        }

        return $resultModels;
    }

    /**
     * @param string $name
     * @return mixed
     * @throws \yii\base\UnknownPropertyException
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->apiData))
        {
            return ArrayHelper::getValue($this->apiData, $name);
        }

        return parent::__get($name);
    }

    /**
     * @return mixed
     */
    public function getPackagistCode()
    {
        return (string) ArrayHelper::getValue($this->apiData, 'related.packagist_code');
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->absoluteUrl;
    }

}