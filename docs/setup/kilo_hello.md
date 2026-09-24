# Kilo: hello

готов

1) Сервис: учебный проект carmoney-lab — предварительная оценка заявки на заём под ПТС (принимает заявку, считает LTV, возвращает `approve` / `review` / `reject`); данные синтетические.
2) Makefile: `make up` (поднять сервис и базу), `make down`, `make test` (PHPUnit), `make lint` (php -l), `make seed`, а также `ps`, `logs`, `install`, `help`; docker-compose.yml описывает сервисы backend (PHP-сервер на порту `${APP_PORT:-8080}`) и db (mysql:8.0 на порту `${DB_PORT:-3307}`), команд запуска/проверки в нём самом не нашёл.
3) Решение approve / review / reject считается в `backend/src/Domain/` — класс `DecisionEngine` (`backend/src/Domain/DecisionEngine.php`).

модель: training-2026-09-glm-5.3