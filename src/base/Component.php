<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.03.2015
 */

namespace skeeks\cms\base;

use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\IHasConfig;
use skeeks\cms\models\CmsComponentSettings;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsUser;
use skeeks\cms\traits\HasComponentDbSettingsTrait;
use skeeks\cms\traits\HasComponentDescriptorTrait;
use yii\base\Exception;
use yii\base\InvalidParamException;
use yii\base\Model;
use yii\base\ModelEvent;
use yii\caching\TagDependency;
use yii\console\Application;
use yii\db\AfterSaveEvent;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;

/**
 * @deprecated
 *
 * @property array       namespace
 * @property array       settings
 * @property CmsSite     cmsSite
 * @property CmsUser     cmsUser
 *
 * @property array       callAttributes
 * @property string|null override
 * @property array       overridePath
 *
 * Class Component
 * @package skeeks\cms\base
 */
abstract class Component extends Model implements ConfigFormInterface
{
    //Можно задавать описание компонента.
    use HasComponentDescriptorTrait;


    const OVERRIDE_DEFAULT = 'default';
    const OVERRIDE_SITE = 'site';
    const OVERRIDE_USER = 'user';
    /**
     * @event Event an event that is triggered when the record is initialized via [[init()]].
     */
    const EVENT_INIT = 'init';

    /**
     * @event Event an event that is triggered after the record is created and populated with query result.
     */
    const EVENT_AFTER_FIND = 'afterFind';
    /**
     * @event ModelEvent an event that is triggered before inserting a record.
     * You may set [[ModelEvent::isValid]] to be `false` to stop the insertion.
     */
    const EVENT_BEFORE_INSERT = 'beforeInsert';
    /**
     * @event AfterSaveEvent an event that is triggered after a record is inserted.
     */
    const EVENT_AFTER_INSERT = 'afterInsert';
    /**
     * @event ModelEvent an event that is triggered before updating a record.
     * You may set [[ModelEvent::isValid]] to be `false` to stop the update.
     */
    const EVENT_BEFORE_UPDATE = 'beforeUpdate';
    /**
     * @event AfterSaveEvent an event that is triggered after a record is updated.
     */
    const EVENT_AFTER_UPDATE = 'afterUpdate';
    /**
     * @event ModelEvent an event that is triggered before deleting a record.
     * You may set [[ModelEvent::isValid]] to be `false` to stop the deletion.
     */
    const EVENT_BEFORE_DELETE = 'beforeDelete';
    /**
     * @event Event an event that is triggered after a record is deleted.
     */
    const EVENT_AFTER_DELETE = 'afterDelete';
    /**
     * @event Event an event that is triggered after a record is refreshed.
     * @since 2.0.8
     */
    const EVENT_AFTER_REFRESH = 'afterRefresh';
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
    /**
     * Путь переопределения настроек
     * @var array
     */
    protected $_overridePath = ['default', 'site', 'user'];
    /**
     * Текущий путь для которого сохранять настройки
     * @var string|null
     */
    protected $_override = null;
    /**
     * Callable attributes
     * @var array
     */
    protected $_callAttributes = [];
    /**
     * @var array
     */
    protected $_oldAttributes = [];
    /**
     * @var CmsSite|null
     */
    protected $_cmsSite = null;
    /**
     * @var CmsUser|null
     */
    protected $_cmsUser = null;
    /**
     * @var null
     */
    protected $_namespace = null;
    private $_settingsId;
    /**
     * Populates an active record object using a row of data from the database/storage.
     *
     * This is an internal method meant to be called to create active record objects after
     * fetching data from the database. It is mainly used by [[ActiveQuery]] to populate
     * the query results into active records.
     *
     * When calling this method manually you should call [[afterFind()]] on the created
     * record to trigger the [[EVENT_AFTER_FIND|afterFind Event]].
     *
     * @param BaseActiveRecord $record the record to be populated. In most cases this will be an instance
     * created by [[instantiate()]] beforehand.
     * @param array            $row attribute values (name => value)
     */
    public static function populateRecord($record, $row)
    {
        $columns = array_flip($record->attributes());
        foreach ($row as $name => $value) {
            if (isset($columns[$name])) {
                $record->{$name} = $value;
            } elseif ($record->canSetProperty($name)) {
                $record->$name = $value;
            }
        }
        $record->_oldAttributes = $record->toArray();
    }
    /**
     * Creates an active record instance.
     *
     * This method is called together with [[populateRecord()]] by [[ActiveQuery]].
     * It is not meant to be used for creating new records directly.
     *
     * You may override this method if the instance being created
     * depends on the row data to be populated into the record.
     * For example, by creating a record based on the value of a column,
     * you may implement the so-called single-table inheritance mapping.
     * @param array $row row data to be populated into the record.
     * @return static the newly created active record
     */
    public static function instantiate($row)
    {
        return new static;
    }

