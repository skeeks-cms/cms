<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m240821_172301__create_table__cms_company2category extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_company2category';
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

            'cms_company_id' => $this->integer()->notNull()->comment("Компания"),
            'cms_company_category_id' => $this->integer()->notNull()->comment("Категория"),

        ], $tableOptions);

        $this->createIndex($tableName.'__uniq', $tableName, ['cms_company_id', 'cms_company_category_id'], true);

        $this->addCommentOnTable($tableName, 'Связь компании с категорией');


        $this->addForeignKey(
            "{$tableName}__cms_company_id", $tableName,
            'cms_company_id', '{{%cms_company}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->addForeignKey(
            "{$tableName}__cms_company_category_id", $tableName,
            'cms_company_category_id', '{{%cms_company_category}}', 'id', 'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        echo "m200507_110601__create_table__shop_product_relation cannot be reverted.\n";
        return false;
    }
}