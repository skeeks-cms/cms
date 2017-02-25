======================
Shop (skeeks/cms-shop)
======================

Полноценный интернет магазин.

Виджеты
-------

Этапы оформления заказа
~~~~~~~~~~~~~~~~~~~~~~~

Потребуется установка ``skeeks/cms-shop-cart-steps-widget``

.. code-block:: php

   <?= \skeeks\cms\shopCartStepsWidget\ShopCartStepsWidget::widget(); ?>


Товары в корзине
~~~~~~~~~~~~~~~~

Потребуется установка ``skeeks/cms-shop-cart-items-widget``

.. code-block:: php

   <?= \skeeks\cms\shopCartItemsWidget\ShopCartItemsListWidget::widget([
      'dataProvider' => new \yii\data\ActiveDataProvider([
          'query' => \Yii::$app->shop->shopFuser->getShopBaskets(),
          'pagination' =>
          [
              'defaultPageSize' => 100,
              'pageSizeLimit' => [1, 100],
          ]
      ]),

   ]); ?>



Оформление заказа
~~~~~~~~~~~~~~~~~

Потребуется установка ``skeeks/cms-shop-checkout-widget``

.. code-block:: php

   <? $checkout = \skeeks\cms\shopCheckout\ShopCheckoutWidget::begin([]); ?>
   <? \skeeks\cms\shopCheckout\ShopCheckoutWidget::end(); ?>


Скидочные купоны
~~~~~~~~~~~~~~~~

Потребуется установка ``skeeks/cms-shop-discount-coupon-widget``

.. code-block:: php

   <?= \skeeks\cms\shopDiscountCoupon\ShopDiscountCouponWidget::widget(); ?>



Примеры
-------

Страница полной корзины
~~~~~~~~~~~~~~~~~~~~~~~

Шаблон находится по адресу ``default\modules\shop\cart\cart.php``

.. code-block:: php

   <?
      \frontend\assets\CartAsset::register($this);
      \skeeks\cms\shop\widgets\ShopGlobalWidget::widget();
      $this->registerJs(<<<JS
          (function(sx, $, _)
          {
              new sx.classes.shop.FullCart(sx.Shop, 'sx-cart-full');
          })(sx, sx.$, sx._);
      JS
      );
   ?>
   <!--=== Content Part ===-->
   <section class="sx-cart-layout bg-printair">
       <div class="row">
           <div class="container sx-border-block">
               <? \skeeks\cms\modules\admin\widgets\Pjax::begin([
                   'id' => 'sx-cart-full',
               ]) ?>

               <? if (\Yii::$app->shop->shopFuser->isEmpty()) : ?>
                   <!-- EMPTY CART -->
                   <div class="panel panel-default">
                       <div class="panel-body">
                           <strong>Ваша корзина пуста!</strong><br/>
                           В вашей корзине нет покупок.<br/>
                           Кликните <a href="/" data-pjax="0">сюда</a> для продолжения покупок. <br/>
                           <!--<span class="label label-warning">this is just an empty cart example</span>-->
                       </div>
                   </div>
                   <!-- /EMPTY CART -->
               <? else: ?>
                   <?= \skeeks\cms\shopCartStepsWidget\ShopCartStepsWidget::widget(); ?>
                   <hr/>
                   <!-- LEFT -->
                   <div class="col-lg-9 col-sm-8">
                       <?= \skeeks\cms\shopCartItemsWidget\ShopCartItemsListWidget::widget([
                           'dataProvider' => new \yii\data\ActiveDataProvider([
                               'query' => \Yii::$app->shop->shopFuser->getShopBaskets(),
                               'pagination' =>
                               [
                                   'defaultPageSize' => 100,
                                   'pageSizeLimit' => [1, 100],
                               ]
                           ]),

                       ]); ?>
                   </div>
                   <!-- RIGHT -->
                   <div class="col-lg-3 col-sm-4">
                       <? $url = \yii\helpers\Url::to(['/shop/cart/checkout']); ?>
                       <?= $this->render("_result", [
                           'submit' => <<<HTML
       <a href="{$url}" class="btn btn-primary btn-lg btn-block size-15" data-pjax="0">
           <i class="fa fa-mail-forward"></i> Оформить
       </a>
   HTML
                       ]); ?>
                   </div>
               <? endif; ?>

               <? \skeeks\cms\modules\admin\widgets\Pjax::end() ?>
           </div>
       </div>
   </section>


