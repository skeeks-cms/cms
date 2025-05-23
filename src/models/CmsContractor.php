<?php

namespace skeeks\cms\models;

use http\Exception\InvalidArgumentException;
use skeeks\cms\base\ActiveRecord;
use skeeks\cms\helpers\StringHelper;
use skeeks\cms\models\behaviors\HasStorageFile;
use skeeks\cms\models\queries\CmsContractorQuery;
use skeeks\cms\shop\models\ShopBill;
use skeeks\cms\validators\PhoneValidator;
use skeeks\yii2\dadataClient\models\PartyModel;
use yii\helpers\ArrayHelper;
use yii\validators\EmailValidator;
/**
 * This is the model class for table "cms_contractor".
 *
 * @property int                 $id
 * @property int|null            $created_by
 * @property int|null            $updated_by
 * @property int|null            $created_at
 * @property int|null            $updated_at
 * @property int                 $cms_site_id
 * @property string              $contractor_type Тип контрагента (Ип, Юр, Физ лицо)
 * @property string|null         $name Название
 * @property string|null         $full_name Полное название
 * @property string|null         $international_name Интернациональное название
 * @property string|null         $first_name
 * @property string|null         $last_name
 * @property string|null         $patronymic
 * @property string              $inn ИНН
 * @property string|null         $ogrn ОГРН
 * @property string|null         $kpp КПП
 * @property string|null         $okpo ОКПО
 * @property string|null         $address Адрес организации
 * @property string|null         $mailing_address Почтовый адрес (для отправки писем)
 * @property string|null         $mailing_postcode Почтовый индекс
 * @property int|null            $cms_image_id Фото
 * @property int|null            $stamp_id Печать
 * @property int|null            $director_signature_id Подпись директора
 * @property int|null            $signature_accountant_id Подпись гл. бухгалтера
 * @property int                 $is_our Это наш контрагент?
 * @property string              $description Описание
 * @property string              $phone Телефон
 * @property string              $email Email
 *
 * @property string              $asShortText
 *
 * @property CmsStorageFile      $cmsImage
 * @property CmsSite             $cmsSite
 * @property CmsStorageFile      $directorSignature
 * @property CmsStorageFile      $signatureAccountant
 * @property CmsStorageFile      $stamp
 * @property CmsCompany[]        $companies
 * @property CmsUser[]           $users
 * @property CmsContractorBank[] $banks
 *
 * @property ShopBill[]          $receiverBills
 * @property ShopBill[]          $senderBills
 */
class CmsContractor extends ActiveRecord
{

    const TYPE_LEGAL = 'legal';

