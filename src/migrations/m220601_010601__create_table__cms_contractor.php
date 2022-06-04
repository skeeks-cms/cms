<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m220601_010601__create_table__cms_contractor extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_contractor';
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

            'contractor_type' => $this->string()->notNull()->comment("Тип контрагента (Ип, Юр, Физ лицо)"),

            'name'      => $this->string()->comment("Название"),
            'full_name' => $this->string()->comment("Полное название"),
            'international_name' => $this->string()->comment("Интернациональное название"),

            'first_name' => $this->string(),
            'last_name'  => $this->string(),
            'patronymic' => $this->string(),

            'inn'  => $this->string(255)->comment("ИНН"),
            'ogrn' => $this->string(255)->comment("ОГРН"),
            'kpp'  => $this->string(255)->comment("КПП"),
            'okpo' => $this->string(255)->comment("ОКПО"),

            'address'         => $this->string(255)->comment("Адрес организации"),

            'mailing_address' => $this->string(255)->comment("Почтовый адрес (для отправки писем)"),
            'mailing_postcode' => $this->string(255)->comment("Почтовый индекс"),

            'cms_image_id'            => $this->integer()->comment("Фото"),

            'stamp_id'                => $this->integer()->comment("Печать"),
            'director_signature_id'   => $this->integer()->comment("Подпись директора"),
            'signature_accountant_id' => $this->integer()->comment("Подпись гл. бухгалтера"),

            'phone' => $this->string(255)->comment("Телефон"),
            'email' => $this->string(255)->comment("Email"),

            'is_our' => $this->integer(1)->defaultValue(0)->notNull()->comment("Это наш контрагент?"),

            'description' => $this->text()->comment("Описание"),

        ], $tableOptions);


        $this->createIndex($tableName.'__updated_by', $tableName, 'updated_by');
        $this->createIndex($tableName.'__created_by', $tableName, 'created_by');
        $this->createIndex($tableName.'__created_at', $tableName, 'created_at');
        $this->createIndex($tableName.'__updated_at', $tableName, 'updated_at');

        $this->createIndex($tableName.'__phone', $tableName, 'phone');
        $this->createIndex($tableName.'__email', $tableName, 'email');

        $this->createIndex($tableName.'__first_name', $tableName, 'first_name');
        $this->createIndex($tableName.'__last_name', $tableName, 'last_name');
        $this->createIndex($tableName.'__patronymic', $tableName, 'patronymic');
        $this->createIndex($tableName.'__name', $tableName, 'name');
        $this->createIndex($tableName.'__full_name', $tableName, 'full_name');
        $this->createIndex($tableName.'__international_name', $tableName, 'international_name');
        $this->createIndex($tableName.'__is_our', $tableName, 'is_our');


        $this->createIndex($tableName.'__cms_site_id', $tableName, 'cms_site_id');
        $this->createIndex($tableName.'__cms_site_id_inn', $tableName, ['cms_site_id', 'inn', 'contractor_type'], true);

        $this->addCommentOnTable($tableName, 'Контрагенты');

        $this->addForeignKey(
            "{$tableName}__created_by", $tableName,
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );
        $this->addForeignKey(
            "{$tableName}__updated_by", $tableName,
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );


        $this->addForeignKey(
            "{$tableName}__cms_image_id", $tableName,
            'cms_image_id', '{{%cms_storage_file}}', 'id', 'SET NULL', 'SET NULL'
        );
        $this->addForeignKey(
            "{$tableName}__stamp_id", $tableName,
            'stamp_id', '{{%cms_storage_file}}', 'id', 'SET NULL', 'SET NULL'
        );
        $this->addForeignKey(
            "{$tableName}__director_signature_id", $tableName,
            'director_signature_id', '{{%cms_storage_file}}', 'id', 'SET NULL', 'SET NULL'
        );
        $this->addForeignKey(
            "{$tableName}__signature_accountant_id", $tableName,
            'signature_accountant_id', '{{%cms_storage_file}}', 'id', 'SET NULL', 'SET NULL'
        );



        $this->addForeignKey(
            "{$tableName}__cms_site_id", $tableName,
            'cms_site_id', '{{%cms_site}}', 'id', 'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        echo "m200507_110601__create_table__shop_product_relation cannot be reverted.\n";
        return false;
    }
}