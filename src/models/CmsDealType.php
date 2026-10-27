<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use yii\helpers\ArrayHelper;
/**
 * @property int       $id
 * @property int       $created_by
 * @property int       $updated_by
 * @property int       $created_at
 * @property int       $updated_at
 * @property string    $name
 * @property string    $description
 * @property string    $period
 * @property int       $is_periodic Конечная или периодичная услуга?
 *
 * @property string    $periodAsText
 * @property CmsDeal[] $deals
 * @property bool      $clientAreasCustom the type has its own "что можно открыть клиенту"
 * @property array     $clientAreaLevels  area => level the type allows to open
 */
class CmsDealType extends ActiveRecord
{
    const PERIOD_MONTH = 'month';
    const PERIOD_YEAR = 'year';

    /** @var bool|null */
    private $_clientAreasCustom;

    /** @var array|null */
    private $_clientAreaLevels;

    /** @var bool the client areas came from a form or code and are saved with the type */
    private $_clientAreasPosted = false;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%cms_deal_type}}';
    }
    /**
     * @return string
     */
    public function getPeriodAsText()
    {
        return (string)ArrayHelper::getValue(self::optionsForPeriod(), $this->period);
    }

    static public function optionsForPeriod()
    {
        return [
            self::PERIOD_MONTH => 'Мес',
            self::PERIOD_YEAR  => 'Год',
        ];
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['created_by', 'created_at', 'is_periodic'], 'integer'],
            [['name'], 'required'],
            [['description'], 'string'],
            [['period'], 'string'],
            [['name'], 'string', 'max' => 255],
            [['clientAreasCustom', 'clientAreaLevels'], 'safe'],

            [
                'period',
                function ($attribute) {
                    if ($this->is_periodic && !$this->period) {
                        $this->addError($attribute, 'Нужно заполнять для периодических сделок');
                    }

                    if (!$this->is_periodic && $this->period) {
                        $this->addError($attribute, 'Для разовых сделок заполнять не нужно');
                    }
                },

            ],

            [
                'period',
                'default',
                'value' => function () {
                    if ($this->is_periodic) {
                        return self::PERIOD_MONTH;
                    } else {
                        return null;
                    }
                },
            ],

        ]);
    }
    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'is_periodic' => 'Периодическая сделка?',
            'period'      => 'Период',
            'name'        => 'Название',
            'description' => 'Комментарий',
            'clientAreasCustom' => 'Настроить, что можно открыть клиенту',
        ]);
    }
    /**
     * {@inheritdoc}
     */
    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [
            'is_periodic' => 'Сделки бывают разовые или постоянные',
            'period'      => 'Период действия сделки',
            'clientAreasCustom' => 'Без настройки клиенту можно открыть задачи, отчёты, документы, счета и управление услугой, а возможности дополнительных модулей закрыты. Сделка может только сузить то, что разрешено здесь.',
        ]);
    }

    /**
     * "Сделка как услуга": the type has its own list of areas it may open to clients.
     */
    public function getClientAreasCustom(): bool
    {
        if ($this->_clientAreasCustom === null) {
            $this->_clientAreasCustom = !$this->isNewRecord && static::dealAccess() && static::dealAccess()->typeOpenLevels((int)$this->id) !== null;
        }
        return $this->_clientAreasCustom;
    }

    public function setClientAreasCustom($value)
    {
        $this->_clientAreasCustom = (bool)$value;
        $this->_clientAreasPosted = true;
    }

    /**
     * @return array area => level the type allows (without settings — what is open by default)
     */
    public function getClientAreaLevels(): array
    {
        if ($this->_clientAreaLevels === null) {
            $dealAccess = static::dealAccess();
            $levels = $dealAccess && !$this->isNewRecord ? $dealAccess->typeOpenLevels((int)$this->id) : null;
            if ($levels === null && $dealAccess) {
                $levels = [];
                foreach ($dealAccess->getAreaList() as $area => $config) {
                    $levels[$area] = $config['openByDefault'] ? $config['max'] : 0;
                }
            }
            $this->_clientAreaLevels = (array)$levels;
        }
        return $this->_clientAreaLevels;
    }

    public function setClientAreaLevels($levels)
    {
        $this->_clientAreaLevels = array_map('intval', (array)$levels);
        $this->_clientAreasPosted = true;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($this->_clientAreasPosted && ($dealAccess = static::dealAccess())) {
            $dealAccess->saveTypeOpenLevels((int)$this->id, $this->getClientAreasCustom() ? $this->getClientAreaLevels() : null);
            $this->_clientAreasPosted = false;
        }
    }

    /**
     * The access service when its tables exist (shared code may run before the migration).
     */
    protected static function dealAccess(): ?\skeeks\cms\deal\DealAccess
    {
        return \Yii::$app->has('dealAccess') && \skeeks\cms\models\queries\CmsDealQuery::dealAccessReady() ? \Yii::$app->get('dealAccess') : null;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDeals()
    {
        return $this->hasMany(CmsDeal::class, ['cms_deal_type_id' => 'id']);
    }

    /**
     * @deprecated Use {@see getDeals()}.
     * @return \yii\db\ActiveQuery
     */
    public function getCrmDeals()
    {
        return $this->getDeals();
    }

    /**
     * @return string
     */
    public function asText()
    {
        return $this->name;
    }
}