    const TYPE_INDIVIDUAL = 'individual';
    const TYPE_SELFEMPLOYED = 'selfemployed';
    const TYPE_HUMAN = 'human';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cms_contractor';
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            HasStorageFile::class => [
                'class'  => HasStorageFile::class,
                'fields' => [
                    'cms_image_id',
                    'stamp_id',
                    'director_signature_id',
                    'signature_accountant_id',
                ],
            ],
            /*HasJsonFieldsBehavior::class => [
                'class'  => HasJsonFieldsBehavior::class,
                'fields' => [
                    'dadata',
                ],
            ],*/
        ]);
    }


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'cms_site_id', 'is_our'], 'integer'],
            [['contractor_type'], 'required'],
            [
                ['description', 'contractor_type', 'name', 'full_name', 'international_name', 'first_name', 'last_name', 'patronymic', 'inn', 'ogrn', 'kpp', 'okpo', 'address', 'mailing_address', 'mailing_postcode'],
                'string',
                'max' => 255,
            ],


            [['cms_image_id'], 'safe'],
            [['stamp_id'], 'safe'],
            [['director_signature_id'], 'safe'],
            [['signature_accountant_id'], 'safe'],

            /*[
                [
                    'inn',
                    'ogrn',
                    'kpp',
                    'okpo',
                ],
                "filter",
                'filter' => 'trim',
            ],*/

            [
                'cms_site_id',
                'default',
                'value' => function () {
                    if (\Yii::$app->skeeks->site) {
                        return \Yii::$app->skeeks->site->id;
                    }
                },
            ],

            [
                [
                    'name',
                    'international_name',
                    'full_name',
                    'first_name',
                    'last_name',
                    'patronymic',
                    'description',
                    'inn',
                ],
                'default',
                'value' => null,
            ],
            
            [['cms_site_id', 'inn'], 'unique', 'when' => function() {
                return $this->inn;
            }, 'targetAttribute' => ['cms_site_id', 'inn'], 'message' => 'Этот ИНН уже используется'],
            
            [['inn'], 'unique', 'when' => function() {
                return $this->inn;
            }, 'targetAttribute' => ['inn'], 'message' => 'Этот ИНН уже используется'],

            


            [
                ['inn'],
                'required',
                'when' => function () {
                    return (bool)(in_array($this->contractor_type, [self::TYPE_INDIVIDUAL, self::TYPE_SELFEMPLOYED, self::TYPE_LEGAL]));
                },
            ],

            [
                ['name'],
                'required',
                'when' => function () {
                    return (bool)(in_array($this->contractor_type, [self::TYPE_LEGAL]));
                },
            ],

            [
                ['first_name'],
                'required',
                'when' => function () {
                    return (bool)(!in_array($this->contractor_type, [self::TYPE_LEGAL]));
                },
            ],

            [
                ['name'],
                function () {
                    if (!in_array($this->contractor_type, [self::TYPE_LEGAL])) {
                        $this->name = null;
                    }
                },
            ],

            [
                ['first_name', 'last_name', 'patronymic'],
                function () {
                    if (in_array($this->contractor_type, [self::TYPE_LEGAL])) {
                        $this->first_name = null;
                        $this->last_name = null;
                        $this->patronymic = null;
                    }
                },
            ],


            [['phone'], 'string', 'max' => 64],
            [['phone'], PhoneValidator::class],
            //[['phone'], "filter", 'filter' => 'trim'],
            [
                ['phone'],
                "filter",
                'filter' => function ($value) {
                    return StringHelper::strtolower($value);
                },
            ],

            [['email'], 'string', 'max' => 64],
            [['email'], EmailValidator::class],
            //[['email'], "filter", 'filter' => 'trim'],

            [
                ['email'],
                "filter",
                'filter' => function ($value) {
                    return StringHelper::strtolower($value);
                },
            ],

        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'cms_site_id'             => 'Cms Site ID',
            'contractor_type'         => 'Тип контрагента',
            'name'                    => 'Название',
            'full_name'               => 'Полное название',
            'international_name'      => 'Интернациональное название',
            'first_name'              => 'Имя',
            'last_name'               => 'Фамилия',
            'patronymic'              => 'Отчество',
            'inn'                     => 'ИНН',
            'ogrn'                    => 'ОГРН',
            'kpp'                     => 'КПП',
            'okpo'                    => 'ОКПО',
            'address'                 => 'Адрес',
            'mailing_address'         => 'Почтовый адрес',
            'mailing_postcode'        => 'Индекс',
            'cms_image_id'            => 'Фото или логотип',
            'stamp_id'                => 'Печать',
            'director_signature_id'   => 'Подпись директора',
            'signature_accountant_id' => 'Подпись бухгалтера',
            'is_our'                  => 'Контрагент нашей компании',
            'phone'                   => 'Телефон',
            'email'                   => 'Email',
            'description'             => 'Описание',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeHints()
    {
        return array_merge(parent::attributeLabels(), [

            'stamp_id'                => 'Используется при формировании счетов',
            'director_signature_id'   => 'Используется при формировании счетов',
            'signature_accountant_id' => 'Используется при формировании счетов',
        ]);
    }


    /**
     * @return array
     */
    static public function optionsForType()
    {
        return [
            self::TYPE_LEGAL        => 'Компания',
            self::TYPE_INDIVIDUAL   => 'ИП',
            self::TYPE_SELFEMPLOYED => 'Самозанятый',
            self::TYPE_HUMAN        => 'Физическое лицо',
            //self::TYPE_INDIVIDUAL => 'Физическое лицо',
        ];
    }

    public function asText()
    {
        //$parent = parent::asText();

        $parent = '';

        if (in_array($this->contractor_type, [
            self::TYPE_INDIVIDUAL,
            self::TYPE_SELFEMPLOYED,
            self::TYPE_HUMAN,
        ])) {
            $parent .= $this->typeAsText." ";
        }

        if (!in_array($this->contractor_type, [self::TYPE_LEGAL])) {
            $parent .= implode(" ", [
                $this->last_name,
                $this->first_name,
            ]);
        } else {
            $parent .= $this->name;
        }

        if ($this->international_name) {
            $parent .= " / ".$this->international_name;
        }

        //return "#" . $this->id . " " . $parent;
        return $parent;
    }

    /**
     * @return string
     */
    public function getTypeAsText()
    {
        return (string)ArrayHelper::getValue(self::optionsForType(), $this->contractor_type);
    }

    /**
     * Gets query for [[CmsImage]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsImage()
    {
        return $this->hasOne(CmsStorageFile::className(), ['id' => 'cms_image_id']);
    }

    /**
     * Gets query for [[CmsSite]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSite()
    {
        return $this->hasOne(CmsSite::className(), ['id' => 'cms_site_id']);
    }


    /**
     * Gets query for [[DirectorSignature]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDirectorSignature()
    {
        return $this->hasOne(CmsStorageFile::className(), ['id' => 'director_signature_id']);
    }

    /**
     * Gets query for [[SignatureAccountant]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSignatureAccountant()
    {
        return $this->hasOne(CmsStorageFile::className(), ['id' => 'signature_accountant_id']);
    }

    /**
     * Gets query for [[Stamp]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStamp()
    {
        return $this->hasOne(CmsStorageFile::className(), ['id' => 'stamp_id']);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContractorMap()
    {
        return $this->hasMany(CmsContractorMap::class, ['cms_contractor_id' => 'id'])
            ->from(['cmsContractorMap' => CmsContractorMap::tableName()]);
    }

    /**
     * @return \yii\db\ActiveQuery|CrmContractorQuery
     */
    /*public function getCmsUsers()
    {
        return $this->hasMany(CrmContractor::class, ['id' => 'crm_child_contractor_id'])->via('crmContractorMapCompanies');
    }*/


    /**
     * {@inheritdoc}
     * @return CmsContractorQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new CmsContractorQuery(get_called_class());
    }


    /**
     * @param PartyModel $party
     * @return $this
     */
    public function setAttributesFromDadata(PartyModel $party)
    {
        $this->name = $party->unrestricted_value;
        $this->full_name = $party->unrestricted_value;

        $this->kpp = $party->kpp;
        $this->ogrn = $party->ogrn;
        $this->okpo = $party->getDataValue("okpo");
        $this->address = (string)$party->address;
        $this->mailing_address = (string)$party->address;
        $this->mailing_postcode = $party->getDataValue("address.data.postal_code");
        $this->inn = $party->inn;
        //$this->dadata = $party->toArray();

        if ($party->type == "LEGAL") {
            $this->contractor_type = self::TYPE_LEGAL;
        } elseif ($party->type == "INDIVIDUAL") {
            $this->contractor_type = self::TYPE_INDIVIDUAL;

            [$this->last_name, $this->first_name, $this->patronymic] = explode(" ", $party->name->full);

        } else {
            throw new InvalidArgumentException("Тип {$party->type} не предусмотрен");
        }

        return $this;
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsCompany2contractors()
    {
        return $this->hasMany(CmsCompany2contractor::class, ['cms_contractor_id' => 'id'])
            ->from(['cmsCompany2contractors' => CmsCompany2contractor::tableName()]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanies()
    {
        return $this->hasMany(CmsCompany::class, ['id' => 'cms_company_id'])
            ->via('cmsCompany2contractors');;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBanks()
    {
        return $this->hasMany(CmsContractorBank::class, ['cms_contractor_id' => 'id'])->orderBy(['sort' => SORT_ASC]);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(CmsUser::class, ['id' => 'cms_user_id'])
            ->via('cmsContractorMap');;
    }

    /**
     * @return null|string
     */
    public function getAsShortText()
    {
        $parent = "";


        if (!in_array($this->contractor_type, [self::TYPE_LEGAL])) {
            $parent = implode(" ", [
                $this->last_name,
                $this->first_name,
                $this->patronymic,
            ]);

            if ($this->contractor_type == self::TYPE_INDIVIDUAL) {
                $parent = "ИП ".$parent;
            }
            if ($this->contractor_type == self::TYPE_SELFEMPLOYED) {
                $parent = "Самозанятый ".$parent;
            }

        } else {
            $parent = $this->name;
        }

        return $parent;
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSenderBills()
    {
        return $this->hasMany(ShopBill::class, ['sender_contractor_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReceiverBills()
    {
        return $this->hasMany(ShopBill::class, ['receiver_contractor_id' => 'id']);
    }
}