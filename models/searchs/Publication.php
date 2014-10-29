<?php

namespace skeeks\cms\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use skeeks\cms\models\Publication as PublicationModel;

/**
 * Publication represents the model behind the search form about `common\models\Publication`.
 */
class Publication extends PublicationModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'created_by', 'updated_by', 'created_at', 'updated_at', 'album_image_id', 'album_file_id', 'game_id', 'count_comment', 'count_subscribe', 'count_vote', 'count_vote_up'], 'integer'],
            [['name', 'description_short', 'description_full', 'meta_title', 'meta_description', 'meta_keywords', 'image', 'image_cover', 'seo_page_name'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = PublicationModel::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'album_image_id' => $this->album_image_id,
            'album_file_id' => $this->album_file_id,
            'game_id' => $this->game_id,
            'count_comment' => $this->count_comment,
            'count_subscribe' => $this->count_subscribe,
            'count_vote' => $this->count_vote,
            'count_vote_up' => $this->count_vote_up,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'description_short', $this->description_short])
            ->andFilterWhere(['like', 'description_full', $this->description_full])
            ->andFilterWhere(['like', 'meta_title', $this->meta_title])
            ->andFilterWhere(['like', 'meta_description', $this->meta_description])
            ->andFilterWhere(['like', 'meta_keywords', $this->meta_keywords])
            ->andFilterWhere(['like', 'image', $this->image])
            ->andFilterWhere(['like', 'image_cover', $this->image_cover])
            ->andFilterWhere(['like', 'seo_page_name', $this->seo_page_name]);

        return $dataProvider;
    }
}
