<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m220604_090601__create_table__cms_user_log extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_user_log';
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

            'cms_user_id' => $this->integer()->notNull(),
            'cms_site_id' => $this->integer()->notNull(),

            'name' => $this->string(255)->comment("Название адреса (необязательное)"),

            'value' => $this->string(255)->notNull()->comment("Полный адрес"),

            'latitude' => $this->double()->notNull()->comment("Широта"),
            'longitude' => $this->double()->notNull()->comment("Долгота"),

            'entrance' => $this->string(255)->comment("Подъезд"),
            'floor' => $this->string(255)->comment("Этаж"),
            'apartment_number' => $this->string(255)->comment("Номер квартиры"),

            'comment' => $this->text()->comment("Комментарий"),

            'cms_image_id' => $this->integer()->comment("Фото адреса"),

            'priority' => $this->integer()->notNull()->defaultValue(100),

        ], $tableOptions);

        $this->createIndex($tableName.'__updated_by', $tableName, 'updated_by');
        $this->createIndex($tableName.'__created_by', $tableName, 'created_by');
        $this->createIndex($tableName.'__created_at', $tableName, 'created_at');
        $this->createIndex($tableName.'__updated_at', $tableName, 'updated_at');


        $this->createIndex($tableName.'__cms_site_id', $tableName, 'cms_site_id');
        $this->createIndex($tableName.'__priority', $tableName, 'priority');

        $this->createIndex($tableName.'__value_uniq', $tableName, ['cms_user_id', 'value']);
        $this->createIndex($tableName.'__name_uniq', $tableName, ['cms_user_id', 'name'], true);
        $this->createIndex($tableName.'__coordinates', $tableName, ['cms_user_id', 'latitude', 'longitude']);

        $this->createIndex($tableName.'__cms_image_id', $tableName, ['cms_image_id']);

        $this->addCommentOnTable($tableName, 'Адреса пользователя');

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

        //Удаляя пользователя - удаляются и все email
        $this->addForeignKey(
            "{$tableName}__cms_user_id", $tableName,
            'cms_user_id', '{{%cms_user}}', 'id', 'CASCADE', 'CASCADE'
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