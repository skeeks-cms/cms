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
            'packages' => $extensionCodes
        ]]);

        $resultModels = [];

        if ($result)
        {
            foreach ($result as $data)
            {
                $resultModels[] = new static([
                    'apiData' => $data
                ]);
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

        parent::__get($name);
    }

}