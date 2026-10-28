<?php

use skeeks\cms\crm\OutboundSales;
use skeeks\cms\models\CmsLeadPipeline;
use yii\db\Migration;

/**
 * П2д: the stage «Ответил» of «Исходящие продажи» — a campaign (the flow:
 * bot, API, mailing) puts the deal it makes on a reply there
 * (OutboundSales::stage(..., ROLE_REPLIED)). Only missing stages are added,
 * after «Смотрел»; nothing existing is changed.
 */
class m261029_110000__update_cms_lead_stage__outbound_replied extends Migration
{
    public function safeUp()
    {
        if ($this->db->getTableSchema('{{%cms_lead_pipeline}}', true)) {
            foreach (CmsLeadPipeline::find()->andWhere(['code' => CmsLeadPipeline::CODE_OUTBOUND])->all() as $pipeline) {
                OutboundSales::ensureStages($pipeline);
            }
        }
    }

    public function safeDown()
    {
        // The stages stay: deals and the history may already stand on them.
        return true;
    }
}
