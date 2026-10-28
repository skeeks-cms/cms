<?php

use skeeks\cms\crm\OutboundSales;
use skeeks\cms\models\CmsLeadPipeline;
use yii\db\Migration;

/**
 * П2д: «Услуга» of a deal (the kind of service being sold, `cms_deal_type`)
 * and the stages of «Исходящие продажи» the personal offer moves a deal
 * through. An outbound pipeline without stages gets the whole default chain
 * (OutboundSales::DEFAULT_STAGES); one with stages only gets the missing
 * «КП отправлено» and «Смотрел» in their place: nothing is renamed, moved or
 * deleted.
 */
class m261029_100000__alter_table__cms_lead_outbound extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%cms_lead}}', 'cms_deal_type_id', $this->integer()->null()->comment('Услуга'));
        $this->createIndex('cms_lead__cms_deal_type_id', '{{%cms_lead}}', ['cms_deal_type_id']);
        $this->addForeignKey('cms_lead__cms_deal_type_id', '{{%cms_lead}}', 'cms_deal_type_id', '{{%cms_deal_type}}', 'id', 'SET NULL', 'SET NULL');

        if ($this->db->getTableSchema('{{%cms_lead_pipeline}}', true)) {
            foreach (CmsLeadPipeline::find()->andWhere(['code' => CmsLeadPipeline::CODE_OUTBOUND])->all() as $pipeline) {
                OutboundSales::ensureStages($pipeline);
            }
        }
    }

    public function safeDown()
    {
        // The stages stay: deals and the history may already stand on them.
        $this->dropForeignKey('cms_lead__cms_deal_type_id', '{{%cms_lead}}');
        $this->dropIndex('cms_lead__cms_deal_type_id', '{{%cms_lead}}');
        $this->dropColumn('{{%cms_lead}}', 'cms_deal_type_id');
    }
}
