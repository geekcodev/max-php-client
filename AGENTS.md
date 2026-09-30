# AGENTS.md

> Проектный контекст и рабочие правила для разработчиков и ИИ-агентов (включая opencode).
> Читай этот файл **целиком** в начале работы — он задаёт обязательный процесс проверок. Справочник контрактов
> **API MAX** (спека, эндпоинты, enums, DTO, лимиты) вынесен в `docs/api-reference.md` — читай его перед правками
> DTO, enum, транспорта, вебхуков и вызовов API. Пользовательскую документацию (быстрый старт, примеры, интеграция
> в фреймворки) смотри в `README.md`.

## 1. О проекте

- **Что это.** Публичная PHP-библиотека **`geekcodev/max-php-client`** — framework-agnostic клиент для **MAX Messenger
  Bot API** (мессенджер MAX, https://max.ru). Цель — production-grade ядро, которое переиспользуется в интеграционных
  модулях: фреймворк-мосты (Laravel, Symfony) делаются **отдельными пакетами** поверх этого клиента и от ядра не
  зависят.
- **Статус.** Последний выпущенный релиз — **v1.1.6** (тег `v1.1.6`, Packagist `geekcodev/max-php-client`).
  Релиз v1.1.7 **перевыпускается под тем же номером**: первый выход смержен в `main` (`ec9ab69`, PR #20), но тег
  `v1.1.7` удалён с origin и локально, Packagist v1.1.7 не публиковался. Код не откатывался, релиз возобновили ради
  новых изменений и пересоздадут тег с двумя коммитами. Версию всегда уточняй по
  `git ls-remote --tags origin | tail -1` (теги на сервере —
  источник истины, локальные могут остаться от удалённых) и по `git log --oneline -10`, а не по этому файлу: номер
  релиза здесь протухает медленнее, чем меняются теги. Перед финалом релиза приводи его в соответствие с последним
  тегом (см. раздел 9). В `.agents/plans/` лежит план серверной проверки телефона из MAX Bridge, ждёт реального
  захвата `{ phone, authDate, hash, userId }`.
- **Лицензия.** MIT (c) 2026 Evgeny Semenov.
- **Язык.** Рабочий язык общения с пользователем, все md-файлы, описания и журнал — **русский**.

## 2. Ветки, git и коммиты

- `main` — стабильная, соответствует выпущенным релизам. `dev` — рабочая ветка; изменения сначала здесь.
- Релизный процесс: PR `dev → main` → тег `vX.Y.Z` → GitHub Release → автопубликация на Packagist. Тег ставится только
  на `main`.
- `.env` — untracked (в `.gitignore`): хранит `MAX_API_TOKEN`, `MAX_WEBHOOK_SECRET`. **Никогда не коммитить** и не
  логировать значения.
- Коммиты и push делает пользователь (в окружении нет credential.helper/gh) — **не коммить и не пушить без явного
  запроса**.
- **Различай «текст коммита» и «коммит».** Если пользователь просит «напиши текст/сообщение коммита» — верни краткий
  HEAD (одна строка subject) на английском языке по [Conventional Commits](https://www.conventionalcommits.org/):
  `тип` (`feat`, `fix`, `refactor`, `style`, `docs`, `test`, `chore`, `ci`, ...) + `scope` + краткое описание, без тела,
  **без** выполнения `git commit`. Если просит «закоммить» / «сделай коммит» — тогда выполняй реальный `git commit` с
  таким коротким сообщением. Никогда не коммить по умолчанию и не делай `git add .` без проверки `git status` и
  `git diff`.
- Перед завершением релиза проверь, что нет мусора в рабочем дереве: `git status --short` должен быть чистым. Заведённые
  рабочие каталоги (`.agents/`) в `.gitignore` — это ожидаемо, а не мусор.

## 3. Правила для ИИ-агентов

1. В начале работы прочитай `AGENTS.md`; перед правками API-контрактов — `docs/api-reference.md`.
2. **Не коммить и не пушить без явного запроса пользователя.**
3. Перед завершением любой задачи, менявшей код, прогони обязательный Gate (раздел 8) целиком. Результаты не подменяй;
   недоступный шаг честно указывай в отчёте, а не пропускай молча. Отдельно пройди **шаг 7 — сверку документации**:
   цифры из последнего прогона, правдивые отрицания, актуальный статус релиза, отсутствие висячих ссылок.
4. Не выдумывай сигнатуры и эндпоинты: сверяйся с `docs/api-reference.md` или спецификацией `max-openapi`
   (https://github.com/geekcodev/max-openapi). Прод-поведение важнее спеки в случаях, перечисленных в
   `docs/api-reference.md`, раздел 9.
5. Если для задачи чего-то не хватает (токен, сеть, контейнер) — скажи об этом, а не упрощай задачу молча.
6. Ответы — краткие и по делу; в коде — без лишних комментариев.
7. **Расхождение, найденное в интеграционном проекте, — регрессия этого пакета.** Зафиксируй его как отдельную задачу
   (тест + фикс + релиз), а не как локальный обход в стороннем проекте. Три таких расхождения уже найдены интеграторами
   (v1.1.1 — CRLF, v1.1.2 — base64, v1.1.3 — raw `vcf_info`); схема — `docs/api-reference.md`, раздел 9.
8. **Веди `.agents`** (раздел 5): после каждой содержательной сессии обнови `journals/JOURNAL.md` и добавь файл сессии;
   многошаговые задачи фиксируй в `plans/`; правки релиза — в `release/`. После выхода релиза обнови статусы: release
   notes помечается выпущенным, план — «завершён» либо «ждёт данных» с указанием, чего именно не хватает.
9. **Соблюдай OWASP Top 10** (раздел 7) при написании кода: секреты сравниваются только `hash_equals`, url из вебхуков и
   подписок проверяются на `https://` и домен, вход ограничивается по размерам и типам, секреты и payload не попадают в
   логи.
10. **Язык — русский.** Все md-файлы, комментарии в коде, описания, планы и журнал пиши по-русски, информативно, без
    смешения языков и без декоративных артефактов (значков, условных обозначений, символов непонятного происхождения).
    Допустимы только русский и английский. Идентификаторы в коде, имена API-полей и термины спеки остаются как есть.

## 4. Структура репозитория

```
src/                          клиент, DTO, enums, исключения, сервисные компоненты
tests/                        PHPUnit: unit-тесты + Integration/SmokeTest (группа integration)
examples/                     рабочие примеры ботов + run.sh (docker-запуск без локального PHP)
docs/api-reference.md         справочник API MAX: спека, эндпоинты, enums, DTO, лимиты, вебхуки
scripts/check-coverage.php    порог покрытия строк (по умолчанию 95%)
.github/workflows/ci.yml      CI: quality + integration
Dockerfile                    PHP 8.4, опциональный Xdebug (ARG INSTALL_XDEBUG=false)
docker-compose.yml            сервис app, user 1000:1000, volume ./, .env пробрасывается
composer.json                 PSR-4, PHP ^8.4
phpunit.xml                   failOnRisky/failOnWarning; группа integration исключена по умолчанию
phpstan.neon                  level max
.php-cs-fixer.dist.php        PSR-12
.env.example                  MAX_API_TOKEN, MAX_WEBHOOK_SECRET (эталон имён переменных)
```

`composer.lock`, `.phpunit.cache/`, `build/`, `vendor/`, `.agents/` — в `.gitignore` (для библиотеки lock не коммитится;
`.agents/` — локальная рабочая память, наружу не отдаётся).

## 5. Рабочие каталоги `.agents` и `docs`

`.agents/` — **локальный** каталог (в `.gitignore`): планы, журнал сессий и описания релизов. Он не попадает в
репозиторий и в дистрибутив Packagist, поэтому туда не кладут то, что должно быть публичным: для внешних потребителей
истина — `README.md`, `docs/api-reference.md` и описания в GitHub Release.

| Каталог                 | Содержимое                                                                                                         |
|-------------------------|--------------------------------------------------------------------------------------------------------------------|
| `journals/JOURNAL.md`   | Карта сессий: дата · файл · теги · краткое описание                                                                |
| `journals/sessions/`    | Файлы сессий `YYYY-MM-DD-тема.md`: frontmatter с тегами, тело ≤2 КБ                                                |
| `plans/`                | Планы многошаговых задач, статус: `в работе`, `завершён` или `ждёт данных` (с указанием чего именно); не удаляются |
| `release/`              | `RELEASE_NOTES_vX.Y.Z.md` — описание каждой версии, статус: в работе или выпущено                                  |
| `docs/` (в репозитории) | Публичная документация; карта документов — здесь, в разделе 4                                                      |

### Правила ведения

- **Файл сессии** — компактный отчёт: frontmatter (`tags`, `date`), затем секции `Проблема` / `Решение` / `Тесты` /
  `Нюансы` / `Gate`. Обязательная строка о Gate: что именно прогналось и с каким результатом. Секреты, токены,
  `vcf_info`, payload колбэков и прод-ответы в журнал не пишутся.
- **`JOURNAL.md`** — одна строка на сессию, самые новые сверху; формат строки: `дата · файл · теги · описание`.
- **План** — для задач из трёх и более шагов или требующих исследования (например, синхронизация со спекой): цель,
  исследование, реализация, тесты, нюансы, статус. Готовый план не удаляется, а помечается завершённым.
- **Release notes** — пишутся в `.agents/release/RELEASE_NOTES_vX.Y.Z.md` при выпуске версии; значимые пункты
  дублируются в README (раздел «История изменений») и в GitHub Release.
- Если правка изменила поведение публичного API или контракт с интеграторами — обнови `README.md` и
  `docs/api-reference.md` в той же сессии.

## 6. Архитектура и ключевые контракты

### Слои

| Слой        | Ключевые классы                                                   | Назначение                                                                                                        |
|-------------|-------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------|
| API         | `ApiClient`, `ApiClient::create()`                                | Единственная точка входа; фабрика компонентов                                                                     |
| Transport   | `HttpClient`, `RequestBuilder`, `ResponseDecoder`                 | PSR-18 запросы, сборка URI/заголовков, разбор ответов, нормализация ошибок                                        |
| Retry       | `RetryStrategy`                                                   | Экспоненциальный бэкофф: 429/5xx/сетевые сбои/`attachment.not.ready`                                              |
| RateLimit   | `RateLimiter`                                                     | Token bucket, 2 запроса/сек                                                                                       |
| Webhook     | `WebhookHandler`                                                  | Парсинг Update, верификация секрета (`hash_equals`)                                                               |
| LongPolling | `LongPollingRunner`                                               | Обёртка над `getUpdates` (только dev/тесты)                                                                       |
| Upload      | `Uploader`                                                        | Multipart-загрузка медиа (требует `ext-fileinfo`)                                                                 |
| Security    | `ContactVerifier`, `ContactPhoneExtractor`, `WebAppDataValidator` | Верификация контакта по кнопке `request_contact` и номер телефона из `vcf_info`; стартовых данных мини-приложения |
| Internal    | `Internal\Json`                                                   | Единственное место работы с JSON (кодирование/декодирование с исключениями)                                       |
| Dto         | `src/Dto/*` (54 класса)                                           | Типизированные модели запросов и ответов                                                                          |
| Enum        | `src/Enum/*` (10)                                                 | Строго типизированные значения                                                                                    |
| Exception   | `src/Exception/*` (7)                                             | Иерархия типизированных ошибок                                                                                    |

### Контракты компонентов

- **`ApiClient::create()`** — параметры: `$httpClient` (PSR-18), `$requestFactory` / `$streamFactory` /
  `$uriFactory` (PSR-17), `$accessToken` (обязательный), `$baseUri`, `$retryStrategy`, `$rateLimiter` (per-chat 2
  req/s), `$globalRateLimiter` (по умолчанию 30 req/s). Внутри собирает `HttpClient` (transport + retry + глобальный
  rate limit) и `Uploader`. Используется во всех примерах (`examples/bootstrap.php`).
- **Комментарии** — `getComments()`, `sendComment()`, `editComment()`, `deleteComment()`, `getComment()`: работают с
  сообщениями в каналах, где у бота есть право `read_all_messages`; `comment_id` = `mid` комментария, передаётся
  query-параметром; апдейты `comment_created`/`comment_edited` разбираются в `Update::$comment` (`CommentMessage`), а
  `Update::$message` остаётся `null`.
- **`WebhookHandler::decode(): Update|list<Update>`** — критичный нюанс: ответ может быть **одним объектом**
  **или** списком. Итерация без проверки ломает foreach:
  ```php
  $updates = $handler->decode($body);
  $updates = $updates instanceof Update ? [$updates] : $updates;
  foreach ($updates as $update) { ... }
  ```
  Ответы эндпоинта: неверный секрет → HTTP 401, невалидный body → HTTP 400, успех → HTTP 200 (обязателен в течение 30
  сек, иначе API повторит доставку). Нюанс полей: `Update::$user` — nullable; для `message_created`/`message_edited`/
  `message_callback` `user` и `chat_id` берутся из `message.sender`/`message.recipient` (и `callback.message`), если их
  нет на верхнем уровне.
- **`RetryStrategy`** — по умолчанию ретраит: 429, 5xx, сетевые сбои, `attachment.not.ready`; только идемпотентные
  методы; число попыток и задержки настраиваются.
- **`RateLimiter`** — дефолт 2 запроса/сек (лимит API на диалог/чат/канал).
- **`LongPollingRunner`** — **не для production**: спека ограничивает скорость и хранение событий.
- **`Uploader::upload(UploadType $type, string $filePath)`** — multipart собирается в seekable `php://temp`-поток (файл
  копируется по чанкам 8 КБ, без загрузки в память целиком; поток безопасен для повторов при ретраях). После загрузки
  **ждать** перед отправкой сообщения — иначе `attachment.not.ready` (ретраится автоматически).
- **`ContactVerifier`** — верификация: `hash_equals(hash_hmac('sha256', $vcfInfo, $accessToken), $hash)`; `vcf_info`
  хэшируется как есть, сырыми байтами (реальные CRLF, v1.1.3), при наличии литеральных `\r\n` — восстановление и
  повторная проверка. Хэш в hex или base64 (v1.1.2).
- **`ContactPhoneExtractor`** — `fromVcf(string $vcfInfo): ?string`: значение первого непустого `TEL` из `vcf_info`
  (`/^(?:[A-Za-z0-9-]+\.)?TEL(?:;[^:]*)?:(.*)$/i` — с префиксом группы; `X-TEL` не подходит), с учётом всех форм
  переводов строк и свёрнутых строк vCard. Во входящем payload `vcf_phone` нет, номер берётся только отсюда; значение
  возвращается как есть, без нормализации (v1.1.7). Подтверждён единственный реальный захват:
  `TEL;TYPE=cell:79250000000`, то есть полный код страны **без `+`**; в MAX один аккаунт — один номер, поэтому значение
  однозначно идентифицирует пользователя и требует канонизации на стороне потребителя, если используется как ключ.
  Формат для номеров других стран не подтверждён — не нормализовать наугад, ждать выборок.
- **`WebAppDataValidator`** — верификация стартовых данных мини-приложения (`verify(string $initData)` и
  `verifyFromUrl(string $url)`): `secret_key = HMAC-SHA256('WebAppData', token)`, подпись
  `hex(HMAC-SHA256(secret_key, launch_params))`; `launch_params` — значения после URL-декодирования, отсортированные по
  ключам, `key=value` через `\n`, без `hash`. Сравнение — `hash_equals`.

### Соглашения DTO

- Все DTO — `final readonly`, типизированные nullable-поля, валидация типов в `fromArray()`.
- `toArray()` — для запросов. Конструкторы `create()` есть только у `NewMessageBody`, `NewCommentBody`, `EditChatBody`,
  `AttachmentRequest`, `PinMessageBody`.
- Вложения (`Attachment`) — discriminated по `token`: 7 типов payload (+ image). Координаты локации в спеке лежат на
  верхнем уровне вложения, но вложенная форма тоже разбирается.
- JSON-кодирование/декодирование — только через `Internal\Json`, никогда напрямую `json_encode`/`json_decode`.
- ID: `message_id` / `callback_id` / `messageId` — **строки**; `chat_id` / `user_id` — **int64**.
- Отклонения API от спек-типов: внутри объекта `link` идентификаторы приходят **строками** (`sender: "277570130"`,
  `chat: "117541872"`), хотя `sender` объявлен как `integer/int64` (а в новой спеке — объект `User`). Такие int64 читать
  через `Json::tolerantInt()` (int или числовая строка → int, нет значения → `null`, мусор → исключение), а не через
  `Json::requiredInt()`. `LinkedMessage::$sender` — `?int` (в спеке поле необязательное), `LinkedMessage::$senderUser` —
  `?User` для новой формы спеки. Полный список зафиксированных расхождений — `docs/api-reference.md`, раздел 9.

### Иерархия исключений

`MaxApiException` (базовое) → `ApiException`, `AttachmentNotReadyException`, `InvalidArgumentException`,
`InvalidResponseException`, `NetworkException`, `RateLimitException`. Для бизнес-обработки ловить
`MaxApiException`. В `README.md` — раздел «Ошибки» с таблицей кодов и исключений.

## 7. Соглашения по коду

| Принцип              | Применение к этому пакету                                                                                                |
|----------------------|--------------------------------------------------------------------------------------------------------------------------|
| **SOLID**            | Один класс — одна ответственность; расширение через PSR-интерфейсы и передачу зависимостей в `ApiClient::create()`       |
| **DRY**              | JSON только через `Internal\Json`; общие DTO (`UserWithPhoto`, `BotInfo`, `ChatMember`) переиспользуются, не дублируются |
| **KISS**             | Никаких магических абстракций и собственных DI-контейнеров; публичный API — простые методы клиента                       |
| **TDD**              | Новый компонент сначала покрывается unit-тестом; HTTP-слой — через `tests/Support/MockHttpClient` (PSR-18)               |
| **BC-совместимость** | Публичное API библиотеки: не удалять и не менять сигнатуры в patch-релизе; новое — через необязательные параметры        |
| **Production-grade** | Gate (раздел 8), покрытие ≥95%, fail-closed на секретах, никаких глобальных состояний                                    |

- PHP **8.4**, `declare(strict_types=1)` во всех файлах, PSR-12 (php-cs-fixer), PHPStan **level max**.
- Namespace `GeekCo\MaxPhpClient` (тесты `GeekCo\MaxPhpClient\Tests`), PSR-4.
- Не добавлять комментарии без необходимости. `@codeCoverageIgnore` — только для defensive-веток, недостижимых в тестах
  (например, `file_get_contents()` вернул `false`).
- Тесты обязательны для нового кода. Интеграционные — read-only, группа `integration`, без токена `markTestSkipped`
  (не падать).
- Новые файлы в `examples/` — со смысловым именем, с `bootstrap.php`, без токенов в коде.

### OWASP Top 10 (обязательно при написании кода)

- Не доверять входящим данным: webhook body, query/path-параметры, поля из JSON (A03 — injection).
- Постоянновременное сравнение секретов — только `hash_equals` (A07 — identification failures).
- Не логировать: access token, secret, `vcf_info`, callback payload (A02, A09).
- SSRF: url из вебхуков/подписок — валидация `https://` + домен; upload-URL строго `https://`.
- Корректное кодирование в PSR-7; никаких конкатенаций URL (A03).
- Ограничение входных данных по размерам и типам.

## 8. Локальная разработка и обязательный Gate

PHP и Composer на хосте **не установлены** — весь запуск через Docker:

```bash
docker compose run --rm app bash                       # интерактивная оболочка PHP 8.4
docker compose run --rm app composer install
docker compose run --rm app composer run lint          # php-cs-fixer --dry-run (PSR-12)
docker compose run --rm app composer run format        # php-cs-fixer: авто-исправление
docker compose run --rm app vendor/bin/phpstan analyse # level max
docker compose run --rm app vendor/bin/phpunit         # unit-тесты
docker compose run --rm app composer run coverage      # тесты + проверка покрытия ≥95%
docker compose run --rm app composer audit             # уязвимости зависимостей → 0 уязвимых
```

`composer audit` (и `install`/`update`) из сети `docker compose run` и даже с `--network host` могут висеть с
`curl error 28`: DNS для `repo.packagist.org` и `packagist.org` отдаёт ротирующийся набор A-адресов, часть из них из
некоторых сетей не отвечает. Рабочий рецепт — подставить достижимые адреса (резолвить заново на каждый запуск):

```bash
pick_ip() {  # первый отвечающий на 443 IPv4-адрес из ротирующегося пула DNS
  local host="$1" ip
  for _ in $(seq 12); do
    for ip in $(getent ahostsv4 "$host" | awk '{print $1}' | sort -u); do
      curl -4 -s --max-time 4 -o /dev/null --resolve "$host:443:$ip" "https://$host/" && { echo "$ip"; return; }
    done
  done
}

docker run --rm --network host \
  --add-host "repo.packagist.org:$(pick_ip repo.packagist.org)" \
  --add-host "packagist.org:$(pick_ip packagist.org)" \
  -v "$(pwd)":/var/www/html -w /var/www/html -e COMPOSER_ROOT_VERSION=dev-main \
  ghcr.io/geekcodev/php:8.4-bookworm composer audit
```

Один ответ DNS часто содержит только неотвечающие адреса, поэтому в `pick_ip` адреса опрашиваются многократно; повтор
`composer audit` без фиксации адресов не помогает. В CI сеть нормальная, шаг выполняется штатно.

Запуск примеров без локального PHP:

```bash
examples/run.sh echo-bot-long-polling.php
examples/run.sh echo-bot-webhook.php   # слушает http://localhost:8080
```

Интеграционные смоук-тесты (read-only, реальный API, нужен `MAX_API_TOKEN`):

```bash
source .env && docker run --rm --network host \
  -v "$(pwd)":/var/www/html -w /var/www/html \
  -e MAX_API_TOKEN="$MAX_API_TOKEN" \
  ghcr.io/geekcodev/php:8.4-bookworm vendor/bin/phpunit --group integration
```

Нюансы интеграционных тестов:

- TLS до `platform-api2.max.ru` из Docker-сети (`docker compose run`) блокируется — только `--network host`.
- Цепочка сертификатов Минцифры — `tests/Fixtures/max-ca-chain.pem`.
- Без токена/доступа тесты пропускаются (`markTestSkipped`), а не падают.

### Обязательная последовательность (Gate) перед завершением задачи

После изменений в PHP-коде (`src/`, `tests/`, `examples/`):

1. **Lint**: `composer run lint` → 0 файлов с правками.
2. Если есть правки — `composer run format`, затем повторить lint.
3. **Статика**: `vendor/bin/phpstan analyse` → 0 ошибок.
4. **Тесты**: `vendor/bin/phpunit` → все зелёные (failOnRisky/failOnWarning).
5. **Покрытие**: `composer run coverage` → ≥95% строк.
6. **Аудит зависимостей**: `composer audit` → 0 уязвимых пакетов.

Все шаги обязательны. Если шаг недоступен в окружении (нет сети для `composer audit`, нет обращения к API) — сообщить
пользователю и указать в отчёте и в файле сессии.

### Обязательная проверка документации (шаг 7, всегда)

Gate не считается пройденным, пока не сверены **все** md-файлы и doc-комментарии. Проверять и при обычной задаче (раздел
6), и обязательно перед финалом релиза и перед коммитом, который закрывает релизный цикл. Правило введено после v1.1.7,
где статус релиза в этом файле отставал на четыре версии, а release notes, план и журнал утверждали, что смоук-тесты не
запускались, хотя они были зелёные.

1. **Числа совпадают с фактом.** Тесты, assertions, покрытие, число файлов в `.gitattributes` и названия фикстур в
   release notes, плане и файле сессии — из последнего прогона Gate, а не из памяти. Ошибка была ровно здесь:
   350/898 вместо 351/904 и старое имя `profile-crlf.vcf`.
2. **Отрицания не лгут.** «Не прогонялось», «нет токена», «нет данных» — только если так и есть. После прогона
   смоук-тестов или `composer audit` запись переводится в результат. Проверяются только файлы **текущего** релиза
   (release notes последней версии, его план и его файл сессии): архивные записи прошлых релизов не трогаются,
   иначе проверка всегда срабатывает на истории и её перестанут читать.
3. **Статус релиза.** Версия в разделе 1 этого файла равна последнему тегу **на origin**
   (`git ls-remote --tags origin | tail -1`), а не локальному `git tag`: после отката релизного тега локальная копия
   остаётся и даёт ложный «зелёный» результат проверки. В `.agents/release/` последний release notes помечен
   выпущенным либо явно откаченным, планы — «завершён», «ждёт данных» с указанием чего именно, или «в работе».
4. **Публичное API.** Новая функциональность, изменившая контракт, отражена в `README.md` и
   `docs/api-reference.md`: раздел «История изменений» содержит пункт о релизе, а в справочнике есть описание нового
   поведения. Для каждого утверждения «подтверждено данными» должен быть назван источник, иначе формулировка «проверено
   на реальном payload» недопустима.
5. **md-файлы не содержат мусора.** Нет висячих ссылок на переименованные файлы, дублей разделов, знаков-заменителей,
   смешения языков; примеры в README совпадают с рабочими примерами в `examples/`.
6. **`.agents/` не попадает в релиз.** Каталог в `.gitignore`, поэтому проверяй `git show --name-only <tag>`: в состав
   релиза не должно входить ничего из `.agents/`, `.env` и рабочих каталогов.

Проверка быстрая и обязательная: `git status --short` (чисто), `git show --stat <tag>` (состав),
`git ls-remote --tags origin | tail -1` (версия — источник истины; если разошлось с локальным `git tag`, удалённый
на сервере тег остался в копии и должен быть удалён локально), `grep -rn "не прогонялось\|не запускалось"
.agents/release/RELEASE_NOTES_v<последний>.md` (лживые отрицания — только в файлах текущего релиза), `grep -c "тестов" .agents/release/RELEASE_NOTES_vX.Y.Z.md` (цифры). После отката релиза
проверяй ещё `grep -rn "выпущено\|выпущен" .agents/` — записи о выходе должны быть переведены в откат. Результат —
строка в отчёте и в файле сессии.

## 9. CI/CD и релизы

- **Job `quality`**: сборка образа с Xdebug (`--build-arg INSTALL_XDEBUG=true`), lint, phpstan, phpunit + coverage gate,
  `composer audit`. Job-level `env: IMAGE: max-php-client:ci`.
- **Job `integration`**: смоук-тесты реального API; без `MAX_API_TOKEN` — шаги пропускаются, не падают (секрет
  передаётся только через job-level `env`, `secrets` в `if` на уровне job запрещены GitHub Actions).
- Ключевые детали workflow: `-e COMPOSER_ROOT_VERSION=dev-main` во всех шагах (обход отсутствия git-метаданных в
  volume), `-e XDEBUG_MODE=coverage` для генерации отчёта, кэш `vendor` по `composer.json`.
- **Релиз**: описание версии в `.agents/release/RELEASE_NOTES_vX.Y.Z.md` → merge PR `dev → main` →
  `git tag vX.Y.Z && git push origin vX.Y.Z` → GitHub Release из тега → Packagist (автообновление по webhook). Значимые
  пункты релиза продублировать в `README.md` (раздел «История изменений»). Перед merge обязательно пройти шаг 7 Gate
  (сверка документации): иначе в релиз уедут устаревшие числа и ложные отрицания. Тег ставится на merge-коммите в
  `main`; лёгкий тег допустим (Packagist берёт версию по имени), но для локальной памяти полезнее аннотированный.
- `version` в `composer.json` **не указывается** — Packagist берёт версию из тегов.

## 10. Частые ошибки (gotchas)

1. `WebhookHandler::decode()` возвращает `Update|list<Update>` — **не** итерировать без `instanceof`-проверки;
   `Update::$user` **nullable** (для сообщений/колбэков `user` и `chat_id` берутся из `message.sender`/`recipient`).
2. Токен — без `Bearer`; только заголовок, не query.
3. `attachment.not.ready` — загруженное вложение ещё не готово: ждать и ретраить.
4. `join_time` — миллисекунды (как остальные timestamp).
5. Из Docker-сети TLS до API блокируется — только `--network host`.
6. Имя переменной — только `MAX_API_TOKEN` (старое `MAX_ACCESS_TOKEN` не используется).
7. `getChats` deprecated — chat_id хранить через подписку на `bot_added`/`bot_started`.
8. `mime_content_type()` требует `ext-fileinfo` (объявлено в `require` composer.json).
9. Секреты/токены/vcf_info/callback payload — никогда в логи, коммиты и журнал сессий.
10. Версионирование — только git-тегами; `version` в composer.json не указывать.
11. `message.link.sender` приходит строкой, а не int — при разборе нужен `Json::tolerantInt()`, иначе теряется
    `mid` отправленного сообщения и отбрасываются апдейты с `link` (см. v1.1.4, v1.1.5).
12. `message.link.sender` по новой спеке — объект `User`, а не int64: в проде встречаются и объект, и числовая строка.
    Разбирать оба, `LinkedMessage::$senderUser` и `$sender` заполняются одновременно (v1.1.6).
13. Разметка в ответе (`MessageBody::$markup`, `CommentMessageBody::$markup`) — это список `MarkupElement` с `from` и
    `length`, а не markdown-строка. В enum `Markup` значение `underline`, хотя в `discriminator.mapping` спеки опечатка
    `underlined` (v1.1.6).
14. В `editComment` и `deleteComment` `comment_id` — это `mid` комментария, и он идёт **query-параметром**, а не в пути;
    `message_id` и `comment_id` валидируются на `[a-zA-Z0-9_-]+` (v1.1.6).
15. `ContactAttachmentPayload.vcf_phone` приходит только в **исходящем** payload; во входящем номер телефона брать из
    `vcf_info` через `ContactPhoneExtractor::fromVcf()`, порядок в потребителе — `vcfPhone ?? fromVcf(vcfInfo)`
    (v1.1.7). В реальном payload номер **без `+`**. Регистрация в MAX — только на один номер, поэтому контакт однозначно
    идентифицирует пользователя: если номер используется как ключ (CRM, дедупликация, лид), канонизацию делать в своём
    слое, иначе формы из разных источников разойдутся.

## 11. Чек-лист «production-grade» (самооценка при доработках)

- [ ] CI зелёный: lint 0, phpstan 0, phpunit зелёные, покрытие ≥95%, `composer audit` чист.
- [ ] Новый код покрыт unit-тестами (HTTP-слой — через MockHttpClient).
- [ ] Секретов нет в коде, логах, коммитах и журнале сессий.
- [ ] Входные данные валидируются (DTO / WebhookHandler / параметры запросов).
- [ ] OWASP Top 10 соблюдён (раздел 7): сравнение секретов через `hash_equals`, url из вебхуков и подписок проверены на
  `https://` и домен, нет конкатенаций URL, размеры и типы входа ограничены.
- [ ] Публичный API не сломан: сигнатуры в patch-релизе не менялись.
- [ ] Документация сверена (шаг 7 Gate): цифры из последнего Gate, отрицания правдивы, статус релиза равен последнему
  тегу, новый контракт описан в `README.md` и `docs/api-reference.md`, `.agents/` не попал в состав релиза.
- [ ] `README.md` и `docs/api-reference.md` синхронны с реальным поведением кода и API.
- [ ] Обновлены `.agents/journals/` и при необходимости `.agents/release/`.
- [ ] Релиз оформлен: merge в main → тег → GitHub Release → Packagist.
