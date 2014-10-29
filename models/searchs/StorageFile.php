<?php

namespace skeeks\cms\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use skeeks\cms\models\StorageFile as StorageFileModel;

/**
 * StorageFile represents the model behind the search form about `common\models\StorageFile`.
 */
class StorageFile extends StorageFileModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['src', 'cluster_id', 'cluster_file', 'type', 'extension', 'original_name', 'name_to_save', 'name', 'description_short', 'description_full', 'meta_title', 'meta_description', 'meta_keywords', 'users_subscribers', 'users_votes_up', 'users_votes_down', 'linked_to_model', 'linked_to_value'], 'safe'],
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'size', 'status', 'image_height', 'image_width', 'count_comment', 'count_subscribe', 'count_vote', 'result_vote'], 'integer'],
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
        $query = StorageFileModel::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'size' => $this->size,
            'status' => $this->status,
            'image_height' => $this->image_height,
            'image_width' => $this->image_width,
            'count_comment' => $this->count_comment,
            'count_subscribe' => $this->count_subscribe,
            'count_vote' => $this->count_vote,
            'result_vote' => $this->result_vote,
        ]);

        $query->andFilterWhere(['like', 'src', $this->src])
            ->andFilterWhere(['like', 'cluster_id', $this->cluster_id])
            ->andFilterWhere(['like', 'cluster_file', $this->cluster_file])
            ->andFilterWhere(['like', 'type', $this->type])
            ->andFilterWhere(['like', 'extension', $this->extension])
            ->andFilterWhere(['like', 'original_name', $this->original_name])
            ->andFilterWhere(['like', 'name_to_save', $this->name_to_save])
            ->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'description_short', $this->description_short])
            ->andFilterWhere(['like', 'description_full', $this->description_full])
            ->andFilterWhere(['like', 'meta_title', $this->meta_title])
            ->andFilterWhere(['like', 'meta_description', $this->meta_description])
            ->andFilterWhere(['like', 'meta_keywords', $this->meta_keywords])
            ->andFilterWhere(['like', 'users_subscribers', $this->users_subscribers])
            ->andFilterWhere(['like', 'users_votes_up', $this->users_votes_up])
            ->andFilterWhere(['like', 'users_votes_down', $this->users_votes_down])
            ->andFilterWhere(['like', 'linked_to_model', $this->linked_to_model])
            ->andFilterWhere(['like', 'linked_to_value', $this->linked_to_value]);

        return $dataProvider;
    }
}
