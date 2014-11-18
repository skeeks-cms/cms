<?php
/**
 * HasMultiLangAndSiteFields
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 18.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors;
use skeeks\cms\base\behaviors\ActiveRecord;
use skeeks\cms\models\Lang;
use skeeks\cms\models\Site;
use yii\base\Event;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class HasPageOptions
 * @package skeeks\cms\models\behaviors
 */
class HasMultiLangAndSiteFields extends ActiveRecord
{
    const DEFAULT_VALUE_SECTION = '_';
    /**
     * @var string
     */
    public $fields = [];


    /**
     * @var null|Site|int|string
     */
    public $currentSite = null;

    /**
     * @var null|Lang|string
     */
    public $currentLang = null;


    public function init()
    {
        parent::init();

        /*if (!$this->currentSite)
        {
            $this->currentSite = \Yii::$app->currentSite;
        }

        if (!$this->currentLang)
        {
            $this->currentSite = \Yii::$app->language;
        }*/
    }

    /*public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_UPDATE      => "beforeUpdateMultifields",
        ];
    }

    public function beforeUpdateMultifields(Event $event)
    {
        foreach ($this->fields as $fieldName)
        {
            print_r($this->owner);die;
            $value = $this->owner->{$fieldName};
            $this->owner->{$fieldName} = [];
            //$this->setMultiFieldValue($fieldName, $value);
        }
    }*/



    /**
     * @param \skeeks\cms\base\db\ActiveRecord $owner
     * @throws \skeeks\cms\Exception
     */
    public function attach($owner)
    {
        $owner->attachBehavior("json_multi_lang_and_site_fields", [
            "class"  => HasJsonFieldsBehavior::className(),
            "fields" => $this->fields
        ]);

        parent::attach($owner);
    }


    /**
     * @param Site|int|string $site
     * @return \skeeks\cms\base\db\ActiveRecord
     */
    public function setCurrentSite($site)
    {
        $this->currentSite = $site;
        return $this->owner;
    }
    /**
     * @param Lang|string $lang
     * @return \skeeks\cms\base\db\ActiveRecord
     */
    public function setCurrentLang($lang)
    {
        $this->currentLang = $lang;
        return $this->owner;
    }


    /**
     * @param $field
     * @param $value
     * @return \skeeks\cms\base\db\ActiveRecord
     */
    public function setMultiFieldValue($field, $value)
    {
        $site = null;
        if ($this->currentSite)
        {
            if ($this->currentSite instanceof Site)
            {
                $site = (string) $this->currentSite->primaryKey;
            } else
            {
                $site = (string) $this->currentSite;
            }
        }


        $lang = null;
        if ($this->currentLang)
        {
            if ($this->currentLang instanceof Lang)
            {
                $lang = (string) $this->currentLang->id;
            } else
            {
                $lang = (string) $this->currentLang;
            }
        }

        $allValues = $this->getMultiFieldValues($field);

        if ($site && $lang)
        {
            $allValues[$site][$lang][self::DEFAULT_VALUE_SECTION] = $value;
        } else if ($site)
        {
            $allValues[$site][self::DEFAULT_VALUE_SECTION] = $value;
        } else if($lang)
        {
            $allValues[$lang][self::DEFAULT_VALUE_SECTION] = $value;
        } else
        {
            $allValues[self::DEFAULT_VALUE_SECTION] = $value;
        }

        $this->owner->{$field} = $allValues;

        return $this->owner;
    }

    /**
     * @param string $field
     * @return mixed
     */
    public function getMultiFieldValue($field)
    {
        $site = null;
        if ($this->currentSite)
        {
            if ($this->currentSite instanceof Site)
            {
                $site = $this->currentSite->primaryKey;
            } else
            {
                $site = $this->currentSite;
            }
        }


        $lang = null;
        if ($this->currentLang)
        {
            if ($this->currentLang instanceof Lang)
            {
                $lang = $this->currentLang->id;
            } else
            {
                $lang = $this->currentLang;
            }
        }

        $allValues = $this->getMultiFieldValues($field);


        if ($site && $lang)
        {
            if (isset($allValues[$site][$lang][self::DEFAULT_VALUE_SECTION]))
            {
                return $allValues[$site][$lang][self::DEFAULT_VALUE_SECTION];

            } else if (isset($allValues[$site][self::DEFAULT_VALUE_SECTION]))
            {
                return $allValues[$site][self::DEFAULT_VALUE_SECTION];

            } else if (isset($allValues[$lang][self::DEFAULT_VALUE_SECTION]))
            {
                return $allValues[$lang][self::DEFAULT_VALUE_SECTION];
            }

        } else if ($site)
        {
            if (isset($allValues[$site][self::DEFAULT_VALUE_SECTION]))
            {
                return $allValues[$site][self::DEFAULT_VALUE_SECTION];
            }

        } else if ($lang)
        {

            if (isset($allValues[$lang][self::DEFAULT_VALUE_SECTION]))
            {
                return $allValues[$lang][self::DEFAULT_VALUE_SECTION];
            }
        }

        return $this->getMultiFieldDefaultValue($field);
    }


    /**
     *
     * Значение поля для сайта
     *
     * @param string $field
     * @param Site|int|string $site
     * @return array|null
     */
    public function getMultiFieldSiteValues($field, $site)
    {
        if ($site instanceof Site)
        {
            $site = $site->primaryKey;
        }

        if (is_string($site) || is_int($site))
        {
            return ArrayHelper::getValue($this->getMultiFieldValues($field), $site);
        }

        return null;
    }

    /**
     *
     * Получить значение для какого то языка
     *
     * @param string $field
     * @param int|string $lang
     * @return array|null
     */
    public function getMultiFieldLangValues($field, $lang)
    {
        if ($lang instanceof Lang)
        {
            $lang = $lang->id;
        }

        if (is_string($lang) || is_int($lang))
        {
            return ArrayHelper::getValue($this->getMultiFieldValues($field), $lang);
        }

        return null;
    }

    /**
     *
     * Значение по умолчанию.
     *
     * @param $field
     * @return mixed
     */
    public function getMultiFieldDefaultValue($field)
    {
        return ArrayHelper::getValue($this->getMultiFieldValues($field), self::DEFAULT_VALUE_SECTION);
    }

    /**
     *
     * Все значения поля.
     *
     * @param $field
     * @return array
     */
    public function getMultiFieldValues($field)
    {
        return (array) $this->owner->{$field};
    }


}