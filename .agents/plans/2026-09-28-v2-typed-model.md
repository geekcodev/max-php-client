# План: v2.0.0 — типизированная модель событий, вложений и разметки

Статус: в работе (реализация отложена)

## Актуальность плана

План написан по коммиту спеки `745aaf2` и не зафиксирован. Ожидается, что
`max-openapi` будет обновляться. Перед этапом 1 обязательно:

- снять свежий коммит спеки и перепроверить таблицу 19 подтипов `Update`, набор полей вложений и разметки — расхождения
  зафиксировать здесь, а не в коде;
- заново сверить `MAX_API_REFERENCE.md` и `docs/api-reference.md` раздел 9: часть решений ниже может устареть;
- пересмотреть пять открытых решений (namespace, имя базового класса, форма метаданных вложений, legacy-поля
  `LinkedMessage`, судьба `Update::$comment`).

Если спека обновится, план правится, а не исполняется «как есть»: прод-поведение и
`AGENTS.md` § 9 имеют приоритет.

## Цель

Перевести клиент на типизированную иерархию по спецификации `max-openapi` и снять все помеченные `@deprecated` элементы.
v2.0.0 — major-релиз, ломающие изменения разрешены и обязаны сопровождаться гайдом по миграции.

Источник контрактов: `/tmp/opencode/max-openapi/openapi.yaml`, `MAX_API_REFERENCE.md`, коммит `745aaf2`, сверено
2026-09-27. Прод-поведение важнее спеки (AGENTS § 9, раздел 9
`docs/api-reference.md`).

## Исследование (что говорит спека)

Объём будущих классов: 19 подтипов `Update`, 9 подтипов `Attachment` + 9 подтипов
`AttachmentRequest`, 10 подтипов `MarkupElement` — итого 47 новых классов поверх текущих 54 DTO и 10 enum.

Базовые поля события (`UpdateBase`): `update_type`, `timestamp` (оба обязательны). Каждый подтип закрепляет своё
значение в `update_type` через `enum`, поэтому ветка
`oneOf` выбирается однозначно — `discriminator` не нужен, достаточно `match` по
`update_type`.

Поля 19 подтипов:

| Подтип                             | Поля подтипа                                                                                             | Обязательные                                             |
|------------------------------------|----------------------------------------------------------------------------------------------------------|----------------------------------------------------------|
| `MessageCreatedUpdate`             | `message: Message`, `user_locale`                                                                        | `message`                                                |
| `MessageEditedUpdate`              | `message: Message`                                                                                       | `message`                                                |
| `MessageRemovedUpdate`             | `chat_id`, `message_id`, `user_id`, `user_locale`                                                        | `chat_id`, `message_id`, `user_id`                       |
| `MessageCallbackUpdate`            | `callback: Callback`, `message: Message\|null`, `user_locale`                                            | `callback`                                               |
| `CommentCreatedUpdate`             | `message: CommentMessage`                                                                                | `message`                                                |
| `CommentEditedUpdate`              | `message: CommentMessage`                                                                                | `message`                                                |
| `CommentRemovedUpdate`             | `chat_id`, `message_id`, `user_id`, `post_id`                                                            | все четыре                                               |
| `BotAddedToChatUpdate`             | `chat_id`, `user: User`, `is_channel`                                                                    | все три                                                  |
| `BotAdminPermissionsChangedUpdate` | `chat_id`, `user_id`, `bot_id`, `is_channel`, `is_admin`, `permissions: list<ChatAdminPermission>\|null` | `chat_id`, `user_id`, `bot_id`, `is_channel`, `is_admin` |
| `BotRemovedFromChatUpdate`         | `chat_id`, `user: User`, `is_channel`                                                                    | все три                                                  |
| `UserAddedToChatUpdate`            | `chat_id`, `user: User`, `is_channel`, `inviter_id`                                                      | `chat_id`, `user`, `is_channel`                          |
| `UserRemovedFromChatUpdate`        | `chat_id`, `user: User`, `is_channel`, `admin_id`                                                        | `chat_id`, `user`, `is_channel`                          |
| `BotStartedUpdate`                 | `chat_id`, `user: User`, `user_locale`, `payload`                                                        | `chat_id`, `user`                                        |
| `BotStoppedUpdate`                 | `chat_id`, `user: User`, `user_locale`                                                                   | `chat_id`, `user`                                        |
| `ChatTitleChangedUpdate`           | `chat_id`, `user: User`, `title`                                                                         | `chat_id`, `title`                                       |
| `DialogClearedUpdate`              | `chat_id`, `user: User`, `user_locale`                                                                   | `chat_id`, `user`                                        |
| `DialogMutedUpdate`                | `chat_id`, `user: User`, `muted_until`, `user_locale`                                                    | `chat_id`, `user`, `muted_until`                         |
| `DialogUnmutedUpdate`              | `chat_id`, `user: User`, `user_locale`                                                                   | `chat_id`, `user`                                        |
| `DialogRemovedUpdate`              | `chat_id`, `user: User`, `user_locale`                                                                   | `chat_id`, `user`                                        |