Страница оформления заказа
~~~~~~~~~~~~~~~~~~~~~~~~~~

Шаблон находится по адресу ``default\modules\shop\cart\checkout.php``

.. code-block:: php

   <?
      \frontend\assets\CartAsset::register($this);
      \skeeks\cms\shop\widgets\ShopGlobalWidget::widget();

      $this->registerJs(<<<JS
          (function(sx, $, _)
          {
              new sx.classes.shop.FullCart(sx.Shop, 'sx-cart-full');
          })(sx, sx.$, sx._);
      JS
      );
   ?>


   <!--=== Content Part ===-->
   <section class="sx-cart-layout bg-printair">
       <div class="row">
           <div class="container sx-border-block">
               <? \skeeks\cms\modules\admin\widgets\Pjax::begin([
                   'id'                    => 'sx-cart-full',
               ]) ?>


               <? if (\Yii::$app->shop->shopFuser->isEmpty()) : ?>
                   <!-- EMPTY CART -->
                       <div class="panel panel-default">
                       <div class="panel-body">
                           <strong>Ваша корзина пуста!</strong><br />
                           В вашей корзине нет покупок.<br />
                           Кликните <a href="/" data-pjax="0">сюда</a> для продолжения покупок. <br />
                           <!--<span class="label label-warning">this is just an empty cart example</span>-->
                       </div>
                   </div>
                   <!-- /EMPTY CART -->
               <? else: ?>

                   <?= \skeeks\cms\shopCartStepsWidget\ShopCartStepsWidget::widget(); ?>

                   <hr />

                   <!-- LEFT -->
                   <div class="col-lg-9 col-sm-8">

                       <!-- CART -->

                       <!-- cart content -->
                       <div id="cartContent">

       <?
       $this->registerCss(<<<CSS
       .radio input[type=radio]
       {
           left: 0px;
           margin-left: 0px;
       }
       .checkbox label, .radio label
       {
           padding-left: 0px;
       }
   CSS
       );
       ?>
                           <? $checkout = \skeeks\cms\shopCheckout\ShopCheckoutWidget::begin([
                               'btnSubmitWrapperOptions' =>
                               [
                                   'style' => 'display: none;'
                               ]
                           ]); ?>
                           <? \skeeks\cms\shopCheckout\ShopCheckoutWidget::end(); ?>

                           <div class="clearfix"></div>
                       </div>
                       <!-- /cart content -->

                       <!-- /CART -->

                   </div>


                   <!-- RIGHT -->
                   <div class="col-lg-3 col-sm-4">

                       <? $url = \yii\helpers\Url::to(['/shop/cart/payment']) ; ?>
                       <?= $this->render("_result", [
                           'submit' => <<<HTML
       <a href="#" onclick="$('#{$checkout->formId}').submit(); return false;" class="btn btn-primary btn-lg btn-block size-15" data-pjax="0">
           <i class="fa fa-mail-forward"></i> Оформить
       </a>
   HTML

                       ]); ?>

                   </div>
               <? endif; ?>

               <? \skeeks\cms\modules\admin\widgets\Pjax::end() ?>
           </div>
       </div>
   </section>


Финальная страница заказа
~~~~~~~~~~~~~~~~~~~~~~~~~

Шаблон находится по адресу ``default\modules\shop\order\finish.php``

