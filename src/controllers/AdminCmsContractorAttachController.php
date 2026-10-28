<?php

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\BackendModelCreateAction;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsContractor;
use yii\base\Event;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

/**
 * Adds requisites (a contractor) to an owner record — a company or a lead —
 * through the full contractor form with the "fill by INN" button.
 *
 * The INN is unique across the base: when a contractor with the entered INN
 * already exists, it is only linked to the owner and the form data is not
 * saved, so requisites are never duplicated. Otherwise the new contractor is
 * created and linked in one transaction.
 */
abstract class AdminCmsContractorAttachController extends AdminCmsContractorController
{
    private $_owner;
    /** @var \yii\db\Transaction|null */
    private $_transaction;

    /** GET parameter with the owner id. */
    abstract protected function ownerParam(): string;

    abstract protected function findOwner(int $id): ?ActiveRecord;

    abstract protected function canAttach(ActiveRecord $owner): bool;

    abstract protected function isAttached(ActiveRecord $owner, CmsContractor $contractor): bool;

    /** Saves the owner-contractor link or throws \RuntimeException. */
    abstract protected function attach(ActiveRecord $owner, CmsContractor $contractor): void;

    /** Where the form returns after saving. */
    abstract protected function ownerUrl(ActiveRecord $owner): array;

    public function actions()
    {
        $create = ArrayHelper::getValue(parent::actions(), 'create', []);

        return [
            'create' => ArrayHelper::merge($create, [
                'class' => BackendModelCreateAction::class,
                'name' => 'Добавить реквизиты',
                'buttons' => ['save'],
                'accessCallback' => fn() => $this->canAttach($this->owner),
                'on '.BackendModelCreateAction::EVENT_BEFORE_VALIDATE => function (Event $event) {
                    $event->sender->afterSaveUrl = \yii\helpers\Url::to($this->ownerUrl($this->owner));
                    $this->attachExisting($event->sender);
                },
                'on '.BackendModelCreateAction::EVENT_BEFORE_SAVE => function () {
                    $this->beginAttachTransaction();
                },
                'on '.BackendModelCreateAction::EVENT_AFTER_SAVE => function (Event $event) {
                    $this->attach($this->owner, $event->sender->model);
                    $this->_transaction->commit();
                },
            ]),
        ];
    }

    public function updateFields($action)
    {
        $result = parent::updateFields($action);
        // "Our company" is a property of the studio's own legal entities, not
        // of a client's requisites.
        unset($result['is_our']);

        return $result;
    }

    public function getOwner(): ActiveRecord
    {
        if ($this->_owner === null) {
            $this->_owner = $this->findOwner((int)\Yii::$app->request->get($this->ownerParam()));
            if (!$this->_owner) {
                throw new NotFoundHttpException('Запись не найдена или недоступна.');
            }
        }

        return $this->_owner;
    }

    private function beginAttachTransaction(): void
    {
        $this->_transaction = CmsContractor::getDb()->beginTransaction();
        \Yii::$app->on(\yii\base\Application::EVENT_AFTER_REQUEST, function (): void {
            if ($this->_transaction && $this->_transaction->isActive) {
                $this->_transaction->rollBack();
            }
        });
    }

    /**
     * A contractor with the same INN is linked instead of being created; the
     * request ends here with the same response as a regular save.
     */
    private function attachExisting(BackendModelCreateAction $action): void
    {
        $inn = trim((string)$action->model->inn);
        if ($inn === '' || !\Yii::$app->request->isPost) {
            return;
        }
        $existing = CmsContractor::find()->andWhere(['inn' => $inn])->one();
        if (!$existing) {
            return;
        }

        $rr = new RequestResponse();
        $rr->success = true;
        try {
            if (!$this->isAttached($this->owner, $existing)) {
                $this->attach($this->owner, $existing);
                $rr->message = 'Реквизиты с этим ИНН уже есть в базе — они привязаны без повторного создания.';
            } else {
                $rr->message = 'Эти реквизиты уже добавлены.';
            }
        } catch (\DomainException $e) {
            // A refusal of the owner (П2а: an INN of another company without a reason) is a message, not an error page.
            $rr->success = false;
            $rr->message = $e->getMessage();
        }

        if ($rr->isRequestAjaxPost() && !$rr->success) {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            \Yii::$app->response->data = $rr;
            \Yii::$app->end();
        }
        if (!$rr->success) {
            \Yii::$app->session->setFlash('error', $rr->message);
            \Yii::$app->response->redirect(\yii\helpers\Url::current());
            \Yii::$app->end();
        }

        if ($rr->isRequestAjaxPost()) {
            $rr->success = true;
            $rr->data = [
                'afterSaveUrl' => \yii\helpers\Url::to($this->ownerUrl($this->owner)),
                'type' => 'create',
                'submitBtn' => \Yii::$app->request->post('submit-btn'),
            ];
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            \Yii::$app->response->data = $rr;
        } else {
            \Yii::$app->session->setFlash('success', $rr->message);
            \Yii::$app->response->redirect($this->ownerUrl($this->owner));
        }
        \Yii::$app->end();
    }
}
