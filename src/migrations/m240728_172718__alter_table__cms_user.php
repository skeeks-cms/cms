<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.07.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m240728_172718__alter_table__cms_user extends Migration
{
    public function safeUp()
    {
        //$tableName = '{{%cms_session}}';
        $tableName = 'cms_user';

        $this->addColumn($tableName, "is_worker", $this->integer(1)->notNull()->defaultValue(0)->comment("Сотрудник?"));

        $this->addColumn($tableName, "post", $this->string(255)->null()->comment("Должность"));
        $this->addColumn($tableName, "work_shedule", $this->text()->null()->comment("Рабочий график"));

        $this->createIndex($tableName.'__is_worker', $tableName, 'is_worker');
    }

    public function safeDown()
    {
        echo self::class;
        return false;
    }
}