Вложения: `AttachmentBase` (только `type`) + 9 подтипов ответа, `AttachmentRequestBase`

+ 9 подтипов запроса. У `location` `payload` нет, координаты на верхнем уровне. Остальные подтипы держат `payload`;
  `share` и `inline_keyboard` используют общие payload-схемы для запроса и ответа, остальные — отдельные
  `*AttachmentRequestPayload`.

Важное расхождение: `max-openapi` собирает метаданные (`width`, `height`, `duration`,
`thumbnail`, `filename`, `size`, `title`, `description`, `image_url`, `transcription`)
внутрь `payload`, а официальная `schema.yaml` и прода держат их на верхнем уровне подтипа. Это блокирующий вопрос этапа
2.

Разметка: `MarkupElement` (база: `type`, `from`, `length`) + 10 подтипов; `LinkMarkup`
добавляет `url`, `UserMentionMarkup` — `user_id`/`user_link`, остальные только базовые поля. Точные поля `HeadingMarkup`
и `QuoteMarkup` проверить по спеке на этапе 3.

## Решения, требующие подтверждения владельца

1. **Namespace.** Оставить `GeekCo\MaxPhpClient` и ограничиться `^2.0` в `composer.json`
   (рекомендуется: мосты Laravel/Symfony не придётся переименовывать).
2. **Имя базового класса.** Сделать существующий `Update` абстрактным и polymorphic (фабрика `Update::fromArray()`
   возвращает подтип) вместо введения `UpdateBase`
   (рекомендуется: имя `Update` остаётся, миграция короче).
3. **Форма метаданных вложений.** Верхний уровень подтипа по прода и официальной
   `schema.yaml` (рекомендуется) или `payload` по `max-openapi`.
4. **Legacy-поля `LinkedMessage`.** `sender: ?int`, `chat: ?string`, `mid` на верхнем уровне приходят из прода;
   предлагается сохранить и в v2, пометив новую форму
   `senderUser`/`chatId` как основную.
5. **`Update::$comment`.** В v1.1.6 добавлен как обход отсутствия типизации. В v2 удалить: комментарии приходят в
   `CommentCreatedUpdate::$message` /
   `CommentEditedUpdate::$message` типа `CommentMessage`.

## Реализация по этапам

Каждый этап — отдельный PR `dev → dev` с зелёным Gate. Этапы 1-3 аддитивны и не ломают v1; этап 4 ломает публичный API.

### Этап 1. Каркас типизированных событий

- `src/Dto/Update.php`: `abstract readonly class`, поля `update_type`, `timestamp`, фабрика `fromArray()` с `match` по
  `UpdateType` → подтип; неизвестный тип →
  `InvalidResponseException`.
- 19 подтипов в `src/Dto/Update/` (по одному файлу на событие), поля строго по таблице исследования, `fromArray()` с
  валидацией обязательности.
- `Message` и `CommentMessage`: сверить набор полей и, если он совпадает, ввести общую абстрактную базу `MessageBase`;
  `Update::$message` из базы убрать.
- `WebhookHandler::decode(): Update|list<Update>` — сигнатура сохраняется, наполнение становится подтипами;
  `LongPollingRunner` не меняется.
- Тесты: 19 фикстур (примеры из `openapi.yaml`), `instanceof` на каждый подтип, unknown
  `update_type`, `toArray()` round-trip.

### Этап 2. Типизированные вложения

- `AttachmentBase` (abstract, `type`) + 9 подтипов ответа; `AttachmentRequestBase` + 9 подтипов запроса;
  переиспользовать payload-классы v1.1.6, добавить недостающие.
