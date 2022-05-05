<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m220504_090601__create_table__cms_theme extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_theme';
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

            'created_by' => $this->integer(),
            'updated_by' => $this->integer(),

            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),

            'cms_site_id' => $this->integer()->notNull(),

            'code' => $this->string(255)->comment("Уникальный код темы")->notNull(),
            'config' => $this->text()->comment("Настройки темы"),

            //Можно переписать название темы, описание и фото
            'name' => $this->string(255)->comment("Название темы"),
            'description' => $this->string(255)->comment("Описание темы"),
            'cms_image_id' => $this->integer()->comment("Фото"),

            'priority' => $this->integer()->notNull()->defaultValue(100),

            'is_active' => $this->integer(1)->comment("Активирована?"),

        ], $tableOptions);

        $this->createIndex($tableName.'__updated_by', $tableName, 'updated_by');
        $this->createIndex($tableName.'__created_by', $tableName, 'created_by');
        $this->createIndex($tableName.'__created_at', $tableName, 'created_at');
        $this->createIndex($tableName.'__updated_at', $tableName, 'updated_at');


        $this->createIndex($tableName.'__cms_site_id', $tableName, 'cms_site_id');
        $this->createIndex($tableName.'__priority', $tableName, 'priority');
        $this->createIndex($tableName.'__is_active_uniq', $tableName, ['cms_site_id', 'is_active'], true);
        $this->createIndex($tableName.'__code_uniq', $tableName, ['cms_site_id', 'code'], true);
        $this->createIndex($tableName.'__cms_image_id', $tableName, ['cms_image_id']);

        $this->addCommentOnTable($tableName, 'Темы сайта');

        $this->addForeignKey(
            "{$tableName}__created_by", $tableName,
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );
        $this->addForeignKey(
            "{$tableName}__updated_by", $tableName,
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        //Удаляя сайт - удаляются и все его телефоны
        $this->addForeignKey(
            "{$tableName}__cms_site_id", $tableName,
            'cms_site_id', '{{%cms_site}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->addForeignKey(
            "{$tableName}__cms_image_id", $tableName,
            'cms_image_id', '{{%cms_storage_file}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function safeDown()
    {
        echo "m200507_110601__create_table__shop_product_relation cannot be reverted.\n";
        return false;
    }
}