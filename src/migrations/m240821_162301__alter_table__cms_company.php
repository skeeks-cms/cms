<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.07.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m240821_162301__alter_table__cms_company extends Migration
{
    public function safeUp()
    {
        //$tableName = '{{%cms_session}}';
        $tableName = 'cms_company';

        $this->addColumn($tableName, "cms_company_status_id", $this->integer()->null()->comment("Статус компании"));
        $this->createIndex($tableName.'__cms_company_status_id', $tableName, 'cms_company_status_id');

        $this->addForeignKey(
            "{$tableName}__cms_company_status_id", $tableName,
            'cms_company_status_id', '{{%cms_company_status}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function safeDown()
    {
        echo self::class;
        return false;
    }
}
