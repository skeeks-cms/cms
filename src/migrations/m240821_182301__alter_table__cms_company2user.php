<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m240821_182301__alter_table__cms_company2user extends Migration
{
    public function safeUp()
    {
        //$tableName = '{{%cms_session}}';
        $tableName = 'cms_company2user';

        $this->addColumn($tableName, "comment", $this->string(255)->null()->comment("Комментарий"));
        $this->addColumn($tableName, "sort", $this->integer()->notNull()->defaultValue(100)->comment("Сортировка"));

        $this->addColumn($tableName, "is_root", $this->integer(0)->notNull()->defaultValue(1)->comment("Доступны все данные по копании?"));
        $this->addColumn($tableName, "is_notify", $this->integer(0)->notNull()->defaultValue(1)->comment("Отправлять уведомления?"));


        $this->createIndex($tableName.'__comment', $tableName, 'comment');
        $this->createIndex($tableName.'__sort', $tableName, 'sort');
        $this->createIndex($tableName.'__is_root', $tableName, 'is_root');
        $this->createIndex($tableName.'__is_notify', $tableName, 'is_notify');

    }

    public function safeDown()
    {
        echo self::class;
        return false;
    }
}