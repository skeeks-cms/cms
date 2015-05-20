<?php
/**
 * HasMultiLangAndSiteFieldsTrait
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 18.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors\traits;
use skeeks\cms\base\db\ActiveRecord;

/**
 *
 * @method ActiveRecord     setCurrentSite($site)
 * @method ActiveRecord     setCurrentLang($lang)
 * @method ActiveRecord     setMultiFieldValue($field, $value)
 * @method array|null       getMultiFieldSiteValues($field, $site)
 * @method array|null       getMultiFieldLangValues($field, $lang)
 * @method mixed            getMultiFieldDefaultValue($field)
 * @method array            getMultiFieldValues($field)
 * @method mixed            getMultiFieldValue($field)
 *
 * Class HasMultiLangAndSiteFieldsTrait
 * @package skeeks\cms\models\behaviors\traits
 */
trait HasMultiLangAndSiteFieldsTrait
{}