<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m251022_172301__create_table__cms_faq2content_element extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_faq2content_element';
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
            'cms_content_element_id' => $this->integer()->notNull()->comment("Элемент"),

        ], $tableOptions);

        $this->createIndex($tableName.'__uniq', $tableName, ['cms_faq_id', 'cms_content_element_id'], true);

        $this->addCommentOnTable($tableName, 'Связь вопрос/ответ с элементом');


        $this->addForeignKey(
            "{$tableName}__cms_faq_id", $tableName,
            'cms_faq_id', '{{%cms_faq}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->addForeignKey(
            "{$tableName}__cms_content_element_id", $tableName,
            'cms_content_element_id', '{{%cms_content_element}}', 'id', 'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        echo "m240530_132301__create_table__cms_company cannot be reverted.\n";
        return false;
    }
}

