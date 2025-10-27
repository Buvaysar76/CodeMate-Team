# Balance Service (Laravel + PostgreSQL + Docker)

Сервисное приложение для работы с балансом пользователей через HTTP JSON API.

## Функционал

- Пополнение баланса (POST /api/deposit)
- Списание средств (POST /api/withdraw)
- Перевод между пользователями (POST /api/transfer)
- Получение баланса (GET /api/balance/{user_id})

Баланс не может быть отрицательным. Все операции выполняются в транзакциях.

---

## Технологии

- PHP 8.2+
- Laravel
- PostgreSQL
- Docker, Docker Compose
- Swagger UI (/api/docs)
- PHPUnit (Feature tests)

---

## Быстрый старт (Docker)

1. Скопируйте `.env.example` в `.env` при необходимости отредактируйте.

2. Поднимите контейнеры:

```bash
docker-compose up -d --build
```

3. Сгенерируйте ключ и выполните миграции/сидеры:

```bash
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --seed
```

4. Откройте в браузере:

```
http://localhost:8080
```

API доступно по `http://localhost:8080/api`.

---

## Эндпоинты

### POST /api/deposit
```json
{
  "user_id": 1,
  "amount": 500.00,
  "comment": "Пополнение через карту"
}
```
Коды: 200, 422

---

### POST /api/withdraw
```json
{
  "user_id": 1,
  "amount": 200.00,
  "comment": "Покупка подписки"
}
```
Коды: 200, 409, 422

---

### POST /api/transfer
```json
{
  "from_user_id": 1,
  "to_user_id": 2,
  "amount": 150.00,
  "comment": "Перевод другу"
}
```
Коды: 200, 409, 404, 422

---

### GET /api/balance/{user_id}
```json
{
  "user_id": 1,
  "balance": 350.00
}
```
Коды: 200, 404

---

## Swagger UI

Документация доступна по адресу:
```
http://localhost:8080/api/docs
```

---

## Тесты

```bash
docker-compose exec app php artisan test
```

Проект покрыт feature-тестами: депозит, снятие, перевод, получение баланса, негативные сценарии.

---

## Seeder'ы

```bash
docker-compose exec app php artisan db:seed
```

Создают тестовых пользователей.

---

### Требования выполнены ✔️

- Docker ✅
- PostgreSQL ✅
- транзакции ✅
- валидации ✅
- 4 эндпоинта ✅
- тесты ✅
- Swagger ✅
- сидеры ✅

Готово к проверке 🚀

