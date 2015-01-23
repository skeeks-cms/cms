/*!
 *
 * Ynadex map widget
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 22.01.2015
 * @since 1.0.0
 */

(function(sx, $, _)
{
    sx.createNamespace('classes.widgets', sx);

    /**
     *
     */
    sx.classes.widgets.YandexMap = sx.classes.Widget.extend({

        _init: function()
        {
            this.ymaps  = null;
            this.myPlacemark  = null;
            var self    = this;

            ymaps.ready(function()
            {
                self._initMap();

                self.onDomReady(function()
                {
                    self.trigger("ready");
                });
            });

            this.bind('select', function(e, data)
            {
                self.setFieldsValues(data);
            });

            this.bind('ready', function(e, data)
            {
                self.initStartMap();
            });
        },


        initStartMap: function()
        {
            if (this.JLng.val() && this.JLat.val())
            {
                this.setCoordinates([this.JLat.val(), this.JLng.val()]);
                this.ymaps.setCenter([this.JLat.val(), this.JLng.val()]);
            }
        },




        setFieldsValues: function(data)
        {
            this.JAddress.val(data.address);
            this.JLat.val(data.coords[0]);
            this.JLng.val(data.coords[1]);
        },

        /**
         * @private
         */
        _initMap: function()
        {
            var self = this;


            this.ymaps = new ymaps.Map(self.get('idMap'), self.get('yandex'));
            this._initControllSearch();
            this._initScenario();
        },


        _initControllSearch: function()
        {
            var self = this;
            this.SearchControl = new ymaps.control.SearchControl({
                options: {
                    noPlacemark: true
                }
            });
            this.ymaps.controls.add(
                this.SearchControl
            );

        // Результаты поиска будем помещать в коллекцию.
            this.mySearchResults = new ymaps.GeoObjectCollection(null, {
                hintContentLayout: ymaps.templateLayoutFactory.createClass('$[properties.name]'),
                draggable: true
            });
            //this.ymaps.geoObjects.add(this.mySearchResults);

        // При клике по найденному объекту метка становится красной.
            this.mySearchResults.events.add('click', function (e) {
                e.get('target').options.set('preset', 'islands#redIcon');
            });

             // Выбранный результат помещаем в коллекцию.
            self.SearchControl.events.add('resultselect', function (e) {
                var index = e.get('index');
                self.SearchControl.getResult(index).then(function (res) {

                    self.setCoordinates(res.geometry.getCoordinates());

                   self.mySearchResults.add(res);
                });
            }).add('submit', function () {
                    self.mySearchResults.removeAll();
                });




        },

        _initScenario: function()
        {
            var self = this;
            // Слушаем клик на карте
            this.ymaps.events.add('click', function (e) {
                var coords = e.get('coords');
                self.setCoordinates(coords);
            });

            return this;
        },

        setCoordinates: function(coords)
        {
            var self = this;
            // Если метка уже создана – просто передвигаем ее
            if (self.myPlacemark) {
                self.myPlacemark.geometry.setCoordinates(coords);
            }
            // Если нет – создаем.
            else {
                self.myPlacemark = self.createPlacemark(coords);
                self.ymaps.geoObjects.add(self.myPlacemark);
                // Слушаем событие окончания перетаскивания на метке.
                self.myPlacemark.events.add('dragend', function () {
                    self.getAddress(self.myPlacemark.geometry.getCoordinates());
                });
            }

            this.ymaps.panTo(coords);
            //this.ymaps.setCenter(coords);

            self.getAddress(coords);
        },

        createPlacemark: function(coords)
        {
            var self = this;
             return new ymaps.Placemark(coords, {
                iconContent: 'поиск...'
            }, {
                preset: 'islands#violetStretchyIcon',
                draggable: true
            });
        },

        getAddress: function(coords)
        {
            var self = this;
             self.myPlacemark.properties.set('iconContent', 'поиск...');
             ymaps.geocode(coords).then(function (res) {
                var firstGeoObject = res.geoObjects.get(0);

                self.trigger('select', {
                    'object' : firstGeoObject,
                    'address' : firstGeoObject.properties.get('text'),
                    'address_name' : firstGeoObject.properties.get('name'),
                    'coords' : coords,
                });

                self.myPlacemark.properties
                    .set({
                        iconContent: firstGeoObject.properties.get('name'),
                        balloonContent: firstGeoObject.properties.get('text')
                    });
            });
        },

        _onDomReady: function()
        {
            this.JAddress   = $("#" + this.get('fieldNameAddress'));
            this.JLng       = $("#" + this.get('fieldNameLng'));
            this.JLat       = $("#" + this.get('fieldNameLat'));



        },

        _onWindowReady: function()
        {}
    });


})(sx, sx.$, sx._);