.. code-block:: php

   <section>
       <div class="row">
           <div class="col-sm-12">

   <?= \skeeks\cms\shopCartStepsWidget\ShopCartStepsWidget::widget(); ?>
   <hr />
   <div class="box-light">
       <!--=== Content Part ===-->
       <div class="row">
           <div class="col-lg-12 col-md-10">
               <h4>Заказ №<?= $model->id; ?> от <?= \Yii::$app->formatter->asDatetime($model->created_at); ?> </h4>

               <div class="table-responsive">
                   <?= \yii\widgets\DetailView::widget([
                       'model' => $model,
                       'template' => "<tr><th>{label}</th><td style='width:50%;'>{value}</td></tr>",
                       'attributes' => [
                           /*[                      // the owner name of the model
                               'label' => 'Номер заказа',
                               'format' => 'raw',
                               'value' => $model->id,
                           ],*/
                           /*[                      // the owner name of the model
                               'label' => 'Создан',
                               'format' => 'raw',
                               'value' => \Yii::$app->formatter->asDatetime($model->created_at),
                           ],*/
                           [                      // the owner name of the model
                               'label' => 'Сумма заказа',
                               'format' => 'raw',
                               'value' => \Yii::$app->money->convertAndFormat($model->moneyOriginal),
                           ],
                           [                      // the owner name of the model
                               'label' => 'Способ оплаты',
                               'format' => 'raw',
                               'value' => $model->paySystem->name,
                           ],
                           [
                               'label' => 'Доставка',
                               'format' => 'raw',
                               'value' => 'Курьер',
                           ],
                           [                      // the owner name of the model
                               'label' => 'Статус',
                               'format' => 'raw',
                               'value' => Html::tag('span', $model->status->name, ['style' => 'color: ' . $model->status->color]),
                           ],
                           [                      // the owner name of the model
                               'label' => 'Оплата',
                               'format' => 'raw',
                               'value' => $model->payed == 'Y' ? "<span style='color: green;'>Оплачен</span>" : "<span style='color: red;'>Не оплчаен</span>",
                           ],
                           [                      // the owner name of the model
                               'attribute' => 'Заказ отменен',
                               'label' => 'Заказ отменен',
                               'format' => 'raw',
                               'value' => $model->reason_canceled,
                               'visible' => $model->canceled == 'Y',
                           ],
                       ]
                   ]) ?>
               </div>
               <h4>Данные покупателя: </h4>

               <div class="table-responsive">
                   <?= \yii\widgets\DetailView::widget([
                       'model' => $model->buyer->relatedPropertiesModel,
                       'template' => "<tr><th style='width: 50%; '>{label}</th><td style='width:50%;'>{value}</td></tr>",
                       'attributes' => array_keys($model->buyer->relatedPropertiesModel->toArray())
                   ]) ?>
               </div>
               <h4>Содержимое заказа: </h4>
               <!-- cart content -->
               <?= \skeeks\cms\shopCartItemsWidget\ShopCartItemsListWidget::widget([
                   'dataProvider' => new \yii\data\ActiveDataProvider([
                       'query' => $model->getShopBaskets(),
                       'pagination' =>
                       [
                           'defaultPageSize' => 100,
                           'pageSizeLimit' => [1, 100],
                       ],
                   ]),
                   'footerView'    => false,
                   'itemView'      => '@skeeks/cms/shopCartItemsWidget/views/items-list-order-item',
               ]); ?>
               <!-- /cart content -->
               <div class="toggle-transparent toggle-bordered-full clearfix">
                   <div class="toggle active" style="display: block;">
                       <div class="toggle-content" style="display: block;">

                               <span class="clearfix">
                                   <span
                                       class="pull-right"><?= \Yii::$app->money->convertAndFormat($model->moneyOriginal); ?></span>
                                   <strong class="pull-left">Товаров:</strong>
                               </span>
                           <? if ($model->moneyDiscount->getValue() > 0) : ?>
                               <span class="clearfix">
                                       <span
                                           class="pull-right"><?= \Yii::$app->money->convertAndFormat($model->moneyDiscount); ?></span>
                                       <span class="pull-left">Скидка:</span>
                                   </span>
                           <? endif; ?>

                           <? if ($model->moneyDelivery->getValue() > 0) : ?>
                               <span class="clearfix">
                                       <span
                                           class="pull-right"><?= \Yii::$app->money->convertAndFormat($model->moneyDelivery); ?></span>
                                       <span class="pull-left">Доставка:</span>
                                   </span>
                           <? endif; ?>

                           <? if ($model->moneyVat->getValue() > 0) : ?>
                               <span class="clearfix">
                                       <span
                                           class="pull-right"><?= \Yii::$app->money->convertAndFormat($model->moneyVat); ?></span>
                                       <span class="pull-left">Налог:</span>
                                   </span>
                           <? endif; ?>

                           <? if ($model->weight > 0) : ?>
                               <span class="clearfix">
                                       <span class="pull-right"><?= $model->weight; ?> г.</span>
                                       <span class="pull-left">Вес:</span>
                                   </span>
                           <? endif; ?>
                           <hr/>

                               <span class="clearfix">
                                   <span
                                       class="pull-right size-20"><?= \Yii::$app->money->convertAndFormat($model->money); ?></span>
                                   <strong class="pull-left">ИТОГ:</strong>
                               </span>
                           <hr/>
                           <? if ($model->allow_payment == \skeeks\cms\components\Cms::BOOL_Y) : ?>
                               <? if ($model->paySystem->paySystemHandler && $model->payed == 'N') : ?>
                                   <?= Html::a("Оплатить", \yii\helpers\Url::to(['/shop/order/finish-pay', 'key' => $model->key]), [
                                       'class' => 'btn btn-lg btn-primary'
                                   ]); ?>
                               <? else : ?>

                               <? endif; ?>
                           <? else : ?>
                               <? if ($model->paySystem->paySystemHandler) : ?>
                                   В настоящий момент, заказ находится в стадии проверки и сборки. Его можно будет оплатить позже.
                               <? endif; ?>
                           <? endif; ?>
                       </div>
                   </div>
               </div>
           </div>
       </div>
   </div>



       </div>
   </div>
   </section>




