Cms компонент хранилище (storage)
=====================

В cms по умолчанию есть компонент storage хранилище. Его можно конфигурировать от проекта к проекту. 
Этот компонент позволяет хранить файлы в зависимости от того, как он сконфигурирован, например, на удаленном сервере (по умолчанию локально).

Примеры:
------

### 1) Загрузка файла

```php
$storageFile = Yii::$app->storage->upload($file, [
    "name"          => "Название файла в DB",
    "original_name" => "Оригинальное название файла"
    ......
]);


//$storageFile может цеплять с другим сущьностям у которых есть поведение HasFiles
```

Вот параметры этого метода

```php
/**
 *
 * Загрузить файл в хранилище, добавить в базу, вернуть модель StorageFile
 *
 * @param UploadedFile|string|File $file    объект UploadedFile или File или rootPath до файла локально или http:// путь к файлу (TODO:: доделать)
 * @param array $data                       данные для сохранения в базу
 * @param null $clusterId                   идентификатор кластера по умолчанию будет выбран первый из конфигурации
 * @return StorageFile
 * @throws Exception
 */
public function upload($file, $data = [], $clusterId = null)
{
.....
```

### 2) Привязка файла к модели

```php
//Publication должна иметь поведение HasFiles
$model = Publication::findOne(['id' => 10]);
$storageFile->linkToModel($model);
```

### 3) Привязка файла к определенной группе файлов модели

```php
//Publication должна иметь поведение HasFiles, именно настройка этого повдеения содержит описание групп и настройки каждой группы

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