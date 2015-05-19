<?php
/**
 * HasSubscribes
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 21.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors\traits;
use \skeeks\cms\models\Vote;
use yii\db\ActiveQuery;

/**
 * Class HasVotes
 *
 * @property array $users_votes_up
 * @property array $users_votes_down
 * @property mixed $result_vote
 * @property int $count_vote
 *
 * @method ActiveQuery findVotes
 * @method Vote|bool vote
 *
 * @package skeeks\cms\models\behaviors\traits
 */
trait HasVotes
{}