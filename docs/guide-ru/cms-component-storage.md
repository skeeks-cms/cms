Cms компонент хранилище (storage)
=====================

В cms по умолчанию есть компонент storage хранилище. Его можно конфигурировать от проекта к проекту. 
Этот компонент позволяет хранить файлы в зависимости от того, как он сконфигурирован, например, на удаленном сервере (по умолчанию локально).

1) Загрузка файла
-------

```php
$storageFile = Yii::$app->storage->upload($file, [
    "name"          => "Название файла в DB",
    "original_name" => "Оригинальное название файла"
]);
```

Вот параметры этого метода

```php
/**
 *
 * Загрузить файл в хранилище, добавить в базу, вернуть модель StorageFile
 *
 * @param UploadedFile|string $file    объект UploadedFile или rootPath до файла локально
 * @param array $data
 * @param null $clusterId
 * @return StorageFile
 * @throws Exception
 */
public function upload($file, $data = [], $clusterId = null)
{
.....
```

2) Привязка файла к модели
--------
```php
$model = Publication::findOne(['id' => 10]);
$storageFile->linkToModel($model);
```

3) Привязк файла к определенной группе файлов модели
--------------

```php
$model = Publication::findOne(['id' => 10]);
$group = $model->getFilesGroups()->getComponent("images");

if ($group)
{
    try
    {
        $group->attachFile($storageFile)->save();
    } catch (\yii\base\Exception $e)
    {
        echo $e->getMessage();
    }
}

```
