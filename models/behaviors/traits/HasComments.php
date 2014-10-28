<?php
/**
 * Полезные методы для работы с комментариями найти все, добавить
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 20.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors\traits;

use skeeks\cms\models\Comment;

/**
 * Class HasComments
 * @package skeeks\cms\models\behaviors\traits
 */
trait HasComments
{
    /**
     * @param $content
     * @return bool|Comment
     * @throws \skeeks\sx\Exception
     */
    public function addComment($content)
    {
        $comment = new Comment(array_merge(
            $this->getRef()->toArray(),
            [
                "content"           => $content,
            ]
        ));
        $comment = $comment->save(false);
        return $comment;
    }

    /**
     * @return $this
     */
    public function calculateCountComments()
    {
        $this->setAttribute("count_comment", count($this->findComments()->all()));
        $this->save();
        return $this;
    }

    /**
     * @return \yii\db\ActiveQuery
     * @throws \skeeks\sx\Exception
     */
    public function findComments()
    {
        return Comment::find()->where($this->getRef()->toArray());
    }
}