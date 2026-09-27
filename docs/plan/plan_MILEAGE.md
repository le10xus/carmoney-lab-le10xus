# План MILEAGE: пробег больше 400 000 км → решение review

- Задача: `MILEAGE` (день 1, ДЗ.1) · Дата плана: 2026-09-24
- Правило: «пробег авто не больше 400 000 км, иначе решение review»; порог берём из `backend/config/rules.php`, не хардкодом
- Статус: черновик до спеки — `docs/spec/spec_MILEAGE.md` и `docs/intent/intent_MILEAGE.md` пока не созданы, план позже сверяется с ними

> Примечание по разведке: `docs/setup/code_map.md` на момент планирования в репозитории
> отсутствует (артефакт 1.2.2 ещё не создан). Карта кода восстановлена прямым чтением:
> поиск `mileage|пробег` по всему репозиторию (13 файлов: backend, tests, db, frontend, docs)
> плюс чтение всех затронутых классов. Scout не привлекался — покрытие полное.

## Как считается решение сейчас

- `ApplicationValidator::validate()` нормализует заявку: mileage обязателен, диапазон `0..max_mileage_km` (500 000). Отсутствие или `null` → `-1` → `ValidationException` (HTTP 422). Латентное поведение: пустая строка `''` приводится к `0` и молча проходит как «0 км».
- `AssessmentService::assess()`: валидация → `LtvCalculator` → `DecisionEngine::decide(float $ltv)` → approved_limit (approve = запрошенная сумма, иначе 0).
- `DecisionEngine` знает только LTV; пробег в решение сейчас не входит никак.
- Хранение: `vehicles.mileage_km INT UNSIGNED`, `decisions.decision ENUM('approve','review','reject')` — БД и репозиторий уже готовы к review, менять их не нужно.
- Диапазон 400 001–500 000 сегодня проходит валидацию и получает решение чисто по LTV — именно это поведение меняет задача.

## 1. Файлы

Всё, чего нет в списке, при реализации трогать нельзя.

| Файл | Что делаем |
|---|---|
| `backend/config/rules.php` | в секцию `vehicle` добавить ключ `review_above_mileage_km => 400000` с комментарием, отличающим его от валидационного `max_mileage_km` |
| `backend/src/Domain/MileageRule.php` | НОВЫЙ класс-правило «пробег выше порога → review», порог через конструктор |
| `backend/src/Domain/AssessmentService.php` | новый аргумент `MileageRule` в конструкторе; в `assess()` применять правило после `decide()`; обновить docblock класса (решение = LTV + правила) |
| `backend/src/AppFactory.php` | собрать `MileageRule` из `rules['vehicle']['review_above_mileage_km']` и передать в `AssessmentService` |
| `backend/src/Domain/ApplicationValidator.php` | строка 43: нечисловое или пустое `mileage` (в т.ч. `''`) считать невалидным — сейчас `''` молча превращается в 0 |
| `tests/Unit/MileageRuleTest.php` | НОВЫЙ тест: границы порога |
| `tests/Unit/AssessmentServiceTest.php` | сценарии 399999 / 400000 / 400001 и пустой пробег; в `payload()` добавить параметр mileage |
| `tests/Unit/ApplicationValidatorTest.php` | пустой пробег (`''` и отсутствие ключа) → ошибка валидации |

Не меняем: `DecisionEngine.php` (остаётся чистым правилом по LTV), `LtvCalculator.php`, `ApplicationController.php`, `ApplicationRepository.php`, `db/schema.sql`, `db/seed.sql`, `frontend/`.

## 2. Шаги

1. **rules.php** — добавить `'review_above_mileage_km' => 400000` рядом с `max_mileage_km` (500 000) с комментарием: `max_mileage_km` — граница валидации (выше → 422), новый ключ — граница решения (выше → review).
2. **MileageRule.php** — новый `final` класс, сигнатура:
   ```php
   final class MileageRule
   {
       public function __construct(private readonly int $reviewAboveKm) {}
       /** true, если пробег строго больше порога */
       public function requiresReview(int $mileage): bool;
   }
   ```
3. **MileageRuleTest.php** — закрыть границы из раздела «Тесты», прогнать `make test`.
4. **AssessmentService.php + AppFactory.php** — прокинуть `MileageRule` в конструктор (после `DecisionEngine`, перед `VehicleAge`); в `assess()`: если `requiresReview(input['mileage'])` — решение заменяется на `DecisionEngine::REVIEW`; `approved_limit` при review остаётся 0 по существующей логике.
5. **AssessmentServiceTest.php** — сценарии интеграции правила (399999 / 400000 / 400001, приоритет над LTV, пустой пробег), `make test`.
6. **ApplicationValidator.php** — строку `(int) ($payload['mileage'] ?? -1)` заменить на проверку «значение числовое», иначе невалидно (отсутствие / `null` / `''` / нечисло → ошибка `mileage`, как сейчас при выходе за диапазон).
7. **ApplicationValidatorTest.php** — тесты пустого пробега, полный прогон `make test` + `make lint`.

