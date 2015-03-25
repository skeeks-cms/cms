<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 12.03.2015
 */

namespace skeeks\cms\modules\admin\models\forms;

use skeeks\cms\models\User;
use Yii;
use yii\base\Model;

/**
 * Class SshConsoleForm
 * @package skeeks\cms\models\forms
 */
class EmailConsoleForm extends Model
{
    public $content;
    public $to;
    public $from;
    public $subject;

    public function attributeLabels()
    {
        return [
            'content'   => 'Тело сообщения',
            'subject'   => 'Тема сообщения',
            'to'        => 'Кому',
            'from'      => 'От кого'
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['subject', 'to', 'from', 'content'], 'required'],
            [['subject', 'to', 'from', 'content'], 'string'],
            [['to', 'from'], 'email'],
        ];
    }



    /**
     * Logs in a user using the provided username and password.
     *
     * @return boolean whether the user is logged in successfully
     */
    public function execute()
    {
        if ($this->validate())
        {
            $message = \Yii::$app->mailer->compose('@skeeks/cms/mail/text', [
                'content'      => $this->content,
            ])
            ->setFrom([$this->from => \Yii::$app->name])
            ->setTo($this->to)
            ->setSubject($this->subject . ' ' . \Yii::$app->name);

            return $message->send();
        } else
        {
            return false;
        }
    }


}