- Зафиксировать форму метаданных по решению 3; `Attachment::fromArray()` выбирает подтип по `type`.
- `NewMessageBody::$attachments` принимает `list<AttachmentRequest>`; `MessageBody`
  возвращает `list<Attachment>`.
- Тесты: по одному примеру каждого из 18 подтипов, `toArray()` совпадает с запросом из спеки, `location` без `payload`.

### Этап 3. Типизированная разметка

- `MarkupElement` → abstract base (`type`, `from`, `length`) + 10 подтипов из спеки.
- `MessageBody::$markup` и `CommentMessageBody::$markup` → `list<MarkupElement>`.
- `Markup` enum сохраняется (значения совпадают со спекой, кроме опечатки
  `underlined`, см. `docs/api-reference.md` раздел 9).
- Тесты: 10 подтипов, `from`/`length`, `url` и `user_id`/`user_link`, разбор и сборка.

### Этап 4. Удаление deprecated и ломающие изменения

- Удалить: `getChats()`, `addChatMembers()`, `AddChatMembersResult`,
  `FailedUserDetails`, `MessageBody::$caption`, `MessageBody::$format`,
  `NewMessageLink::$chat`, `UploadResult` (остаётся `UploadedInfo`).
- Вернуть сигнатуры `ApiClient::uploadMedia(): UploadedInfo` и `Uploader::upload(): UploadedInfo`
  как окончательные (в v1.1.6 они были сужены, см. решения по BC).
- Удалить `Update::$comment`, если решение 5 подтверждено.
- Удалить `MessageStat`-совместимость и прочие v1-обходы, которые больше не нужны.
- `composer.json`: `PHP ^8.4` без изменений, описание breaking changes в release notes.

### Этап 5. Документация и миграция

- `docs/migration-v2.md`: таблица «было → стало» по всем удалённым элементам, примеры перевода `match` по `update_type`
  на `instanceof`, переход с `getChats()` на подписку `bot_added`/`bot_started`, замена `UploadResult` на
  `UploadedInfo`.
- Обновить `README.md` (примеры вебхуков, разметка, комментарии) и
  `docs/api-reference.md` (иерархия подтипов, раздел 9).
- Обновить `AGENTS.md`: слои DTO, gotchas, Gate.
- Все примеры в `examples/` — на v2 API, `examples/run.sh` без изменений.

### Этап 6. Релиз

- `RELEASE_NOTES_v2.0.0.md` с явным списком breaking changes и ссылкой на гайд.
- PR `dev → main`, тег `v2.0.0`, GitHub Release, Packagist.
- Обновить мосты (отдельные пакеты): Laravel и Symfony — `^2.0` и перевод на подтипы.

## Тесты и Gate

- Coverage ≥95% строк, обязательное покрытие всех 47 новых классов.
- `composer run lint` → 0; `vendor/bin/phpstan analyse` (level max) → 0;
  `vendor/bin/phpunit` → зелёные; `composer audit` → 0 уязвимых.
- Интеграционные смоук-тесты (группа `integration`, read-only): добавить проверку
  `getComments()` и разбора `comment_created` на реальном API; без токена — skip.
- BC-контроль: отдельный тест-набор «v1-примеры из README» должен падать до этапа 4 и стать зелёным после (маркер
  миграции).

## Нюансы и риски

- `readonly` наследование: базовый класс и все подтипы — `readonly`, инициализация только в декларирующем классе;
  обходные сеттеры запрещены.
- PHP не умеет union-типы для DTO, поэтому «объединение» из спеки реализуется
  `match`/`instanceof`; в докблоках указывать конкретный подтип.
- Расширение `UpdateType` (как в v1.1.6) ломает исчерпывающий `match` у потребителей — в v2 это допустимо, но гайд
  должен предписать `default`.
- Рост числа классов: 54 DTO → около 100; `DtoCoverageTest` придётся переписать на data-provider по списку классов,
  иначе тест станет хрупким.
- Прод-отклонения нельзя «чинить» по спеке: `link.sender` (строка или объект),
  `mid` на верхнем уровне, координаты вложений — проверять на реальных ответах.
- Таймлайн не задан владельцем: этапы 1-3 можно вести параллельно, этапы 4-6 строго последовательны.
