<?php

namespace skeeks\cms\controllers\traits;

use yii\web\NotFoundHttpException;

/**
 * A model card opens only a record the employee sees: getModel() loads the
 * record of `pk` through forManager() of its query (CrmAccess rules: the
 * client seen in full, or "своё" for the level "Свои сделки"), otherwise 404.
 * Every model action — view, update, tabs, delete — follows one rule, as the
 * deal (AdminCmsLeadController) and the proposal cards do.
 *
 * The controller as a related grid of another card (`pk` is the parent's)
 * keeps the default lookup: only the request's own controller is scoped.
 *
 * Usage: `use ManagerScopedModel;` in a BackendModelStandartController whose
 * model query has forManager().
 */
trait ManagerScopedModel
{
    public function getModel()
    {
        if ($this->_model === null && \Yii::$app instanceof \yii\web\Application && \Yii::$app->controller === $this
            && ($pk = \Yii::$app->request->get($this->requestPkParamName))) {
            $modelClass = $this->modelClassName;
            $query = $modelClass::find()->forManager();
            if ((new $modelClass())->hasAttribute('cms_site_id')) {
                $query->cmsSite();
            }
            $this->_model = $query->andWhere([$modelClass::tableName().'.'.$this->modelPkAttribute => $pk])->limit(1)->one();
            if (!$this->_model) {
                throw new NotFoundHttpException('Запись не найдена или у вас нет к ней доступа.');
            }
        }

        return parent::getModel();
    }
}