Содержимое шаблона ``default\modules\shop\cart\_result.php``


.. code-block:: php

   <div class="toggle-transparent toggle-bordered-full clearfix">

       <div class="toggle nomargin-top">
           <label>Купон</label>

           <div class="toggle-content" style="display: none;">
               <?= \skeeks\cms\shopDiscountCoupon\ShopDiscountCouponWidget::widget(); ?>
           </div>
       </div>
   </div>

   <div class="toggle-transparent toggle-bordered-full clearfix">
       <div class="toggle active" style="display: block;">
           <div class="toggle-content" style="display: block;">

               <span class="clearfix">
                   <span class="pull-right"><?= \Yii::$app->money->convertAndFormat(\Yii::$app->shop->shopFuser->moneyOriginal); ?></span>
                   <strong class="pull-left">Товаров:</strong>
               </span>
               <? if (\Yii::$app->shop->shopFuser->moneyDiscount->getValue() > 0) : ?>
                   <span class="clearfix">
                       <span class="pull-right"><?= \Yii::$app->money->convertAndFormat(\Yii::$app->shop->shopFuser->moneyDiscount); ?></span>
                       <span class="pull-left">Скидка:</span>
                   </span>
               <? endif; ?>

               <? if (\Yii::$app->shop->shopFuser->moneyDelivery->getValue() > 0) : ?>
                   <span class="clearfix">
                       <span class="pull-right"><?= \Yii::$app->money->convertAndFormat(\Yii::$app->shop->shopFuser->moneyDelivery); ?></span>
                       <span class="pull-left">Доставка:</span>
                   </span>
               <? endif; ?>

               <? if (\Yii::$app->shop->shopFuser->moneyVat->getValue() > 0) : ?>
                   <span class="clearfix">
                       <span class="pull-right"><?= \Yii::$app->money->convertAndFormat(\Yii::$app->shop->shopFuser->moneyVat); ?></span>
                       <span class="pull-left">Налог:</span>
                   </span>
               <? endif; ?>

               <? if (\Yii::$app->shop->shopFuser->weight > 0) : ?>
                   <span class="clearfix">
                       <span class="pull-right"><?= \Yii::$app->shop->shopFuser->weight; ?> г.</span>
                       <span class="pull-left">Вес:</span>
                   </span>
               <? endif; ?>

               <hr />

               <span class="clearfix">
                   <span class="pull-right size-20"><?= \Yii::$app->money->convertAndFormat(\Yii::$app->shop->shopFuser->money); ?></span>
                   <strong class="pull-left">ИТОГ:</strong>
               </span>

               <hr />

               <?= $submit; ?>
           </div>
       </div>
   </div>