<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.05.2015
 */

namespace skeeks\cms\models\behaviors;

use yii\base\Behavior;
use yii\caching\Cache;
use yii\caching\TagDependency;
use yii\db\BaseActiveRecord;

/**
 * Class HasTableCache
 * @package skeeks\cms\models\behaviors
 */
class HasTableCache extends Behavior
{
    /**
     * @var Cache
     */
    public $cache;

    /**
     * @return array
     */
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_AFTER_UPDATE => "invalidateTableCache",
            BaseActiveRecord::EVENT_AFTER_INSERT => "invalidateTableCache",
            BaseActiveRecord::EVENT_BEFORE_DELETE => "invalidateTableCache",
        ];
    }

    /**
     * При любом обновлении, сохранении, удалении записей в эту таблицу, инвалидируется тэг кэша таблицы
     * @return $this
     */
    public function invalidateTableCache()
    {
        TagDependency::invalidate($this->cache, [
            $this->getTableCacheTag(),
        ]);

        $owner = $this->owner;

        if (isset($owner->cms_site_id) && $owner->cms_site_id) {
            //\Yii::info("Invalidate: " . $this->getTableCacheTagCmsSite($owner->cms_site_id));
            TagDependency::invalidate($this->cache, [
                $this->getTableCacheTagCmsSite($owner->cms_site_id),
            ]);
        }

        if (isset($owner->site_id) && $owner->site_id) {
            //die('111');
            TagDependency::invalidate($this->cache, [
                $this->getTableCacheTagCmsSite($owner->cms_site_id),
            ]);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getTableCacheTag()
    {
        return 'sx-table-' . $this->owner->tableName();
    }

    /**
     * @return string
     */
    public function getTableCacheTagCmsSite($cms_site_id = null)
    {
        if ($cms_site_id === null) {
            $cms_site_id = \Yii::$app->skeeks->site->id;
        }

        return 'sx-table-' . $this->owner->tableName() . "-" . $cms_site_id;
    }

}