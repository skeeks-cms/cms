<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.09.2015
 */

namespace skeeks\cms\validators;

use yii\validators\Validator;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseException;
use Exception;

/**
 * Phone validator class that validates phone numbers for given
 * country and formats.
 * Country codes and attributes value should be ISO 3166-1 alpha-2 codes
 * @property string $countryAttribute The country code attribute of model
 * @property string $country The country is fixed
 * @property bool $strict If country is not set or selected adds error
 * @property bool $format If phone number is valid formats value with internation phone number
 */
class PhoneValidator extends Validator
{
    public $strict = true;
    public $countryAttribute;
    public $country = 'ru';
    public $format = true;

    public function validateAttribute($model, $attribute)
    {
        // if countryAttribute is set
        if (!isset($country) && isset($this->countryAttribute)) {
            $countryAttribute = $this->countryAttribute;
            $country = $model->$countryAttribute;
        }
        // if country is fixed
        if (!isset($country) && isset($this->country)) {
            $country = $this->country;
        }

        // if none select from our models with best effort
        if (!isset($country) && isset($model->country_code)) {
            $country = $model->country_code;
        }
        if (!isset($country) && isset($model->country)) {
            $country = $model->country;
        }

        // if none and strict
        if (!isset($country) && $this->strict) {
            $this->addError($model, $attribute, \Yii::t('skeeks/cms', 'For phone validation country required'));
            return false;
        }
        if (!isset($country)) {
            return true;
        }
        $phoneUtil = PhoneNumberUtil::getInstance();
        try {
            $numberProto = $phoneUtil->parse($model->$attribute, $country);
            if ($phoneUtil->isValidNumber($numberProto)) {
                if ($this->format == true) {
                    $model->$attribute = $phoneUtil->format($numberProto, PhoneNumberFormat::INTERNATIONAL);
                }
                return true;
            } else {
                $this->addError($model, $attribute,
                    \Yii::t('skeeks/cms', 'Phone number does not seem to be a valid phone number'));
                return false;
            }
        } catch (NumberParseException $e) {
            $this->addError($model, $attribute, \Yii::t('skeeks/cms', 'Unexpected Phone Number Format'));
        } catch (Exception $e) {
            $this->addError($model, $attribute,
                \Yii::t('skeeks/cms', 'Unexpected Phone Number Format or Country Code'));
        }
    }
}