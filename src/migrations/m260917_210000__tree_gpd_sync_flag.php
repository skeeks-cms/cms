<?php

use yii\db\Migration;

/** Preserve client edits when category synchronization becomes available. */
class m260917_210000__tree_gpd_sync_flag extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%cms_tree}}', 'is_sx_info_update', $this->boolean()->notNull()->defaultValue(1));
        // sx_id is the existing GPD identity, regardless of site or tree type.
        $this->update('{{%cms_tree}}', ['is_sx_info_update' => 0], ['>', 'sx_id', 0]);
    }

    public function safeDown()
    {
        echo "Cannot remove category synchronization protection automatically.\n";
        return false;
    }
}
