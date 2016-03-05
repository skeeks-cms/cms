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
use skeeks\cms\models\CmsContent;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsContentElementProperty;
use skeeks\cms\models\CmsContentProperty;
use skeeks\cms\models\CmsSearchPhrase;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\rbac\CmsManager;
use yii\base\BootstrapInterface;
use yii\data\ActiveDataProvider;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\Application;
use yii\web\View;

use \Yii;
use yii\widgets\ActiveForm;

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
            'name'          => 'Поиск',
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

    public $searchElementContentIds = [];

    public $searchElementFields =
    [
        'description_full',
        'description_short',
        'name',
    ];
    public $enabledElementProperties              = Cms::BOOL_Y;
    public $enabledElementPropertiesSearchable    = Cms::BOOL_Y;

    public $searchQueryParamName = "q";

    public $phraseLiveTime = 0;


    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['searchQueryParamName'], 'string'],
            [['enabledElementProperties'], 'string'],
            [['enabledElementPropertiesSearchable'], 'string'],
            [['phraseLiveTime'], 'integer'],
            [['searchElementFields'], 'safe'],
            [['searchElementContentIds'], 'safe'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'searchQueryParamName'                  => 'Параметр поискового запроса в адресной строке',
            'searchElementFields'                   => 'Основной набор полей элементов, по которым производить поиск',
            'enabledElementProperties'              => 'Искать среди дополнительных полей элементов',
            'enabledElementPropertiesSearchable'    => 'Учитывать настройки дополнительных полей при поиске по ним',
            'searchElementContentIds'               => 'Искать элементы контента следующих типов',
            'phraseLiveTime'                        => 'Время хранения поисковых запросов',
        ]);
    }

    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [
            'searchQueryParamName'              => 'Название параметра для адресной строки',
            'phraseLiveTime'                    => 'Если указано 0 то поисковые запросы не будут удалятся никогда',
            'enabledElementProperties'          => 'Включая эту опцию, поиск начнет учитывать дополнительные поля элементов',
            'enabledElementPropertiesSearchable'=> 'Каждое дополнительное свойство имеет свои настройки. Эта опция включит поиск не по всем дополнительным свойствам, а только с включеной опцией "Значения свойства участвуют в поиске"',
        ]);
    }


    public function renderConfigForm(ActiveForm $form)
    {
        echo $form->fieldSet(\Yii::t('app', 'Main'));

            echo $form->field($this, 'searchQueryParamName');
            echo $form->fieldInputInt($this, 'phraseLiveTime');

        echo $form->fieldSetEnd();


        echo $form->fieldSet('Поиск элементов');

            echo $form->fieldSelectMulti($this, 'searchElementContentIds', CmsContent::getDataForSelect() );
            echo $form->fieldSelectMulti($this, 'searchElementFields', (new \skeeks\cms\models\CmsContentElement())->attributeLabels() );
            echo $form->fieldRadioListBoolean($this, 'enabledElementProperties');
            echo $form->fieldRadioListBoolean($this, 'enabledElementPropertiesSearchable');

        echo $form->fieldSetEnd();

        echo $form->fieldSet('Поиск разделов');
            echo 'В разработке';
        echo $form->fieldSetEnd();
    }

    /**
     * @return string
     */
    public function getSearchQuery()
    {
        return (string) \Yii::$app->request->get($this->searchQueryParamName);
    }

    /**
     * Конфигурирование объекта запроса поиска по элементам.
     *
     * @param \yii\db\ActiveQuery $activeQuery
     * @param null $modelClassName
     * @return $this
     */
    public function buildElementsQuery(\yii\db\ActiveQuery $activeQuery)
    {
        $where = [];

        //Нужно учитывать связанные дополнительные данные
        if ($this->enabledElementProperties == Cms::BOOL_Y)
        {
            $activeQuery->joinWith('cmsContentElementProperties');

            //Нужно учитывать настройки связанные дополнительные данных
            if ($this->enabledElementPropertiesSearchable == Cms::BOOL_Y)
            {
                $activeQuery->joinWith('cmsContentElementProperties.property');

                $where[] = ['and',
                    ['like', CmsContentElementProperty::tableName() . ".value", '%' . $this->searchQuery . '%', false],
                    [CmsContentProperty::tableName() . ".searchable" => Cms::BOOL_Y]
                ];
            } else
            {
                $where[] = ['like', CmsContentElementProperty::tableName() . ".value", '%' . $this->searchQuery . '%', false];
            }
        }

        //Поиск по основному набору полей
        if ($this->searchElementFields)
        {
            foreach ($this->searchElementFields as $fieldName)
            {
                $where[] = ['like', CmsContentElement::tableName() . "." . $fieldName, '%' . $this->searchQuery . '%', false];
            }
        }

        if ($where)
        {
            $where = array_merge(['or'], $where);
            $activeQuery->andWhere($where);
        }

        //Отфильтровать только конкретный тип
        if ($this->searchElementContentIds)
        {
            $activeQuery->andWhere([
                CmsContentElement::tableName() . ".content_id" => (array) $this->searchElementContentIds
            ]);
        }

        return $this;
    }

    /**
     * @param ActiveDataProvider $dataProvider
     */
    public function logResult(ActiveDataProvider $dataProvider)
    {
        $pages = 1;

        if ($dataProvider->totalCount > $dataProvider->pagination->pageSize)
        {
            $pages = round($dataProvider->totalCount / $dataProvider->pagination->pageSize);
        }

        $searchPhrase = new CmsSearchPhrase([
            'phrase'        => $this->searchQuery,
            'result_count'  => $dataProvider->totalCount,
            'pages'         => $pages,
        ]);

        $searchPhrase->save();
    }
}