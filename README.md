# autoxml img
Сервис для работы с изображениями

## Установка

```bash
composer install
php artisan migrate
php artisan db:seed

php artisan serve
```

## Аутентификация

basic http authentication (base64(username:password))

Учетная запись (создается сидером):
- admin@test.loc
- password

ссылка на Postman коллекцию для локальной отладки(127.0.0.1:8000):
https://www.getpostman.com/collections/b416f25feef63ee3a900

ссылка на Postman коллекцию для отладки прода (https://cdn.autoxml.4px.tech):
https://www.getpostman.com/collections/882afd85704b59a2e940

## Api
POST api/image/block - блокировать изображение по ссылке

params:
- url (string) - url изображения для блокировки
___
POST api/image/load - загрузить изображение по ссылке или списку ссылок

params:
- url (string|array) - url (или список url-ов) изображения для загрузки
- mark (string) -  Марка
- model (string) -  Модель
- body (string) -  Кузов
- generation (string) -  Поколение
- complectation (string) -  Комлектация
- color (string) -  Цвет от производителя или русское название цвета
- body_group (string) -  Порядковый номер группы изображений

response example:
```
{
    "image_list": [
        {
            "hash": "f2b636767482f872746def12a4446c71e2ff568263ce2cc45d7c689396cb4369",
            "url": "https://www.iphones.ru/wp-content/uploads/2017/03/mb_gt.jpg",
            "is_blocked": false,
            "src": "http://127.0.0.1:8000/image/marka/model/default/default/complectation/ser_bur_cherniy/body_group/5ca5cf054550d.jpg",
            "id": 27,
            "thumb": "http://127.0.0.1:8000/image/marka/model/default/default/complectation/ser_bur_cherniy/body_group/thumb/5ca5cf054550d.jpg"
        }
    ]
}
```
___
POST api/image/by-hash - запросить данные об изображениях по хешу (sha256) или списку хешей

params:
- hash (string|array) - hash (или список hash-ей)
___
POST api/image/by-url - запросить данные об изображениях по url или списку url-ов

params:
- url (string|array) - url (или список url-ов)