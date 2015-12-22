<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 22.12.2015
 */
namespace skeeks\cms\models\search;

use skeeks\cms\models\SourceMessage;
use yii\data\ActiveDataProvider;
use Yii;
use yii\helpers\ArrayHelper;

class SourceMessageSearch extends SourceMessage
{
    const STATUS_TRANSLATED = 1;
    const STATUS_NOT_TRANSLATED = 2;
    public $status;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['category', 'safe'],
            ['message', 'safe'],
            ['status', 'safe']
        ];
    }
    /**
     * @param array|null $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = SourceMessage::find();
        $dataProvider = new ActiveDataProvider(['query' => $query]);
        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }
        if ($this->status == static::STATUS_TRANSLATED) {
            $query->translated();
        }
        if ($this->status == static::STATUS_NOT_TRANSLATED) {
            $query->notTranslated();
        }
        $query
            ->andFilterWhere(['like', 'category', $this->category])
            ->andFilterWhere(['like', 'message', $this->message]);
        return $dataProvider;
    }
    public static function getStatus($id = null)
    {
        $statuses = [
            self::STATUS_TRANSLATED => \Yii::t('app', 'Translated'),
            self::STATUS_NOT_TRANSLATED => \Yii::t('app', 'Not translated'),
        ];
        if ($id !== null) {
            return ArrayHelper::getValue($statuses, $id, null);
        }
        return $statuses;
    }
}