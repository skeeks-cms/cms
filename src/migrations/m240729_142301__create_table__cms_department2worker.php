
<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m240729_142301__create_table__cms_department2worker extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_department2worker';
        $tableExist = $this->db->getTableSchema($tableName, true);

        if ($tableExist) {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable($tableName, [

            'id' => $this->primaryKey(),

            'created_by' => $this->integer()->null(),
            'created_at' => $this->integer()->null(),

            'cms_department_id' => $this->integer()->notNull()->comment("Отдел"),
            'worker_id' => $this->integer()->notNull()->comment("Сотрудник"),

        ], $tableOptions);

        $this->createIndex($tableName.'__worker_id', $tableName, 'worker_id');
        $this->createIndex($tableName.'__created_by', $tableName, 'created_by');
        $this->createIndex($tableName.'__created_at', $tableName, 'created_at');
        $this->createIndex($tableName.'__cms_department_id', $tableName, 'cms_department_id');
        $this->createIndex($tableName.'__uniq', $tableName, ['cms_department_id', 'worker_id']);

        $this->addCommentOnTable($tableName, 'Связь отделов и сотрудников');

        $this->addForeignKey(
            "{$tableName}__created_by", $tableName,
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            "{$tableName}__worker_id", $tableName,
            'worker_id', '{{%cms_user}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->addForeignKey(
            "{$tableName}__cms_department_id", $tableName,
            'cms_department_id', '{{%cms_department}}', 'id', 'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        echo "m240530_132301__create_table__cms_company cannot be reverted.\n";
        return false;
    }
}