<?php
/**
 * DbController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 08.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\controllers;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\Search;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\controllers\helpers\rules\NoModel;
use skeeks\sx\Dir;
use yii\data\ArrayDataProvider;
use yii\filters\VerbFilter;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Class IndexController
 * @package skeeks\cms\modules\admin\controllers
 */
class DbController extends AdminController
{
    public function init()
    {
        $this->name = "Удаление временных файлов";

        parent::init();
    }


    public function actions()
    {
        return
        [
            "index" =>
            [
                "class"        => AdminAction::className(),
                "name"         => "Работа с базой данных",
                "callback"     => [$this, 'actionIndex'],
            ],
        ];
    }


    public function actionIndex()
    {
        $message = '';

        if (\Yii::$app->request->isPost)
        {
            if (\Yii::$app->request->getQueryParam('act') == 'refresh-tables')
            {
                \Yii::$app->db->getSchema()->refresh();
                \Yii::$app->getSession()->setFlash('success', 'Кэш таблиц успешно обновлен');
            }
        }


        $dataProvider = new ArrayDataProvider([
            'allModels' => \Yii::$app->db->getSchema()->getTableSchemas(),
            'sort' => [
                'attributes' => ['name', 'fullName'],
            ],
            'pagination' => [
                'pageSize' => 100,
            ],
        ]);


        $dbBackupDir = new Dir(BACKUP_DIR . "/db");
        if (!$dbBackupDir->isExist())
        {
            $dbBackupDir->make();
        }




        return $this->render('index', [
            'dataProvider'  => $dataProvider,
            'dbBackupDir'  => $dbBackupDir,
        ]);
    }

    public function actionBackup()
    {
        $rr = new RequestResponse();

        if ($rr->isRequestAjaxPost())
        {
            $dbBackupDir = new Dir(BACKUP_DIR . "/db");
            if (!$dbBackupDir->isExist())
            {
                $dbBackupDir->make();
            }

            $dsnData = $this->getDsnData();
            $username = \Yii::$app->db->username;
            $password = \Yii::$app->db->password;
            $dbname = ArrayHelper::getValue($dsnData, 'dbname');
            $host = ArrayHelper::getValue($dsnData, 'host');

            $file = $dbBackupDir->newFile(date('Y-m-d_H:i:s') . ".sql.gz");
            $filePath = $file->getPath();

            $cmd = "mysqldump -h{$host} -u {$username} -p{$password} {$dbname} | gzip > {$filePath}";

            ob_start();
            system($cmd);
            $result = ob_get_clean();


            $rr->success = true;
            $rr->message = "Копия создана успешно";
            $rr->data = [
                'result' => $result
            ];

            return $rr;
        }

        return $rr;
    }

    /**
     * @return array
     */
    public function getDsnData()
    {
        //TODO: it's bad tmp code
        $dsnData = [];

        $dsn = \Yii::$app->db->dsn;
        if ($strpos = strpos($dsn, ':'))
        {
            $dsn = substr($dsn, ($strpos + 1), strlen(\Yii::$app->db->dsn));
        };

        $dsnDataTmp = explode(';', $dsn);
        if ($dsnDataTmp)
        {
            foreach ($dsnDataTmp as $data)
            {
                $tmpData = explode("=", $data);
                $dsnData[$tmpData[0]] = $tmpData[1];
            }
        }

        $dsnData['username'] = \Yii::$app->db->username;
        $dsnData['password'] = \Yii::$app->db->password;

        return $dsnData;
    }

}