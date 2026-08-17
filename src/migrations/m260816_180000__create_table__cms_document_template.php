<?php

use yii\db\Migration;

class m260816_180000__create_table__cms_document_template extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_document_template';
        if ($this->db->getTableSchema($tableName, true)) {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable($tableName, [
            'id'                   => $this->primaryKey(),
            'created_by'           => $this->integer()->null(),
            'updated_by'           => $this->integer()->null(),
            'created_at'           => $this->integer()->null(),
            'updated_at'           => $this->integer()->null(),
            'cms_site_id'          => $this->integer()->notNull(),
            'name'                 => $this->string(255)->notNull(),
            'theme'                => $this->string(32)->notNull()->defaultValue('skeeks-dark'),
            'document_type'        => $this->string(32)->notNull()->defaultValue('all'),
            'is_active'            => $this->smallInteger()->notNull()->defaultValue(1),
            'is_default'           => $this->smallInteger()->notNull()->defaultValue(0),
            'logo_storage_file_id' => $this->integer()->null(),
            'accent_color'         => $this->string(7)->null(),
            'background_color'     => $this->string(7)->null(),
            'surface_color'        => $this->string(7)->null(),
            'text_color'           => $this->string(7)->null(),
            'muted_color'          => $this->string(7)->null(),
            'border_color'         => $this->string(7)->null(),
            'footer_text'          => $this->text()->null(),
            'show_cover'           => $this->smallInteger()->notNull()->defaultValue(1),
            'show_footer'          => $this->smallInteger()->notNull()->defaultValue(1),
            'show_page_numbers'    => $this->smallInteger()->notNull()->defaultValue(1),
            'page_orientation'     => $this->string(32)->notNull()->defaultValue('portrait'),
        ], $tableOptions);

        $this->createIndex($tableName.'__site_name', $tableName, ['cms_site_id', 'name'], true);
        $this->createIndex($tableName.'__site_document', $tableName, ['cms_site_id', 'document_type']);
        $this->createIndex($tableName.'__site_default', $tableName, ['cms_site_id', 'document_type', 'is_default']);
        $this->createIndex($tableName.'__is_active', $tableName, 'is_active');

        $this->addForeignKey($tableName.'__created_by', $tableName, 'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL');
        $this->addForeignKey($tableName.'__updated_by', $tableName, 'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL');
        $this->addForeignKey($tableName.'__cms_site_id', $tableName, 'cms_site_id', '{{%cms_site}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey($tableName.'__logo_storage_file_id', $tableName, 'logo_storage_file_id', '{{%cms_storage_file}}', 'id', 'SET NULL', 'SET NULL');
        $this->addCommentOnTable($tableName, 'Профили оформления документов');

        return true;
    }

    public function safeDown()
    {
        $tableName = 'cms_document_template';
        if (!$this->db->getTableSchema($tableName, true)) {
            return true;
        }

        foreach (['created_by', 'updated_by', 'cms_site_id', 'logo_storage_file_id'] as $suffix) {
            $this->dropForeignKey($tableName.'__'.$suffix, $tableName);
        }
        $this->dropTable($tableName);

        return true;
    }
}
