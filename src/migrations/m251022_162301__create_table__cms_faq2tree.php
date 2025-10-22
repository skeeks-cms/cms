<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m251022_162301__create_table__cms_faq2tree extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_faq2tree';
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

            'cms_faq_id' => $this->integer()->notNull()->comment("Faq"),
            'cms_tree_id' => $this->integer()->notNull()->comment("Раздел"),

        ], $tableOptions);

        $this->createIndex($tableName.'__uniq', $tableName, ['cms_faq_id', 'cms_tree_id'], true);

        $this->addCommentOnTable($tableName, 'Связь вопрос/ответ с разделом');


        $this->addForeignKey(
            "{$tableName}__cms_faq_id", $tableName,
            'cms_faq_id', '{{%cms_faq}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->addForeignKey(
            "{$tableName}__cms_tree_id", $tableName,
            'cms_tree_id', '{{%cms_tree}}', 'id', 'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        echo "m240530_132301__create_table__cms_company cannot be reverted.\n";
        return false;
    }
}

