<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;
use yii\helpers\ArrayHelper;

class m160329_113837__update_data__cms_user extends Migration
{
    public function safeUp()
    {
        try
        {
            $data = \Yii::$app->db->createCommand('SELECT * FROM `cms_user_email`')->queryAll();
            if ($data)
            {
                foreach ($data as $row)
                {
                    if (ArrayHelper::getValue($row, 'value') && ArrayHelper::getValue($row, 'user_id') && (int) ArrayHelper::getValue($row, 'user_id') > 0)
                    {
                        \Yii::$app->db->createCommand()->update('cms_user', [
                            'email' => ArrayHelper::getValue($row, 'value')
                        ], [
                            'id' => ArrayHelper::getValue($row, 'user_id')
                        ])->execute();
                    }

                }
            }

            $data = \Yii::$app->db->createCommand('SELECT * FROM `cms_user_phone`')->queryAll();
            if ($data)
            {
                foreach ($data as $row)
                {
                    if (ArrayHelper::getValue($row, 'value') && ArrayHelper::getValue($row, 'user_id') && (int) ArrayHelper::getValue($row, 'user_id') > 0)
                    {
                        \Yii::$app->db->createCommand()->update('cms_user', [
                            'phone' => ArrayHelper::getValue($row, 'value')
                        ], [
                            'id' => ArrayHelper::getValue($row, 'user_id')
                        ])->execute();
                    }

                }
            }

            return true;

        } catch(\Exception $e)
        {
            return false;
        }

    }

    public function safeDown()
    {
        echo "m160329_103837__alter_table__cms_user cannot be reverted.\n";
        return false;
    }
}