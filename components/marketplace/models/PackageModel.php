<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.06.2015
 */
namespace skeeks\cms\components\marketplace\models;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsExtension;
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
 * @property string $imageSrc
 * @property string $imagesSrc
 * @property string $url
 * @property string $authorName
 * @property string $authorImage
 * @property UrlHelper $adminUrl
 *
 * Class PackageModel
 * @package skeeks\cms\components\marketplace
 */
class PackageModel extends Model
{
    public $apiData = [];

    /**
     * @param $packagistCode
     * @return null|static
     */
    static public function fetchByCode($packagistCode)
    {
        $result = \Yii::$app->cmsMarkeplace->get(['packages/view-by-code', [
            'packagistCode' => (string) $packagistCode
        ]]);

        if (!$result)
        {
            return null;
        }

        return new static([
            'apiData' => $result
        ]);
    }
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
            //'codes'  => $extensionCodes,
            'per-page'      => 200
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
     * @return string
     */
    public function getPackagistCode()
    {
        return (string) ArrayHelper::getValue($this->apiData, 'related.packagist_code');
    }

    /**
     * @return string
     */
    public function getPackagistUrl()
    {
        return (string) 'https://packagist.org/packages/' . $this->packagistCode;
    }

    /**
     * @return string
     */
    public function getSupport()
    {
        return (string) ArrayHelper::getValue($this->apiData, 'related.support');
    }

    /**
     * @return string
     */
    public function getInstallHelp()
    {
        return (string) ArrayHelper::getValue($this->apiData, 'related.install');
    }

    /**
     * @return string
     */
    public function getDemoUrl()
    {
        return (string) ArrayHelper::getValue($this->apiData, 'related.demo_url');
    }
    /**
     * @return string
     */
    public function getVideoUrl()
    {
        return (string) ArrayHelper::getValue($this->apiData, 'related.video_url');
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->absoluteUrl;
    }

    /**
     * @return UrlHelper
     */
    public function getAdminUrl()
    {
        return UrlHelper::construct('/cms/admin-marketplace/catalog', ['code' => $this->packagistCode])
            ->enableAdmin();
    }

    /**
     * @return bool
     */
    public function isInstalled()
    {
        $extensions = ArrayHelper::map(\Yii::$app->extensions, 'name', 'name');

        return (bool) ArrayHelper::getValue($extensions, $this->packagistCode);
    }

    /**
     * @return null|CmsExtension
     */
    public function createCmsExtension()
    {
        if (!$this->isInstalled())
        {
            return null;
        }

        $extensionData  = ArrayHelper::getValue(\Yii::$app->extensions, $this->packagistCode);
        $cmsExtension   = new CmsExtension($extensionData);

        $cmsExtension->marketplacePackage = $this;
        return $cmsExtension;
    }
}