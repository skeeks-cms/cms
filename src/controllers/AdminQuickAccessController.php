<?php

namespace skeeks\cms\controllers;

use skeeks\cms\backend\BackendController;
use skeeks\cms\backend\helpers\BackendUrlHelper;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsProject;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\CmsUserFavorite;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

class AdminQuickAccessController extends BackendController
{
    public function getPermissionName()
    {
        return '';
    }

    public function actionFavorites()
    {
        $rr = new RequestResponse();
        $rr->success = true;
        $rr->data = [
            'favorites' => static::getFavoriteItems((int) \Yii::$app->user->id),
        ];

        return $rr;
    }

    public function actionToggleFavorite()
    {
        $rr = new RequestResponse();

        try {
            $userId = (int) \Yii::$app->user->id;
            $item = (array) \Yii::$app->request->post('item', []);
            $type = CmsUserFavorite::normalizeType(ArrayHelper::getValue($item, 'type'));
            $entityId = (int) ArrayHelper::getValue($item, 'id');

            if (!$userId || !$type || !$entityId) {
                throw new \Exception('Не передан объект избранного');
            }

            if (!array_key_exists($type, CmsUserFavorite::typeLabels())) {
                throw new \Exception('Неподдерживаемый тип объекта');
            }

            $favorite = CmsUserFavorite::find()
                ->andWhere([
                    'cms_user_id' => $userId,
                    'entity_type' => $type,
                    'entity_id' => $entityId,
                ])
                ->one();

            $active = false;
            if ($favorite) {
                $favorite->delete();
            } else {
                $favorite = new CmsUserFavorite();
                $favorite->cms_user_id = $userId;
                $favorite->entity_type = $type;
                $favorite->entity_id = $entityId;
                $favorite->priority = static::nextPriority($userId);
                if (!$favorite->save()) {
                    throw new \Exception('Не удалось сохранить избранное: ' . print_r($favorite->errors, true));
                }
                $active = true;
            }

            $rr->success = true;
            $rr->data = [
                'active' => $active,
                'favorites' => static::getFavoriteItems($userId),
            ];
        } catch (\Exception $e) {
            $rr->success = false;
            $rr->message = $e->getMessage();
        }

        return $rr;
    }

    public function actionSortFavorites()
    {
        $rr = new RequestResponse();

        try {
            $userId = (int) \Yii::$app->user->id;
            $keys = (array) \Yii::$app->request->post('keys', []);
            $priority = 100;

            foreach ($keys as $key) {
                list($type, $entityId) = array_pad(explode(':', (string) $key, 2), 2, null);
                $type = CmsUserFavorite::normalizeType($type);
                $entityId = (int) $entityId;

                if (!$type || !$entityId) {
                    continue;
                }

                CmsUserFavorite::updateAll(
                    ['priority' => $priority, 'updated_at' => time()],
                    ['cms_user_id' => $userId, 'entity_type' => $type, 'entity_id' => $entityId]
                );
                $priority += 100;
            }

            $rr->success = true;
            $rr->data = [
                'favorites' => static::getFavoriteItems($userId),
            ];
        } catch (\Exception $e) {
            $rr->success = false;
            $rr->message = $e->getMessage();
        }

        return $rr;
    }

    public static function getFavoriteItems($userId)
    {
        $result = [];
        $favorites = CmsUserFavorite::find()
            ->andWhere(['cms_user_id' => $userId])
            ->orderBy(['priority' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        foreach ($favorites as $favorite) {
            $item = static::favoriteToItem($favorite);
            if ($item) {
                $result[] = $item;
            }
        }

        return $result;
    }

    protected static function nextPriority($userId)
    {
        $max = (int) CmsUserFavorite::find()
            ->andWhere(['cms_user_id' => $userId])
            ->max('priority');

        return $max + 100;
    }

    protected static function favoriteToItem(CmsUserFavorite $favorite)
    {
        $model = null;
        $route = null;
        $name = null;
        $image = null;

        if ($favorite->entity_type === CmsUserFavorite::TYPE_COMPANY) {
            $model = CmsCompany::find()->forManager()->andWhere([CmsCompany::tableName() . '.id' => $favorite->entity_id])->one();
            $route = '/cms/admin-cms-company/view';
            $name = $model ? (string) $model->name : null;
            $image = static::modelImage($model);
        } elseif ($favorite->entity_type === CmsUserFavorite::TYPE_PROJECT) {
            $model = CmsProject::find()->forManager()->andWhere([CmsProject::tableName() . '.id' => $favorite->entity_id])->one();
            $route = '/cms/admin-cms-project/view';
            $name = $model ? (string) $model->name : null;
            $image = static::modelImage($model);
        } elseif ($favorite->entity_type === CmsUserFavorite::TYPE_CLIENT) {
            $model = CmsUser::find()->forManager()->isWorker(false)->andWhere([CmsUser::tableName() . '.id' => $favorite->entity_id])->one();
            $route = '/cms/admin-user/view';
            $name = $model ? (string) $model->shortDisplayName : null;
            $image = $model ? (string) $model->avatarSrc : null;
        }

        if (!$model || !$route) {
            return null;
        }

        return [
            'type' => $favorite->entity_type,
            'id' => (int) $favorite->entity_id,
            'name' => $name,
            'url' => Url::to([$route, 'pk' => $favorite->entity_id]),
            'action' => (string) BackendUrlHelper::createByParams([
                $route,
                'pk' => $favorite->entity_id,
            ])->enableEmptyLayout()->enableNoActions()->url,
            'image' => $image,
            'touchedAt' => (int) $favorite->updated_at,
        ];
    }

    protected static function modelImage($model)
    {
        if ($model && $model->cmsImage) {
            return (string) \Yii::$app->imaging->thumbnailUrlOnRequest($model->cmsImage->src, new \skeeks\cms\components\imaging\filters\Thumbnail([
                'w' => 80,
                'h' => 80,
                'm' => \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND,
            ]), '', true);
        }

        return null;
    }
}
