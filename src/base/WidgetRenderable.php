<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.05.2015
 */

namespace skeeks\cms\base;

use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\NumberField;
use yii\caching\ChainedDependency;
use yii\caching\Dependency;
use yii\caching\TagDependency;
use yii\console\Application;
use yii\helpers\ArrayHelper;

/**
 * Class WidgetRenderable
 * @package skeeks\cms\base
 */
class WidgetRenderable extends Widget
{
    /**
     * @var null Файл в котором будет реднериться виджет
     */
    public $viewFile = "default";

    /**
     * @var bool Кэш включен?
     */
    public $is_cache = false;

    /**
     * @var bool Кэш для каждого из пользователей свой?
     */
    public $is_cache_unique_for_user = true;

    /**
     * @var int Время жизни кэша
     */
    public $cache_duration = 0;

    /**
     * @var array
     */
    public $cache_tags = [];

    /**
     * @var array 
     */
    public $params = [];


    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'viewFile'                 => \Yii::t('skeeks/cms', 'File-template'),
            'is_cache'                 => "Включить кэширование",
            'cache_duration'           => "Время жизни кэша",
            'is_cache_unique_for_user' => "У каждого юзера свой кэш?",
        ]);
    }

    public function attributeHints()
    {
        return array_merge(parent::attributeHints(), [
            'is_cache'                 => "Внимание если в виджете подключаются какие либо скрипты, то возможно, ваш виджет с кэшированием будет работать некорректно.",
            'cache_duration'           => "Максимальное время жизни кэша. Но он может сброситься и раньше по мере необходимости.",
            'is_cache_unique_for_user' => "Если эта настройка включена, то у каждого авторизованного пользоателя этот блок будет кэшироваться по своему.",
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['viewFile'], 'string'],

            [['is_cache'], 'boolean'],
            [['cache_duration'], 'integer'],
            [['is_cache_unique_for_user'], 'boolean'],
        ]);
    }

    /**
     * @return array
     */
    protected function _getConfigFormCache()
    {
        return [
            'cache' => [
                'class'  => FieldSet::class,
                'name'   => 'Настройки кэширования',
                'fields' => [
                    'is_cache'                 => [
                        'class'     => BoolField::class,
                        'allowNull' => false,
                    ],
                    'cache_duration'           => [
                        'class'  => NumberField::class,
                        'append' => "сек",
                    ],
                    'is_cache_unique_for_user' => [
                        'class'     => BoolField::class,
                        'allowNull' => false,
                    ],
                ],
            ],
        ];
    }

    /*public function run()
    {
        if ($this->viewFile) {
            return $this->render($this->viewFile, [
                'widget' => $this,
            ]);
        } else {
            return \Yii::t('skeeks/cms', "Template not found");
        }
    }*/

    public function getCacheTags()
    {
        $this->cache_tags;
    }

    /**
     * @var null|Dependency
     */
    protected $_cacheDependency = null;

    /**
     * @return Dependency|ChainedDependency
     */
    public function getCacheDependency()
    {
        if ($this->_cacheDependency === null) {
            $dependency = new ChainedDependency();
            $dependency->dependencies = [
                new TagDependency([
                    'tags' => [
                        \Yii::$app instanceof Application ? "console" : "web",
                        static::class,
                        $this->namespace,
                        $this->cmsUser ? $this->cmsUser->cacheTag : '',
                        $this->cmsSite ? $this->cmsSite->cacheTag : '',
                    ],
                ]),
            ];

            $this->_cacheDependency = $dependency;
        }

        return $this->_cacheDependency;
    }

    /**
     * @param Dependency $dependency
     * @return $this
     */
    public function setCacheDependency(Dependency $dependency)
    {
        $this->_cacheDependency = $dependency;
        return $this;
    }


    public function run()
    {
        $cacheKey = $this->getCacheKey($this->is_cache_unique_for_user).'WidgetRenderableRun';

        $result = \Yii::$app->cache->get($cacheKey);
        if ($result === false || $this->is_cache === false) {

            if ($this->viewFile) {
                try {
                    $result = $this->render($this->viewFile, ArrayHelper::merge($this->params, [
                        'widget' => $this,
                    ]));
                } catch (\Exception $e) {
                    $result = $e->getMessage();
                }
                
            } else {
                $result = \Yii::t('skeeks/cms', "Template not found");
            }

            \Yii::$app->cache->set($cacheKey, $result, (int)$this->cache_duration, $this->getCacheDependency());
        }

        return $result;
    }
}