Порядок выбран так, чтобы каждый шаг закрывался тестами до перехода к следующему.

## 3. Тесты

Граничные значения — отдельной строкой каждое.

`tests/Unit/MileageRuleTest.php` (новый, порог 400000):

- 399999 — `requiresReview` = false
- 400000 — `requiresReview` = false («не больше 400 000» — граница допустима)
- 400001 — `requiresReview` = true
- 0 — false (нижняя граница диапазона)
- 500000 — true (верхняя граница валидации)

`tests/Unit/AssessmentServiceTest.php` (добавить; база — заявка с LTV 50 %, без правила даёт approve):

- 399999 — решение по LTV: approve, limit = запрошенной сумме
- 400000 — решение по LTV: approve, limit = запрошенной сумме
- 400001 — review, approved_limit = 0 (правило перебивает LTV-approve)
- 400001 при LTV 95 % (reject-зона) — review по плану; приоритет спорен — см. вопрос 1
- пустой пробег: ключ `mileage` отсутствует — `ValidationException` с ошибкой `mileage`
- пустой пробег: `mileage = null` — `ValidationException`

`tests/Unit/ApplicationValidatorTest.php` (добавить):

- `mileage = ''` — `ValidationException`, в ошибках есть ключ `mileage` (после шага 6)
- `mileage` отсутствует — `ValidationException`, в ошибках есть ключ `mileage`

Регрессия: существующие `DecisionEngineTest` (не меняется), `AssessmentServiceTest` (payload 96 000 км — не задет) и `ApplicationValidatorTest` должны остаться зелёными без правок.

## 4. Риски

- **Взаимодействие с `max_mileage_km` (главный риск).** Появляются две близкие границы: 400 000 (решение) и 500 000 (валидация). Диапазон 400 001–500 000, который сегодня получает решение по LTV, начнёт возвращать review — меняется поведение существующего API. Пробег 500 001+ по-прежнему даёт 422, а не review. Границы можно перепутать при поддержке; возможно, заказчик захочет выровнять (вопрос 2).
- **Приоритет правил.** При пробеге >400 000 и LTV >85 % план даёт review, хотя LTV-правило говорит reject («иначе решение review» читается буквально). Если бизнес считает reject важнее — семантику и тест «400001 при LTV 95 %» придётся поменять (вопрос 1).
- **Ужесточение валидатора (шаг 6).** `''` в mileage раньше молча превращалась в 0 км и проходила; теперь — 422. Прямые вызовы API с пустой строкой сломаются (у формы поле `required`, фронтенд пустое не шлёт — риск низкий, только интеграции).
- **approved_limit.** Заявок с review станет больше, у всех лимит 0 — операторы увидят больше нулевых лимитов; логика не меняется, меняется распределение решений.
- **Хранение.** ENUM `decisions.decision` уже содержит `review`, `mileage_km INT UNSIGNED` вмещает 400 001 — миграций не требуется; репозиторий решение уже сохраняет как есть.
- **Старые заявки.** Решения фиксируются при создании заявки; пересчёта сохранённых решений нет — правило действует только на новые заявки.
- **Существующие тесты.** Payload-хелперы используют 96 000 / 84 000 км — границы не задеты; риск регрессии низкий, но после шага 6 обязателен полный `make test`.

Не входит (вне объёма задачи):

- сверка пробега с внешним справочником по VIN и показаниями одометра, детекция «клиент называет меньше» (материал `docs/sources/CASE-08.md`, `client_note.md`);
- скидки за пробег и возраст в оценке залоговой стоимости;
- LOAN-12 — лимит суммы по `ltv_by_age`;
- поле «причина review» в ответе API;
- миграции БД, правки frontend, пересчёт старых решений.

## Вопросы, на которые без заказчика не ответить

1. Пробег >400 000 и одновременно LTV >85 % (reject по LTV) — что приоритетнее: review или reject? План: review, по буквальному чтению правила.
2. Оставить ли `max_mileage_km = 500000` как отдельную валидационную границу (500 001+ → 422), выровнять её с новым порогом или убрать? Сейчас образуется «окно» 400 001–500 000 → review.
3. Пустой или неизвестный пробег — это ошибка валидации (422, как в плане) или «неизвестно» → решение review?
4. Нужна ли оператору причина review в ответе `/api/ltv` и `/api/applications` (сейчас поле причин решения не возвращается)?
5. Применять ли правило к уже сохранённым заявкам (пересчёт старых решений) или только к новым? План: только к новым.
