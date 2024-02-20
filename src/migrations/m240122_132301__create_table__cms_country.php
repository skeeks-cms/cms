<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m240122_132301__create_table__cms_country extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_country';
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

            'name' => $this->string(255)->notNull()->unique()->comment("Название страны"),

            'alpha2' => $this->string(2)->notNull()->unique()->comment("ISO 3166-1 alpha-2"),
            'alpha3' => $this->string(3)->notNull()->unique()->comment("ISO 3166-1 alpha-3"),
            'iso'    => $this->string(3)->notNull()->unique()->comment("Цифровой код"),

            'phone_code' => $this->string(16)->null()->comment("Код телефона"),
            'domain'     => $this->string(16)->null()->comment("Домен"),

            'flag_image_id' => $this->integer()->null()->comment("Флаг"),

        ], $tableOptions);

        $this->createIndex($tableName.'__phone_code', $tableName, 'phone_code');
        $this->createIndex($tableName.'__domain', $tableName, 'domain');
        $this->createIndex($tableName.'__flag_image_id', $tableName, 'flag_image_id');

        $this->addCommentOnTable($tableName, 'Справочник стран');

        $this->addForeignKey(
            "{$tableName}__flag_image_id", $tableName,
            'flag_image_id', '{{%cms_storage_file}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function safeDown()
    {
        echo "m200507_110601__create_table__shop_product_relation cannot be reverted.\n";
        return false;
    }
}