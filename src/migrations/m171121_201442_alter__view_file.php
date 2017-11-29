<?php
/**
 * m140801_201442_create_user_table
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */

use yii\db\Schema;
use yii\db\Migration;

/**
 * Class m171121_201442_alter__view_file
 */
class m171121_201442_alter__view_file extends Migration
{
    public function safeUp()
    {
        if ($this->db->driverName === 'pgsql') {
            $tableSchema = $this->db->getTableSchema('cms_tree_type');
            if ($tableSchema->getColumn('viewfile')) {
                $this->renameColumn('{{%cms_tree_type}}', 'viewfile', 'view_file');
            }

            $tableSchema = $this->db->getTableSchema('cms_content');
            if ($tableSchema->getColumn('viewfile')) {
                $this->renameColumn('{{%cms_content}}', 'viewfile', 'view_file');
            }

        } else {

            $tableSchema = $this->db->getTableSchema('cms_tree_type');
            if ($tableSchema->getColumn('viewFile')) {
                $this->renameColumn('{{%cms_tree_type}}', 'viewFile', 'view_file');
            }

            $tableSchema = $this->db->getTableSchema('cms_content');
            if ($tableSchema->getColumn('viewFile')) {
                $this->renameColumn('{{%cms_content}}', 'viewFile', 'view_file');
            }
        }
    }

    public function down()
    {
        echo "m171121_201442_alter__view_file cannot be reverted.\n";
        return false;
    }
}
