## Внешний API

Для интеграции с внешними системами (например, системами поставщиков) реализована специальная точка входа.

### Эндпоинт

Все внешние запросы должны отправляться методом `POST` на:
`https://ваш-домен.ru/server/core/_ajaxListener.class.php`

### Структура запроса

Запрос должен быть отправлен в формате `x-www-form-urlencoded` или `form-data` и содержать следующие поля:

-   `classFile`: `app.class`
-   `class`: `App`
-   `method`: `handleExternalApi`
-   `data`: JSON-строка с основными данными.

### Пример использования (cURL)

```bash
curl -X POST https://ваш-домен.ru/server/core/_ajaxListener.class.php \
-d "classFile=app.class" \
-d "class=App" \
-d "method=handleExternalApi" \
-d 'data={"supplierTin":"1234567890","skus":["SKU001","SKU002"]}'
```