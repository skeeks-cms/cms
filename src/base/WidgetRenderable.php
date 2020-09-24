<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.05.2015
 */

namespace skeeks\cms\base;

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
     * @var bool 
     */
    public $is_cache = false;

    /**
     * @var int 
     */
    public $cache_duration = 0;

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'viewFile' => \Yii::t('skeeks/cms', 'File-template'),
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['viewFile'], 'string'],
        ]);
    }

    public function run()
    {
        if ($this->viewFile) {
            return $this->render($this->viewFile, [
                'widget' => $this,
            ]);
        } else {
            return \Yii::t('skeeks/cms', "Template not found");
        }
    }
    
    
    
    public function run()
    {
        $cacheKey = $this->getCacheKey().'run';

        $dependency = new TagDependency([
            'tags' => [
                $this->className().(string)$this->namespace,
                (new CmsContentElement())->getTableCacheTagCmsSite(),
            ],
        ]);

        $result = \Yii::$app->cache->get($cacheKey);
        if ($result === false || $this->enabledRunCache == Cms::BOOL_N) {
            $result = parent::run();

            \Yii::$app->cache->set($cacheKey, $result, (int)$this->runCacheDuration, $dependency);
        }

        return $result;
    }
    
}