    public function getConfigFormFields()
    {
        return [];
    }

    public function init()
    {
        $this->_callAttributes = $this->attributes;

        //\Yii::beginProfile("Init: ".static::class);

        if (!\Yii::$app instanceof Application) {
            if ($this->cmsSite === null && isset(\Yii::$app->currentSite) && \Yii::$app->currentSite->site) {
                $this->cmsSite = \Yii::$app->currentSite->site;
            }

            if (isset(\Yii::$app->user) && $this->cmsUser === null && !\Yii::$app->user->isGuest) {
                $this->cmsUser = \Yii::$app->user->identity;
            }
        }

        $this->_initSettings();

        $this->trigger(self::EVENT_INIT);

        //\Yii::endProfile("Init: ".static::class);
    }

    /**
     * Загрузка настроек по умолчанию
     * @return $this
     */
    protected function _initSettings($useCache = true)
    {
        try {

            $this->setAttributes($this->getSettings($useCache));
            $this->_oldAttributes = $this->toArray($this->safeAttributes());
        } catch (\Exception $e) {
            \Yii::error(\Yii::t('skeeks/cms', '{cms} component error load defaul settings',
                    ['cms' => 'Cms']).': '.$e->getMessage());
        }

        return $this;
    }
    /**
     * @return array
     */
    public function attributes()
    {
        $class = new \ReflectionClass($this);
        $names = [];
        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                if (!is_object($property->getValue($this))) {
                    $names[] = $property->getName();
                }
            }
        }

        return $names;
    }
    /**
     * @return bool
     */
    public function refresh()
    {
        $this->_initSettings(false);
        $this->afterRefresh();
        return true;
    }
    /**
     *
     */
    public function afterRefresh()
    {
        $this->trigger(self::EVENT_AFTER_REFRESH);
    }
    /**
     * @return null|string
     */
    public function getNamespace()
    {
        return $this->_namespace;
    }
    /**
     * @param string $namespace
     * @return $this
     */
    public function setNamespace($namespace = null)
    {
        $this->_namespace = $namespace;
        return $this;
    }
    /**
     * @return null|string
     */
    public function getOverride()
    {
        return $this->_override;
    }
    /**
     * @param string $override
     * @return $this
     */
    public function setOverride($override)
    {
        if (!in_array($override, static::getOverrides())) {
            throw new Exception('Invalid override name');
        }

        $this->_override = $override;
        return $this;
    }
    /**
     * @return array
     */
    public static function getOverrides()
    {
        return [
            self::OVERRIDE_DEFAULT,
            self::OVERRIDE_SITE,
            self::OVERRIDE_USER,
        ];
    }
    /**
     * @return array
     */
    public function getOverridePath()
    {
        return (array)$this->_overridePath;
    }
    /**
     * @param array $overridePath
     * @return $this
     */
    public function setOverridePath($overridePath)
    {
        foreach ((array)$overridePath as $override) {
            if (!in_array($override, static::getOverrides())) {
                throw new Exception('Invalid override name');
            }
        }

        $this->_overridePath = (array)$overridePath;
        return $this;
    }














    /**
     * TODO: Revrite and check
     */
    /**
     * @return array
     */
    public function getCallAttributes()
    {
        return $this->_callAttributes;
    }
    /**
     * @return null|CmsSite
     */
    public function getCmsSite()
    {
        return $this->_cmsSite;
    }
    /**
     * @param CmsSite $cmsSite
     * @return $this
     */
    public function setCmsSite(CmsSite $cmsSite = null)
    {
        $this->_cmsSite = $cmsSite;
        return $this;
    }
    /**
     * @return null|CmsUser
     */
    public function getCmsUser()
    {
        return $this->_cmsUser;
    }
    /**
     * @param CmsUser $cmsUser
     * @return $this
     */
    public function setCmsUser($cmsUser = null)
    {
        $this->_cmsUser = $cmsUser;
        return $this;
    }
    public function renderConfigForm(ActiveForm $form)
    {
    }
    /**
     * Получение настроек согласно пути перекрытия настроек.
     * @return array
     */
    public function getSettings($useCache = true)
    {
        $key = $this->getCacheKey();

        $dependency = new TagDependency([
            'tags' =>
                [
                    \Yii::getAlias('@webroot'),
                    static::class,
                    $this->namespace,
                    implode('.', $this->overridePath),
                    $this->cmsUser ? (string)$this->cmsUser->id : '',
                    $this->cmsSite ? (string)$this->cmsSite->id : '',
                ],
        ]);

        $settingsValues = [];

        if ($useCache === true) {
            $settingsValues = \Yii::$app->cache->get($key);

            if ($settingsValues === false) {

                $settingsValues = [];

                if ($this->overridePath) {
                    foreach ($this->overridePath as $overrideName) {
                        $settingsValues = ArrayHelper::merge($settingsValues,
                            $this->fetchOverrideSettings($overrideName)
                        );
                    }
                }

                \Yii::$app->cache->set($key, $settingsValues, 0, $dependency);
            }
        } else {
            if ($this->overridePath) {
                foreach ($this->overridePath as $overrideName) {
                    $settingsValues = ArrayHelper::merge($settingsValues,
                        $this->fetchOverrideSettings($overrideName)
                    );
                }
            }
        }


        return $settingsValues;
    }
    /**
     * @return string
     */
    public function getCacheKey()
    {
        return implode([
            \Yii::getAlias('@webroot'),
            static::class,
            $this->namespace,
            \Yii::$app->language,
            $this->cmsUser ? (string)$this->cmsUser->id : '',
            $this->cmsSite ? (string)$this->cmsSite->id : '',
        ]);
    }
    /**
     * @param $overrideName
     * @return array
     */
    public function fetchOverrideSettings($overrideName)
    {
        $settingsModel = $this->_getOverrideSettingsModel($overrideName);

        if ($settingsModel) {
            return (array)$settingsModel->value;
        }

        return [];
    }
    /**
     * @param $overrideName
     * @return null|CmsComponentSettings
     */
    protected function _getOverrideSettingsModel($overrideName)
    {
        $settingsModel = null;

        if ($overrideName == self::OVERRIDE_DEFAULT) {
            $settingsModel = CmsComponentSettings::findByComponentDefault($this)->one();

        } else {
            if ($overrideName == self::OVERRIDE_SITE) {
                if ($this->cmsSite) {
                    $settingsModel = CmsComponentSettings::findByComponentSite($this, $this->cmsSite)->one();
                }
            } else {
                if ($overrideName == self::OVERRIDE_USER) {
                    if ($this->cmsUser) {
                        $settingsModel = CmsComponentSettings::findByComponentUser($this, $this->cmsUser)->one();
                    }
                }
            }
        }

        return $settingsModel;
    }
    /**
     * @param array $data
     * @param null  $formName
     * @return bool
     */
    public function load($data, $formName = null)
    {
        $result = parent::load($data, $formName);

        if ($models = $this->getConfigFormModels()) {
            foreach ($models as $model) {
                if ($model->load($data, $formName) === false) {
                    $result = false;
                }
            }
        }

        return $result;
    }
    public function getConfigFormModels()
    {
        return [];
    }
    /**
     * @param bool $runValidation
     * @param null $attributeNames
     * @return bool
     * @throws Exception
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        if (!$this->override) {
            throw new Exception('Need set current override');
        }

        if ($runValidation && !$this->validate($attributeNames)) {
            return false;
        }

        $modelSettings = $this->_getOverrideSettingsModel($this->override);
        if (!$modelSettings) {
            $modelSettings = $this->_createOverrideSettingsModel($this->override);
        }

        $this->trigger(self::EVENT_BEFORE_UPDATE, new ModelEvent());


        $value = $this->attributes;

        if ($models = $this->getConfigFormModels()) {
            foreach ($models as $key => $model) {
                $value[$key."Array"] = $model->attributes;
            }
        }

        if ($value && $attributeNames) {
            foreach ($value as $key => $val)
            {
                if (!in_array($key, $attributeNames)) {
                    unset($value[$key]);
                }
            }

            $modelSettings->value = ArrayHelper::merge((array) $modelSettings->value, (array) $value);

        } else {
            $modelSettings->value = (array) $value;
        }



        $result = $modelSettings->save();

        $this->trigger(self::EVENT_AFTER_UPDATE, new AfterSaveEvent([
            'changedAttributes' => $this->getDirtyAttributes(),
        ]));

        $this->invalidateCache();

        return $result;
    }
    /**
     * @param null $attributeNames
     * @param bool $clearErrors
     * @return bool
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        $result = parent::validate($attributeNames, $clearErrors);

        if ($models = $this->getConfigFormModels()) {
            foreach ($models as $model) {
                if ($model->validate($attributeNames, $clearErrors) === false) {
                    $result = false;
                }
            }
        }

        return $result;
    }
    /**
     * @param string $overrideName
     * @return CmsComponentSettings
     * @throws Exception
     */
    protected function _createOverrideSettingsModel($overrideName)
    {
        $overrideName = (string)$overrideName;

        $settingsModel = new CmsComponentSettings([
            'component' => static::class,
        ]);

        if ($this->namespace) {
            $settingsModel->namespace = $this->namespace;
        }

        if ($overrideName == self::OVERRIDE_DEFAULT) {
        } else {
            if ($overrideName == self::OVERRIDE_SITE) {
                if (!$this->cmsSite) {
                    throw new Exception('Need set site');
                }

                $settingsModel->cms_site_id = $this->cmsSite->id;

            } else {
                if ($overrideName == self::OVERRIDE_USER) {
                    if (!$this->cmsUser) {
                        throw new Exception('Need set user');
                    }

                    $settingsModel->user_id = $this->cmsUser->id;
                }
            }
        }

        return $settingsModel;
    }

    /**
     * Returns the attribute values that have been modified since they are loaded or saved most recently.
     *
     * The comparison of new and old values is made for identical values using `===`.
     *
     * @param string[]|null $names the names of the attributes whose values may be returned if they are
     * changed recently. If null, [[attributes()]] will be used.
     * @return array the changed attribute values (name-value pairs)
     */
    public function getDirtyAttributes($names = null)
    {
        if ($names === null) {
            $names = $this->attributes();
        }
        $names = array_flip($names);
        $attributes = [];
        if ($this->_oldAttributes === null) {
            foreach ($this->toArray() as $name => $value) {
                if (isset($names[$name])) {
                    $attributes[$name] = $value;
                }
            }
        } else {
            foreach ($this->toArray() as $name => $value) {
                if (isset($names[$name]) && (!array_key_exists($name,
                            $this->_oldAttributes) || $value !== $this->_oldAttributes[$name])
                ) {
                    $attributes[$name] = $value;
                }
            }
        }
        return $attributes;
    }

    /**
     * @return $this
     */
    public function invalidateCache()
    {
        TagDependency::invalidate(\Yii::$app->cache, [
            static::class,
        ]);

        return $this;
    }

    /**
     * @return UrlHelper
     */
    public function getEditUrl()
    {
        $attributes = [];

        foreach ($this->callAttributes as $key => $value) {
            if (!is_object($value)) {
                $attributes[$key] = $value;
            }
        }

        return \skeeks\cms\backend\helpers\BackendUrlHelper::createByParams(['/cms/admin-component-settings/index'])
            ->merge([
                'componentClassName' => $this->className(),
                'attributes'         => $attributes,
                'componentNamespace' => $this->namespace,
            ])
            ->enableEmptyLayout()
            ->url;
    }

    /**
     * @return string
     */
    public function getCallableEditUrl()
    {
        return \skeeks\cms\backend\helpers\BackendUrlHelper::createByParams(['/cms/admin-component-settings/call-edit'])
            ->merge([
                'componentClassName' => $this->className(),
                'componentNamespace' => $this->namespace,
                'callableId'         => $this->callableId,
            ])
            ->enableEmptyLayout()
            ->url;
    }

    /**
     * @return array
     */
    public function getCallableData()
    {
        $attributes = [];

        $attributes = ArrayHelper::toArray($this->callAttributes);

        /*foreach ($this->callAttributes as $key => $value) {
            if (!is_object($value)) {
                $attributes[$key] = $value;
            }
        }*/

        return [
            'attributes' => $attributes,
        ];
    }

    /**
     * @return string
     */
    public function getCallableId()
    {
        return $this->settingsId.'-callable';
    }

    /**
     * Returns the ID of the widget.
     * @param boolean $autoGenerate whether to generate an ID if it is not set previously
     * @return string ID of the widget.
     */
    public function getSettingsId($autoGenerate = true)
    {
        if ($autoGenerate && $this->_settingsId === null) {
            $this->_settingsId = static::$autoSettingsIdPrefix.static::$counterSettings++;
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

    /**
     * @inheritdoc
     */
    public function canGetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        if (parent::canGetProperty($name, $checkVars, $checkBehaviors)) {
            return true;
        }

        try {
            return $this->hasAttribute($name);
        } catch (\Exception $e) {
            // `hasAttribute()` may fail on base/abstract classes in case automatic attribute list fetching used
            return false;
        }
    }

    /**
     * Returns a value indicating whether the model has an attribute with the specified name.
     * @param string $name the name of the attribute
     * @return bool whether the model has an attribute with the specified name.
     */
    public function hasAttribute($name)
    {
        return isset($this->{$name});
    }

    /**
     * @inheritdoc
     */
    public function canSetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        if (parent::canSetProperty($name, $checkVars, $checkBehaviors)) {
            return true;
        }

        try {
            return $this->hasAttribute($name);
        } catch (\Exception $e) {
            // `hasAttribute()` may fail on base/abstract classes in case automatic attribute list fetching used
            return false;
        }
    }

    /**
     * Returns the named attribute value.
     * If this record is the result of a query and the attribute is not loaded,
     * `null` will be returned.
     * @param string $name the attribute name
     * @return mixed the attribute value. `null` if the attribute is not set or does not exist.
     * @see hasAttribute()
     */
    public function getAttribute($name)
    {
        return isset($this->{$name}) ? $this->{$name} : null;
    }

    /**
     * Sets the named attribute value.
     * @param string $name the attribute name
     * @param mixed  $value the attribute value.
     * @throws InvalidParamException if the named attribute does not exist.
     * @see hasAttribute()
     */
    public function setAttribute($name, $value)
    {
        if (isset($this->{$name})) {
            $this->{$name} = $value;
        } else {
            throw new InvalidParamException(get_class($this).' has no attribute named "'.$name.'".');
        }
    }

    /**
     * Returns the old attribute values.
     * @return array the old attribute values (name-value pairs)
     */
    public function getOldAttributes()
    {
        return $this->_oldAttributes === null ? [] : $this->_oldAttributes;
    }

    /**
     * Sets the old attribute values.
     * All existing old attribute values will be discarded.
     * @param array|null $values old attribute values to be set.
     * If set to `null` this record is considered to be [[isNewRecord|new]].
     */
    public function setOldAttributes($values)
    {
        $this->_oldAttributes = $values;
    }

    /**
     * Returns the old value of the named attribute.
     * If this record is the result of a query and the attribute is not loaded,
     * `null` will be returned.
     * @param string $name the attribute name
     * @return mixed the old attribute value. `null` if the attribute is not loaded before
     * or does not exist.
     * @see hasAttribute()
     */
    public function getOldAttribute($name)
    {
        return isset($this->_oldAttributes[$name]) ? $this->_oldAttributes[$name] : null;
    }

    /**
     * Sets the old value of the named attribute.
     * @param string $name the attribute name
     * @param mixed  $value the old attribute value.
     * @throws InvalidParamException if the named attribute does not exist.
     * @see hasAttribute()
     */
    public function setOldAttribute($name, $value)
    {
        if (isset($this->_oldAttributes[$name]) || $this->hasAttribute($name)) {
            $this->_oldAttributes[$name] = $value;
        } else {
            throw new InvalidParamException(get_class($this).' has no attribute named "'.$name.'".');
        }
    }

    /**
     * Marks an attribute dirty.
     * This method may be called to force updating a record when calling [[update()]],
     * even if there is no change being made to the record.
     * @param string $name the attribute name
     */
    public function markAttributeDirty($name)
    {
        unset($this->_oldAttributes[$name]);
    }

    /**
     * Returns a value indicating whether the named attribute has been changed.
     * @param string $name the name of the attribute.
     * @param bool   $identical whether the comparison of new and old value is made for
     * identical values using `===`, defaults to `true`. Otherwise `==` is used for comparison.
     * This parameter is available since version 2.0.4.
     * @return bool whether the attribute has been changed
     */
    public function isAttributeChanged($name, $identical = true)
    {
        if (isset($this->{$name}, $this->_oldAttributes[$name])) {
            if ($identical) {
                return $this->{$name} !== $this->_oldAttributes[$name];
            } else {
                return $this->{$name} != $this->_oldAttributes[$name];
            }
        } else {
            return isset($this->{$name}) || isset($this->_oldAttributes[$name]);
        }
    }

    /**
     * Deletes the table row corresponding to this active record.
     *
     * This method performs the following steps in order:
     *
     * 1. call [[beforeDelete()]]. If the method returns `false`, it will skip the
     *    rest of the steps;
     * 2. delete the record from the database;
     * 3. call [[afterDelete()]].
     *
     * In the above step 1 and 3, events named [[EVENT_BEFORE_DELETE]] and [[EVENT_AFTER_DELETE]]
     * will be raised by the corresponding methods.
     *
     * @return int|false the number of rows deleted, or `false` if the deletion is unsuccessful for some reason.
     * Note that it is possible the number of rows deleted is 0, even though the deletion execution is successful.
     * @throws StaleObjectException if [[optimisticLock|optimistic locking]] is enabled and the data
     * being deleted is outdated.
     * @throws Exception in case delete failed.
     */
    public function delete()
    {
        if (!$this->override) {
            throw new Exception('Set current override');
        }

        $result = false;
        if ($this->beforeDelete()) {
            // we do not check the return value of deleteAll() because it's possible
            // the record is already deleted in the database and thus the method will return 0
            if (!$model = $this->_getOverrideSettingsModel($this->override)) {
                return false;
            }

            $result = $model->delete();
            $this->_oldAttributes = null;
            $this->afterDelete();
        }

        $this->invalidateCache();

        return $result;
    }

    /**
     * This method is invoked before deleting a record.
     * The default implementation raises the [[EVENT_BEFORE_DELETE]] event.
     * When overriding this method, make sure you call the parent implementation like the following:
     *
     * ```php
     * public function beforeDelete()
     * {
     *     if (parent::beforeDelete()) {
     *         // ...custom code here...
     *         return true;
     *     } else {
     *         return false;
     *     }
     * }
     * ```
     *
     * @return bool whether the record should be deleted. Defaults to `true`.
     */
    public function beforeDelete()
    {
        $event = new ModelEvent;
        $this->trigger(self::EVENT_BEFORE_DELETE, $event);

        return $event->isValid;
    }

    /**
     * This method is invoked after deleting a record.
     * The default implementation raises the [[EVENT_AFTER_DELETE]] event.
     * You may override this method to do postprocessing after the record is deleted.
     * Make sure you call the parent implementation so that the event is raised properly.
     */
    public function afterDelete()
    {
        $this->trigger(self::EVENT_AFTER_DELETE);
    }

    /**
     * This method is called at the beginning of inserting or updating a record.
     * The default implementation will trigger an [[EVENT_BEFORE_INSERT]] event when `$insert` is `true`,
     * or an [[EVENT_BEFORE_UPDATE]] event if `$insert` is `false`.
     * When overriding this method, make sure you call the parent implementation like the following:
     *
     * ```php
     * public function beforeSave($insert)
     * {
     *     if (parent::beforeSave($insert)) {
     *         // ...custom code here...
     *         return true;
     *     } else {
     *         return false;
     *     }
     * }
     * ```
     *
     * @param bool $insert whether this method called while inserting a record.
     * If `false`, it means the method is called while updating a record.
     * @return bool whether the insertion or updating should continue.
     * If `false`, the insertion or updating will be cancelled.
     */
    public function beforeSave($insert)
    {
        $event = new ModelEvent;
        $this->trigger($insert ? self::EVENT_BEFORE_INSERT : self::EVENT_BEFORE_UPDATE, $event);

        return $event->isValid;
    }

    /**
     * This method is called at the end of inserting or updating a record.
     * The default implementation will trigger an [[EVENT_AFTER_INSERT]] event when `$insert` is `true`,
     * or an [[EVENT_AFTER_UPDATE]] event if `$insert` is `false`. The event class used is [[AfterSaveEvent]].
     * When overriding this method, make sure you call the parent implementation so that
     * the event is triggered.
     * @param bool  $insert whether this method called while inserting a record.
     * If `false`, it means the method is called while updating a record.
     * @param array $changedAttributes The old values of attributes that had changed and were saved.
     * You can use this parameter to take action based on the changes made for example send an email
     * when the password had changed or implement audit trail that tracks all the changes.
     * `$changedAttributes` gives you the old attribute values while the active record (`$this`) has
     * already the new, updated values.
     *
     * Note that no automatic type conversion performed by default. You may use
     * [[\yii\behaviors\AttributeTypecastBehavior]] to facilitate attribute typecasting.
     * See http://www.yiiframework.com/doc-2.0/guide-db-active-record.html#attributes-typecasting.
     */
    public function afterSave($insert, $changedAttributes)
    {
        $this->trigger($insert ? self::EVENT_AFTER_INSERT : self::EVENT_AFTER_UPDATE, new AfterSaveEvent([
            'changedAttributes' => $changedAttributes,
        ]));
    }

    /**
     * Returns whether there is an element at the specified offset.
     * This method is required by the interface [[\ArrayAccess]].
     * @param mixed $offset the offset to check on
     * @return bool whether there is an element at the specified offset.
     */
    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    /**
     * Returns the text label for the specified attribute.
     * If the attribute looks like `relatedModel.attribute`, then the attribute will be received from the related model.
     * @param string $attribute the attribute name
     * @return string the attribute label
     * @see generateAttributeLabel()
     * @see attributeLabels()
     */
    public function getAttributeLabel($attribute)
    {
        $labels = $this->attributeLabels();
        if (isset($labels[$attribute])) {
            return $labels[$attribute];
        }

        return $this->generateAttributeLabel($attribute);
    }

    /**
     * Returns the text hint for the specified attribute.
     * If the attribute looks like `relatedModel.attribute`, then the attribute will be received from the related model.
     * @param string $attribute the attribute name
     * @return string the attribute hint
     * @see attributeHints()
     * @since 2.0.4
     */
    public function getAttributeHint($attribute)
    {
        $hints = $this->attributeHints();
        if (isset($hints[$attribute])) {
            return $hints[$attribute];
        }
        return '';
    }


    /**
     * Sets the element value at the specified offset to null.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * It is implicitly called when you use something like `unset($model[$offset])`.
     * @param mixed $offset the offset to unset element
     */
    public function offsetUnset($offset)
    {
        if (property_exists($this, $offset)) {
            $this->$offset = null;
        } else {
            unset($this->$offset);
        }
    }
}