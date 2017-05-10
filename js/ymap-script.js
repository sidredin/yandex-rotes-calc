var myMap, route, ch =1;

var markers = [];

var point = []; 

ymaps.ready(init);

function init () {

    myMap = new ymaps.Map('map', {
        center: [55.75899613, 37.62005074], 
        zoom: 5
    });

    ymaps.route([
        'Кронштадт, Якорная площадь',
        'Санкт-Петербург, Финляндский вокзал'// или [59.956084, 30.356849]
    ]).then(
        function (route) {
            myMap.geoObjects.add(route);
        },
        function (error) {
            alert("Возникла ошибка: " + error.message);
        }
    );        

}