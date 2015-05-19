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
namespace skeeks\cms\models;

use skeeks\cms\models\behaviors\HasMetaData;
use skeeks\cms\models\behaviors\HasSeoPageUrl;
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
    use \skeeks\cms\models\behaviors\traits\HasPageUrl;

    /**
     * @var string
     */
    public $viewPageTemplate = "module/controller/view";

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            SeoPageName::className(),
            [
                "class"             => HasSeoPageUrl::className(),
                "viewPageTemplate"  => $this->viewPageTemplate
            ]
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'name' => Yii::t('app', 'Name'),
            'seo_page_name' => Yii::t('app', 'Seo Page Name'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['name'], 'string', 'max' => 255],
            [['seo_page_name'], 'string', 'max' => 64],
            [['seo_page_name'], 'unique'],
        ]);
    }

}
