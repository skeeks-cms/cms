<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 21.05.2015
 */
namespace skeeks\cms\traits;

use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsComponentSettings;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\User;
use skeeks\cms\traits\HasComponentDescriptorTrait;
use yii\base\Model;
use yii\caching\TagDependency;
use yii\console\Application;
use yii\db\Exception;
use yii\helpers\ArrayHelper;

/**
 * @property UrlHelper editUrl
 *
 * Class HasComponentDbSettingsTrait
 * @package skeeks\cms\traits
 */
trait HasComponentDbSettingsTrait
{
    public $namespace = null;

    /**
     * Загрузка настроек по умолчанию
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


        } catch (Exception $e)
        {

        } catch (\Exception $e)
        {
            \Yii::error(\Yii::t('skeeks/cms','{cms} component error load defaul settings',['cms' => 'Cms']).': ' . $e->getMessage());
        }

        return $this;
    }

    public function getCacheKey()
    {
        return implode([
            \Yii::getAlias('@webroot'),
            $this->className(),
            $this->namespace,
            \Yii::$app->currentSite->site->code,
            \Yii::$app->user->id
        ]);
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        if (\Yii::$app instanceof Application)
        {
            return $this->fetchDefaultSettings();
        }


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

                $settingsValues = ArrayHelper::merge($settingsValues,
                    $this->fetchDefaultSettingsBySiteCode($site->code)
                );
            }

            //Настройки для текущего пользователя
            if (!\Yii::$app->user->isGuest)
            {
                $settingsValues = ArrayHelper::merge($settingsValues,
                    $this->fetchDefaultSettingsByUserId(\Yii::$app->user->identity->id)
                );
            }

            \Yii::$app->cache->set($key, $settingsValues, 0, $dependency);
        }

        return $settingsValues;
    }


    /**
     * @param CmsSite $site
     * @return $this
     */
    public function loadSettingsBySite($site)
    {
        $settings = $this->fetchDefaultSettingsBySiteCode($site->code);

        if ($settings)
        {
            $this->attributes = $settings;
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function loadDefaultSettings()
    {
        $settings = $this->fetchDefaultSettings();

        if ($settings)
        {
            $this->attributes = $settings;
            //$this->setAttributes($settings, false);
        }

        return $this;
    }

    /**
     * @param User $site
     * @return $this
     */
    public function loadSettingsByUser($user)
    {
        $settings = $this->fetchDefaultSettingsByUserId($user->id);

        if ($settings)
        {
            $this->attributes = $settings;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function saveDefaultSettings()
    {
        $settings           = CmsComponentSettings::createByComponentDefault($this);
        $settings->value    = $this->attributes;

        $this->invalidateCache();

        return $settings->save();
    }

    /**
     * @return bool
     */
    public function saveDefaultSettingsBySiteCode($site_code)
    {
        $settings           = CmsComponentSettings::createByComponentSiteCode($this, $site_code);
        $settings->value    = $this->attributes;

        $this->invalidateCache();

        return $settings->save();
    }

    /**
     * @return bool
     */
    public function saveDefaultSettingsByUserId($user_id)
    {
        $settings           = CmsComponentSettings::createByComponentUserId($this, $user_id);
        $settings->value    = $this->attributes;

        $this->invalidateCache();

        return $settings->save();
    }


    /**
     * @return $this
     */
    public function invalidateCache()
    {
        TagDependency::invalidate(\Yii::$app->cache, [
            $this->className() . (string) $this->namespace
        ]);

        return $this;
    }


    /**
     *
     * Настройки по умолчанию
     *
     * @return array
     */
    public function fetchDefaultSettings()
    {
        $settings = CmsComponentSettings::fetchByComponentDefault($this);

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
    public function fetchDefaultSettingsBySiteCode($site_code)
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
    public function fetchDefaultSettingsByUserId($user_id)
    {
        $settings = CmsComponentSettings::fetchByComponentUserId($this, (int) $user_id);
        if (!$settings)
        {
            return [];
        }

        return (array) $settings->value;
    }

    /**
     * @return UrlHelper
     */
    public function getEditUrl()
    {
        $attributes = [];

        foreach ($this->defaultAttributes as $key => $value)
        {
            if (!is_object($value))
            {
                $attributes[$key] = $value;
            }
        }

        return UrlHelper::construct('/cms/admin-component-settings/index', [
            'componentClassName'                => $this->className(),
            'attributes'                        => $attributes,
            'componentNamespace'                => $this->namespace,
        ])
            ->enableAdmin()
            ->setSystemParam(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_EMPTY_LAYOUT, 'true');
    }

    /**
     * @return UrlHelper
     */
    public function getCallableEditUrl()
    {
        return UrlHelper::construct('/cms/admin-component-settings/call-edit', [
            'componentClassName'                => $this->className(),
            'componentNamespace'                => $this->namespace,
            'callableId'                        => $this->callableId,
        ])
            ->enableAdmin()
            ->setSystemParam(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_EMPTY_LAYOUT, 'true');
    }


    /**
     * @return array
     */
    public function getCallableData()
    {
        $attributes = [];

        foreach ($this->defaultAttributes as $key => $value)
        {
            if (!is_object($value))
            {
                $attributes[$key] = $value;
            }
        }

        return [
            'attributes'                        => $attributes,
        ];
    }

    /**
     * @return string
     */
    public function getCallableId()
    {
        return $this->settingsId . '-callable';
    }



    /**
     * @var integer a counter used to generate [[id]] for widgets.
     * @internal
     */
    public static $counterSettings = 0;
    /**
     * @var string the prefix to the automatically generated widget IDs.
     * @see getId()
     */
    public static $autoSettingsIdPrefix = 'skeeksSettings';

    private $_settingsId;

    /**
     * Returns the ID of the widget.
     * @param boolean $autoGenerate whether to generate an ID if it is not set previously
     * @return string ID of the widget.
     */
    public function getSettingsId($autoGenerate = true)
    {
        if ($autoGenerate && $this->_settingsId === null) {
            $this->_settingsId = static::$autoSettingsIdPrefix . static::$counterSettings++;
        }

        return $this->_settingsId;
    }

    /**
     * Sets the ID of the widget.
     * @param string $value id of the widget.
     */
    public function setSettingsId($value)
    {
        $this->_settingsId = $value;
    }
}
