<?php
/**
 * Базовая модель страницы сайта.
 *
 * У каждой страницы сайта есть:
 *  - метатеги,
 *  - адрес (seo_page_name)
 *  - name (название страницы)
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\base\models;

use skeeks\cms\models\behaviors\HasMetaData;
use skeeks\cms\models\behaviors\SeoPageName;
use Yii;

/**
 * @property string $name
 * @property string $seo_page_name
 *
 * Class Publication
 * @package skeeks\cms\base\models
 */
abstract class Page extends Core
{
    use \skeeks\cms\models\behaviors\traits\HasMetaData;
    /**
     * @var string
     */
    protected $_viewPage = "module/controller/view";

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            SeoPageName::className(),
            HasMetaData::className()
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'name' => Yii::t('app', 'Name'),
            'meta_title' => Yii::t('app', 'Meta Title'),
            'meta_description' => Yii::t('app', 'Meta Description'),
            'meta_keywords' => Yii::t('app', 'Meta Keywords'),
            'seo_page_name' => Yii::t('app', 'Seo Page Name'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['name', 'meta_title'], 'string', 'max' => 255],
            [['seo_page_name'], 'string', 'max' => 64],
            [['seo_page_name'], 'unique'],
            [['meta_description', 'meta_keywords'], 'string'],
        ]);
    }
    /**
     * @return string
     */
    public function createPageUrl()
    {
        return \Yii::$app->urlManager->createUrl([$this->_viewPage, "seo_page_name" => $this->seo_page_name]);
    }

    /**
     * TODO: old function
     * @return string
     */
    public function getPageUrl()
    {
        return $this->createPageUrl();
    }
}
