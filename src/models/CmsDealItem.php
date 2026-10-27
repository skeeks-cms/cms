<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\models;

use skeeks\cms\deal\DealAccess;
use yii\helpers\ArrayHelper;

/**
 * A line of the deal composition: what the client gets in the package, with
 * its price and period. A bonus is a line with a zero price. A line may point
 * at a resource of the deal (resource_type is a DealAccess provider type).
 * The deal amount is not recounted from the lines automatically.
 *
 * @property int         $id
 * @property int|null    $created_by
 * @property int|null    $updated_by
 * @property int|null    $created_at
 * @property int|null    $updated_at
 * @property int         $cms_deal_id
 * @property string      $name
 * @property string|null $description
 * @property string      $quantity
 * @property string      $price
 * @property string      $amount
 * @property string|null $period empty — as the deal
 * @property string|null $resource_type
 * @property int|null    $resource_id
 * @property int         $priority
 *
 * @property CmsDeal $cmsDeal
 */
class CmsDealItem extends \skeeks\cms\base\ActiveRecord
{
    /** The form value of "Без ресурса": a select drops the label of an empty key. */
    const RESOURCE_NONE = 'none';

    public static function tableName()
    {
        return '{{%cms_deal_item}}';
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['cms_deal_id', 'name'], 'required'],
            [['cms_deal_id', 'resource_id', 'priority'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['quantity', 'price'], 'number', 'min' => 0],
            [['quantity'], 'default', 'value' => 1],
            [['price'], 'default', 'value' => 0],
            [['priority'], 'default', 'value' => 100],
            [['period'], 'in', 'range' => array_keys(CmsDealType::optionsForPeriod())],
            [['period', 'resource_type', 'resource_id', 'description'], 'default', 'value' => null],
            [['resource_type'], 'string', 'max' => 64],
            [['cms_deal_id'], 'exist', 'targetClass' => CmsDeal::class, 'targetAttribute' => 'id'],
            [['resourceKey'], 'safe'],
            [['resource_id'], 'validateResource', 'skipOnEmpty' => false],
        ]);
    }

    /**
     * The resource as one form value: "<type>:<id>", RESOURCE_NONE — no resource.
     */
    public function getResourceKey(): string
    {
        return $this->resource_type && $this->resource_id ? $this->resource_type.':'.(int)$this->resource_id : self::RESOURCE_NONE;
    }

    public function setResourceKey($value)
    {
        $value = trim((string)$value);
        if ($value === '' || strpos($value, ':') === false) {
            $this->resource_type = null;
            $this->resource_id = null;
            return;
        }
        [$type, $id] = explode(':', $value, 2);
        $this->resource_type = $type;
        $this->resource_id = (int)$id ?: null;
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_deal_id'   => 'Сделка',
            'name'          => 'Что входит',
            'description'   => 'Описание',
            'quantity'      => 'Количество',
            'price'         => 'Цена',
            'amount'        => 'Сумма',
            'period'        => 'Период',
            'resource_type' => 'Вид ресурса',
            'resource_id'   => 'Ресурс',
            'priority'      => 'Сортировка',
            'resourceKey'   => 'Ресурс',
        ]);
    }

    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [
            'price'       => 'Цена 0 — строка видна клиенту как подарок (бонус)',
            'period'      => 'Пусто — как у сделки',
            'resourceKey' => 'Сайт, домен или сертификат этой сделки, к которому относится строка',
        ]);
    }

    public function validateResource($attribute)
    {
        if (!$this->resource_type && !$this->resource_id) {
            return;
        }
        if (!$this->resource_type || !$this->resource_id) {
            $this->addError($attribute, 'Укажите вид ресурса и сам ресурс.');
            return;
        }
        //A line keeps its resource as history after the resource moved to another deal; a new link must fit.
        if (!$this->isNewRecord && !$this->isAttributeChanged('resource_type', false) && !$this->isAttributeChanged('resource_id', false) && !$this->isAttributeChanged('cms_deal_id', false)) {
            return;
        }
        /** @var DealAccess $dealAccess */
        $dealAccess = \Yii::$app->get('dealAccess');
        $resource = $dealAccess->resource((string)$this->resource_type, (int)$this->resource_id);
        if (!$resource || (int)$resource->dealId !== (int)$this->cms_deal_id) {
            $this->addError($attribute, 'Ресурс не относится к этой сделке.');
        }
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        $this->amount = round((float)$this->quantity * (float)$this->price, 4);
        return true;
    }

    public function getIsBonus(): bool
    {
        return (float)$this->price == 0.0;
    }

    public function getCmsDeal()
    {
        return $this->hasOne(CmsDeal::class, ['id' => 'cms_deal_id']);
    }

    public function asText()
    {
        return (string)$this->name;
    }
}
