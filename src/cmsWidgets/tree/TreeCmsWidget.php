<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */

namespace skeeks\cms\cmsWidgets\tree;

use skeeks\cms\backend\widgets\ActiveFormBackend;
use skeeks\cms\base\WidgetRenderable;
use skeeks\cms\models\CmsTree;
use skeeks\cms\query\CmsActiveQuery;
use skeeks\cms\widgets\formInputs\selectTree\SelectTreeInputWidget;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\FieldSetEnd;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\WidgetField;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
/**
 *
 * Example 1
 * <?php
        $widget = \skeeks\cms\cmsWidgets\tree\TreeCmsWidget::beginWidget('menu-top');
        $widget->descriptor->name = 'Главное верхнее меню';
        $widget->viewFile = '@app/views/widgets/TreeMenuCmsWidget/menu-top';
        $widget::end();
    ?>
 *
 * Example 2
 * <?php
        $widget = \skeeks\cms\cmsWidgets\tree\TreeCmsWidget::beginWidget('sub-catalog');
        $widget->descriptor->name = 'Подразделы каталога';
        $widget->viewFile = '@app/views/widgets/TreeMenuCmsWidget/sub-catalog-small';
        $widget->parent_tree_id = $model->id;
        $widget->activeQuery->with('image');
        $widget::end();
    ?>
 *
 * Example 3
 * <?php
        $catalogTree = \skeeks\cms\models\CmsTree::find()->cmsSite()->joinWith('treeType as treeType')->andWhere(['treeType.code' => 'catalog'])->orderBy(['level' => SORT_ASC])->limit(1)->one();
        $config = [];
        if ($catalogTree) {
            $config['parent_tree_id'] = $catalogTree->id;
        }
        $widget = \skeeks\cms\cmsWidgets\tree\TreeCmsWidget::beginWidget('home-tree-slider', $config);
        $widget->descriptor->name = 'Слайдер разделов';
        $widget->viewFile = '@app/views/widgets/TreeMenuCmsWidget/revolution-slider';
        $widget->is_has_image_only = true;
        $widget->activeQuery->with('image');
        $widget::end();
   ?>
 *
 * @property CmsActiveQuery $activeQuery
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class TreeCmsWidget extends WidgetRenderable
{

    /**
     * @var null
     */
    public $limit = 200;

    /**
     * @var null
     */
    public $parent_tree_id = null;
    /**
     * @var null
     */
    public $is_active_only = true;

    /**
     * @var null
     */
    public $is_has_image_only = false;

    /**
     * @var array
     */
    public $tree_type_ids = [];

    /**
     * @var string
     */
    public $viewFile = '';

    /**
     * @var string
     */
    public $cmsTreeClass = CmsTree::class;

    /**
     * Сортировка по умолчанию
     * @var string
     */
    public $sorting_option = "priority";

    /**
     * @var int
     */
    public $sorting_direction = SORT_ASC;


    public static function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name' => \Yii::t('skeeks/cms', 'Подразделы'),
        ]);
    }

    protected $_activeQuery = null;


    /**
     * @return CmsActiveQuery|null
     */
    public function getActiveQuery()
    {
        if ($this->_activeQuery === null) {
            $class = $this->cmsTreeClass;
            $table = $class::tableName();
            $this->_activeQuery = $class::find();

            /**
             * @var $query CmsActiveQuery
             */
            $query = $this->_activeQuery;
            $query->cmsSite();

            if ($this->limit) {
                $query->limit($this->limit);
            }

            /**
             *
             */
            if ($this->tree_type_ids) {
                $query->andWhere([$table . ".tree_type_id" => $this->tree_type_ids]);
            }

            if ($this->parent_tree_id) {
                $query->andWhere([$table.'.pid' => $this->parent_tree_id]);
            } else {
                $query->andWhere([$table.'.level' => 1]);
            }

            if ($this->is_active_only) {
                $query->active();
            }

            if ($this->is_has_image_only) {
                $query->andWhere(['is not', $table.'.image_id', null]);
            }

            $this->initSorting($query);
        }

        return $this->_activeQuery;
    }

    /**
     * @param ActiveQuery $query
     * @return $this
     */
    public function initSorting(ActiveQuery $query)
    {
        $class = $this->cmsTreeClass;
        $table = $class::tableName();
        $direction = $this->sorting_direction;
        if ($this->sorting_option) {
            if ($this->sorting_option == 'has_image') {
                $query->orderBy([$table.'.image_id' => (int)$direction]);
            } else {
                $query->orderBy([$table.'.'.$this->sorting_option => (int)$direction]);
            }
        }
        return $this;
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'limit'             => \Yii::t('skeeks/cms', 'Максимальное количество разделов'),
            'is_active_only'    => \Yii::t('skeeks/cms', 'Показывать только активные разделы?'),
            'is_has_image_only' => \Yii::t('skeeks/cms', 'Показывать только разделы с фото?'),
            'tree_type_ids'     => \Yii::t('skeeks/cms', 'Типы разделов'),
            'sorting_option'    => \Yii::t('skeeks/cms', 'Параметр сортировки'),
            'sorting_direction' => \Yii::t('skeeks/cms', 'Направление сортировки'),
            'parent_tree_id'    => \Yii::t('skeeks/cms', 'Корневой раздел'),
        ]);
    }

    public function attributeHints()
    {
        return array_merge(parent::attributeLabels(), [
            'limit'          => 'Задайте максимальное количество разделов, которое может отображаться в этом виджете',
            'is_active_only' => 'Эта настройка включает отображение только активных разделов (скрытые разделы отображаться не будут!)',
            'tree_type_ids'  => 'Показывать разделы только выбранных типов, если ничего не указано то будут показаны разделы всех типов',
            'parent_tree_id' => 'Если корневой раздел не задан, то будут отображаться разделы самого верхнего уровня',
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            ['parent_tree_id', 'integer'],
            ['sorting_option', 'string'],
            ['sorting_direction', 'integer'],
            ['tree_type_ids', 'safe'],
            ['is_has_image_only', 'boolean'],
            ['is_active_only', 'boolean'],
            ['limit', 'integer', 'max' => 400],
        ]);
    }

    /**
     * @return ActiveForm
     */
    public function beginConfigForm()
    {
        return ActiveFormBackend::begin();
    }

    /**
     * @return array
     */
    public function getConfigFormFields()
    {
        return [
            'filters' => [
                'class'  => FieldSet::class,
                'name'   => 'Фильтрация и количество',
                'fields' => $this->getFiltersFields(),
            ],
        ];
    }

    public function getFiltersFields()
    {
        return [
            'limit'             => [
                'class' => NumberField::class,
            ],
            'is_active_only'    => [
                'class'     => BoolField::class,
                'allowNull' => false,
            ],
            'is_has_image_only' => [
                'class'     => BoolField::class,
                'allowNull' => false,
            ],
            'tree_type_ids'     => [
                'class'    => SelectField::class,
                'items'    => \yii\helpers\ArrayHelper::map(
                    \skeeks\cms\models\CmsTreeType::find()->all(), 'id', 'name'
                ),
                'multiple' => true,
            ],

            'sorting_option'    => [
                'class' => SelectField::class,
                'items' => [
                    'priority'  => 'Приоритет',
                    'name'      => 'Название',
                    'has_image' => 'Наличие картинки',
                ],
            ],
            'sorting_direction' => [
                'class' => SelectField::class,
                'items' => [
                    SORT_ASC  => \Yii::t('skeeks/cms', 'ASC (from lowest to highest)'),
                    SORT_DESC => \Yii::t('skeeks/cms', 'DESC (from highest to lowest)'),
                ],
            ],


            'parent_tree_id' => [
                'class'        => WidgetField::class,
                'widgetClass'  => SelectTreeInputWidget::class,
                'widgetConfig' => [
                    'isAllowNodeSelectCallback' => function ($tree) {
                        /*if (in_array($tree->id, $childrents)) {
                            return false;
                        }*/

                        return true;
                    },
                    'treeWidgetOptions'         => [
                        'models' => CmsTree::findRoots()->cmsSite()->all(),
                    ],
                ],
            ],
        ];
    }
}