<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.03.2015
 */
namespace skeeks\cms\base;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsComponentSettings;
use skeeks\cms\traits\HasComponentConfigFormTrait;
use skeeks\cms\traits\HasComponentDescriptorTrait;
use yii\base\Model;
use yii\caching\TagDependency;

/**
 * Class Component
 * @package skeeks\cms\base
 */
abstract class Component extends Model
{
    //Можно задавать описание компонента.
    use HasComponentDescriptorTrait;
    //Может строить форму для своих данных.
    use HasComponentConfigFormTrait;

    public $namespace = null;

    public function init()
    {
        \Yii::beginProfile("Init: " . $this->className());
            $this->initSettings();
        \Yii::endProfile("Init: " . $this->className());
    }

    /**
     * Загрузка настроек по умолчанию
     * TODO: добавить кэш
     * TODO: переписать, чтобы настройки могли храниться не только в базе (пока так)
     * @return $this
     */
    public function initSettings()
    {
        try
        {
            $settingsValues = $this->getSettings();

            if ($settingsValues)
            {
                $this->setAttributes($settingsValues);
            }

        } catch (\Exception $e)
        {
            \Yii::error('Cms component error load defaul settings: ' . $e->getMessage());
        }

        return $this;
    }

    public function getCacheKey()
    {
        return implode([
            $this->className(),
            $this->namespace,
            \Yii::$app->currentSite->site->code,
            \Yii::$app->user->getId()
        ]);
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        $key = $this->getCacheKey();

        $dependency = new TagDependency([
            'tags'      =>
            [
                $this->className(),
                $this->className() . (string) $this->namespace
            ],
        ]);

        $settingsValues = \Yii::$app->cache->get($key);
        if ($settingsValues === false) {

            $settingsValues = $this->fetchDefaultSettings();

            //Настройки для текущего сайта
            if ($site = \Yii::$app->currentSite->site)
            {
                $settingsValues = array_merge($settingsValues,
                    $this->fetchDefaultSettingsBySite($site->code)
                );
            }

            //Настройки для текущего пользователя
            if (!\Yii::$app->user->isGuest)
            {
                $settingsValues = array_merge($settingsValues,
                    $this->fetchDefaultSettingsByUser(\Yii::$app->user->identity->getId())
                );
            }

            \Yii::$app->cache->set($key, $settingsValues, 0, $dependency);
        }

        return $settingsValues;
    }


    /**
     * @return bool
     */
    public function saveDefaultSettings()
    {
        $settings           = CmsComponentSettings::createByComponent($this);
        $settings->value    = $this->attributes;

        //\Yii::$app->cache->delete($this->getCacheKey());
        TagDependency::invalidate(\Yii::$app->cache, [
            $this->className() . (string) $this->namespace
        ]);

        return $settings->save();
    }


    /**
     *
     * Настройки по умолчанию
     *
     * @return array
     */
    public function fetchDefaultSettings()
    {
        $settings = CmsComponentSettings::fetchByComponent($this);
        if (!$settings)
        {
            return [];
        }

        return (array) $settings->value;
    }

    /**
     * Настройки для сайта
     * @param (string) $site_code
     * @return array
     */
    public function fetchDefaultSettingsBySite($site_code)
    {
        $settings = CmsComponentSettings::fetchByComponentSiteCode($this, (string) $site_code);
        if (!$settings)
        {
            return [];
        }

        return (array) $settings->value;
    }


    /**
     * Настройки для пользователя
     * @param (int) $site_code
     * @return array
     */
    public function fetchDefaultSettingsByUser($user_id)
    {
        $settings = CmsComponentSettings::fetchByComponentUserId($this, (int) $user_id);
        if (!$settings)
        {
            return [];
        }

        return (array) $settings->value;
    }

    /**
     * @return $this
     */
    public function getEditUrl()
    {
        return UrlHelper::construct('cms/admin-component-settings/index', [
            'componentClassName'    => $this->className(),
            'attributes'            => $this->attributes,
            'namespace'             => $this->namespace,
        ])
        ->enableAdmin()
        ->setSystemParam(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_EMPTY_LAYOUT, 'true');
    }
}