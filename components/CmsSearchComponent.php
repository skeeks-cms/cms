<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.03.2015
 */
namespace skeeks\cms\components;
use skeeks\cms\actions\ViewModelAction;
use skeeks\cms\assets\CmsToolbarAsset;
use skeeks\cms\assets\CmsToolbarAssets;
use skeeks\cms\assets\CmsToolbarFancyboxAsset;
use skeeks\cms\exceptions\NotConnectedToDbException;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\rbac\CmsManager;
use yii\base\BootstrapInterface;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\Application;
use yii\web\View;

use \Yii;

/**
 * @property string searchQuery
 *
 * Class CmsSearchComponent
 * @package skeeks\cms\components
 */
class CmsSearchComponent extends \skeeks\cms\base\Component
{
    /**
     * Можно задать название и описание компонента
     * @return array
     */
    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name'          => 'Поиск по сайту',
        ]);
    }

    /**
     * Файл с формой настроек, по умолчанию
     *
     * @return string
     */
    public function getConfigFormFile()
    {
        $class = new \ReflectionClass($this->className());
        return dirname($class->getFileName()) . DIRECTORY_SEPARATOR . 'CmsSearchComponent/_form.php';
    }

    public $searchQueryParamName = "q";

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['searchQueryParamName'], 'string'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'searchQueryParamName'               => 'Параметр поискового запроса в адресной строке',
        ]);
    }

    /**
     * @return string
     */
    public function getSearchQuery()
    {
        return (string) \Yii::$app->request->get($this->searchQueryParamName);
    }
}