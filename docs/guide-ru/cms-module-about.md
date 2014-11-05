Что такое модуль cms?
=====================
Cms модуль — это расширенны модуль Yii, который добавляет набор дополнительных свойств, для того чтобы вписаться в общую работу cms.

```
    /**
     * @var string название модуля
     */
    public $name = "";

    /**
     *  [
            "label"     => "Управление пользователями",
            "url"       => "cms/test-admin",
            "priority"  => 10,
        ],

        [
            "label"     => "Управление группами",
            "url"       => "cms/user-group-admin",
            "priority"  => 5,
        ]
     *
     * @var array пункуты меню админки
     */
    public $adminMenuItems      = [];
    /**
     * @var string название модуля в меню админки, опционально
     */
    public $adminMenuName       = "";
    /**
     * @var bool включить/отключить отображение блока меню в админке
     */
    public $adminMenuEnabled    = true;
    
    /**
     * @return array
     */
    protected function _descriptor()
    {
        return
        []
    }
    
```