# Procurement Optimization API (Symfony 7.2.7)

REST API для расчета оптимального плана закупок товаров у поставщиков с учетом ограничений на кратность поставки.

## Технологический стек
- PHP 8.3
- Symfony 7.2.7
- Docker
- Nginx
- Xdebug (для разработки)
- JIT-компиляция PHP (для ускорения алгоритма)

## Требования
- Docker Desktop (Windows/Mac) или Docker Engine (Linux)
- Git (для клонирования репозитория)

## Быстрый запуск

1. Клонируйте репозиторий:
```bash
git clone https://github.com/Kernelix/test_task_vendor_selection_algorithm.git
cd test_task_vendor_selection_algorithm
```

2. Соберите и запустите контейнеры:
```bash 
docker compose up -d --build
```
3. Установите зависимости внутри PHP-контейнера:
```bash 
docker exec -it symfony-api composer install
```

## Пример запроса

Рассчитать оптимальный план закупки 76 единиц товара:

```bash
curl -X POST http://localhost:8080/plan \
  -H "Content-Type: application/json" \
  -d '{
    "N": 76,
    "suppliers": [
      {"id": 111, "count": 42, "price": 13, "pack": 1},
      {"id": 222, "count": 77, "price": 11, "pack": 10},
      {"id": 333, "count": 103, "price": 10, "pack": 50},
      {"id": 444, "count": 65, "price": 12, "pack": 5}
    ]
  }'
```

## Параметры запроса

| Поле       | Тип    | Описание                                                                 |
|------------|--------|--------------------------------------------------------------------------|
| n          | int    | Требуемое количество товара для закупки (потребность)                   |
| suppliers  | array  | Список поставщиков                                                      |

## Параметры поставщика

| Поле  | Тип    | Описание                                                                 |
|-------|--------|--------------------------------------------------------------------------|
| id    | int    | Уникальный идентификатор предложения                                    |
| count | int    | Количество товара на складе (>0)                                        |
| price | float  | Цена за единицу товара (>0)                                             |
| pack  | int    | Кратность поставки (минимальная партия, >0)                             |

## Ожидаемый ответ

### Пример успешного ответа
```json
[
  {"id": 111, "qty": 1},
  {"id": 222, "qty": 20},
  {"id": 333, "qty": 50},
  {"id": 444, "qty": 5}
]
```

## Тестирование

### Запуск юнит-тестов
```bash
docker exec -it symfony-api ./vendor/bin/phpunit
```

## Проверка системы

### Проверить работу JIT-компилятора
```bash
curl http://localhost:8080/jit-info.php