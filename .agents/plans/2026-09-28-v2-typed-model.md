# План: v2.0.0 — типизированная модель событий, вложений и разметки

Статус: в работе (актуализация 2026-10-20; решения владельца получены — этап 0 к реализации)

- Целевая версия: **2.0.0** (major, ломающие изменения разрешены, обязателен `docs/migration-v2.md`).
- Спека: `geekcodev/max-openapi` @ **`9ef38a6`** (2026-10-20), базовое исследование — коммит `745aaf2`
  (план `2026-09-27-openapi-sync.md`, v1.1.6).
- Официальная схема: `geekcodev/max-official` `schema.yaml` @ `1a4a502`, пин `x-official-schema: v0.0.33`.
- Прод-поведение важнее спеки (`AGENTS.md` § 9, раздел 9 `docs/api-reference.md`); при конфликте — запись в раздел 9, а
  не «чинка» кода по спеке.

## 0. Пререквизит и согласованное решение владельца (2026-10-20)

В рабочем дереве `dev` лежит незакоммиченный **v1.1.11** (`Attachment::$filename`, толерантность
`ContactAttachmentPayload::$hash`, README, справочник, release notes). По решению владельца от 2026-10-20 в этот же
релиз **добавляются все аддитивные правки из раздела 2** (R1–R8, кроме случаев, где правка ломает сигнатуру) —
отдельного патча v1.1.12 не будет. Затем v1.1.11 коммитится и выпускается (порядок релиза — `AGENTS.md` § 9), после чего
начинаются работы v2.0.0.

Уже принятые решения (2026-10-20): строгость `required` — умеренная (решение 2); имена DTO вложений в v2 приводятся к
спеке (решение 3); `Update::$comment` удаляется в v2 (решение 5); сверяющий скрипт в репозиторий не переносится (решение
8).

## 1. Актуализация спеки 2026-10-20 (этап 1 выполнен)

### 1.1 Состояние спеки

- Дельта `745aaf2` → `9ef38a6` — два коммита (`30be9fc`, `9ef38a6`), `openapi.yaml` +184 строки; спека прошла
  собственную актуализацию (план `max-openapi/.agents/plans/2026-10-19-spec-update.md`, все этапы завершены:
  33 операции, 107 схем).
- Спека закрепила официальную схему: `info.x-official-schema: v0.0.33` + `tools/check_spec.py`.
- Правило `docs/api-reference.md` § 1 п. 4: уже зафиксированные в справочнике пункты повторно не перепроверяются,
  фиксируются только новые диффы.

### 1.2 Дельта `745aaf2` → `9ef38a6` (влияющая на клиент)

| Что изменилось в спеке                                                                                                                                                                  | Клиент сейчас                                                     | Действие                                               |
|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------|--------------------------------------------------------|
| `addChatMembers` **возвращён** в спеку, ответ `FailedUserDetails{error_code, user_ids[]}`                                                                                               | метод есть, помечен `@deprecated`; DTO в форме `{user_id, error}` | снять `@deprecated`, переписать DTO (форма — раздел 2) |
| `ChatStatus` + `suspended` (итого 5 значений; официальная схема подтверждает)                                                                                                           | 4 значения, `ChatStatus::fromArray('suspended')` падает           | **патч-кандидат**                                      |
| `POST /uploads` → `UploadEndpoint{url (обяз.), token?}`                                                                                                                                 | `UploadedInfo{url обяз., token?}` — совпадает                     | правка только в docs; `UploadResult` удалить в v2      |
| `required` в ответах: `members`×2, `subscriptions`, `updates`, `messages`, `message`, `success`                                                                                         | часть полей nullable                                              | строгость — этап 4 (v2)                                |
| `sendAnswer.notification`                                                                                                                                                               | уже есть — клиент опережает спеку                                 | —                                                      |
| `User.last_activity_time` nullable                                                                                                                                                      | уже `?int` — совпадает                                            | —                                                      |
| `LinkedMessage`/`CommentLinkedMessage`: `chat_id` обяз.                                                                                                                                 | `?int`                                                            | этап 4 (v2)                                            |
| `NewMessageBody.format` nullable                                                                                                                                                        | уже nullable — совпадает                                          | —                                                      |
| `user_locale` в 6 подтипах объявлен строгой строкой (не nullable, но не `required`: `bot_started`, `bot_stopped`, `dialog_cleared`, `dialog_muted`, `dialog_removed`, `dialog_unmuted`) | `?string`, `Json::string` — толерантно, поведение корректно       | оставить толерантность, зафиксировать в справочнике    |
| `Callback`: `payload` обяз.                                                                                                                                                             | `?string`                                                         | этап 4 (v2)                                            |
| `Update` → `allOf UpdateBase` (только `update_type`, `timestamp` — оба обяз.)                                                                                                           | плоский `Update`                                                  | этап 1 v2 (фабрика по `update_type`)                   |
| `getChats` по-прежнему отсутствует в спеке                                                                                                                                              | метод `@deprecated` — не трогать                                  | —                                                      |

Таблица 19 подтипов `Update` из исходного исследования перепроверена: состав полей и `required` подтипов не изменился
(кроме объявления `user_locale` см. выше). Поля `update_type`/`timestamp` подтверждены `UpdateBase`.

### 1.3 Сверка DTO со спекой (скрипт, 2026-10-20)

Сверено **41 сопоставленная схема** (компоненты-события, вложения, разметка, сущности), **20 расхождений**.
Классификация:

**Реальные — теряются данные или падает разбор:**

| #   | Что                                                                                                                                       | Расхождение                                                                                                                                                                                                                       | Релиз                                                                |
|-----|-------------------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------------------------------------------------------------|
| R1  | `ChatStatus`                                                                                                                              | нет `suspended` → `Chat::fromArray()` кидает `InvalidResponseException`                                                                                                                                                           | v1.1.11                                                              |
| R2  | `Subscription`                                                                                                                            | нет обяз. `time` (спека и официальная схема: `required [url, time]`)                                                                                                                                                              | v1.1.11                                                              |
| R3  | `Callback`                                                                                                                                | нет обяз. `timestamp`, `user`; `Update` не читает `callback.user` как фолбэк (для `message` читает); лишнее поле `message` — прод-форма, сохранить                                                                                | v1.1.11                                                              |
| R4  | `FileAttachmentPayload`                                                                                                                   | нет обяз. `filename`, `size` (`Attachment::$filename` добавлен в v1.1.11 отдельно; клиентское `file_size` — легаси); источник — top-level (решение 9)                                                                             | v1.1.11                                                              |
| R5  | `VideoAttachmentPayload`                                                                                                                  | нет `width`, `height`, `thumbnail` (top-level по официальной схеме); `duration` читается только из `payload` — выравнивается                                                                                                      | v1.1.11                                                              |
| R5а | `VideoUrls`                                                                                                                               | клиент `{mp4, preview}` vs спека `{mp4_144…mp4_1080, hls}` — теряются все rendition-URL и HLS                                                                                                                                     | v1.1.11 (добавить, legacy оставить)                                  |
| R6  | `AudioAttachmentPayload`                                                                                                                  | нет `transcription` (top-level)                                                                                                                                                                                                   | v1.1.11                                                              |
| R7  | `FailedUserDetails`                                                                                                                       | форма `{user_id, error}` vs спека `{error_code, user_ids[]}`; метод снова в спеке                                                                                                                                                 | v1.1.11 (чтение обеих форм); строгие nullable-поля — v2.0.0 (этап 5) |
| R8  | `StickerAttachmentPayload` (`{url, code, width, height}` обяз.), `ShareAttachmentPayload` (`{url, token, title, description, image_url}`) | нет типизированных классов, payload — сырой массив                                                                                                                                                                                | v2                                                                   |
| R9  | Имена DTO вложений                                                                                                                        | клиентский `ImageAttachmentPayload` = спека `PhotoAttachmentPayload` (`{photo_id, token, url}`); клиентский `PhotoAttachmentPayload` = спека `VideoThumbnail` (спека только `{url}`, у клиента есть `width`/`height` сверх спеки) | v2                                                                   |
| R10 | Строгость `required`                                                                                                                      | `Message.body`, `Recipient.chat_type`, `LinkedMessage.message`, `NewMessageLink.mid`, `VideoInfo` и др. объявлены nullable там, где спека требует значение                                                                        | v2 (этап 4)                                                          |

**Ложные флаги скрипта (поле есть, скрипт не спускается по наследованию клиента):** `ChatMember`,
`UserWithPhoto` (оба наследуют `User`), `UploadedInfo` (наследует `UploadResult`), `InlineKeyboardAttachmentPayload`
(клиент хранит `rows`, но читает и `buttons`, и `rows`, а сериализует в `buttons` — соответствует спеке).

**Прод-формы, сохранить и держать в разделе 9 справочника:** `ContactAttachmentPayload::$vcfPhone`,
`Callback::$message`, `MessageBody` `caption`/`format`, `NewMessageLink::$chat`, `LinkedMessage` `chat`/`mid`/
`senderUser`.

### 1.4 Подтверждено официальной схемой v0.0.33

- Метаданные вложений (`filename`, `size`, `width`, `height`, `duration`, `thumbnail`, `transcription`) — **top-level**
  подтипа; у `max-openapi` они внутри `payload`. Форма метаданных (решение 3) — верхний уровень, как принято в v1.1.6 и
  подтверждено продом.
- `ChatStatus` — 5 значений; `Subscription.required = [url, time]`;
  `Callback.required = [timestamp, callback_id, user]`.
- `UploadEndpoint{url обяз., token?}` — совпадает с текущим `UploadedInfo`.
- Видео/аудио payload в запросе требует `[url, token]`.

### 1.5 Устаревшие места документации (править на этапе соответствующего релиза)

- `docs/api-reference.md` § 3, строка про `addChatMembers`: « **УБРАН из спеки**» — спека вернула метод (патч v1.1.12).
- `docs/api-reference.md` § 8: `ChatStatus` без `suspended` (строка ~165); `Subscription (url, update_types?)` без
  `time` (строка ~216); `Update`/`Callback` без `timestamp`/`user` (строка ~211).
- Раздел 9 — дополнить прод-формами из 1.3 при их закреплении в коде.

## 2. Состав релизов (решение владельца: без отдельного патча)

### v1.1.11 (аддитивные правки, к уже подготовленному релизу)

Публичные сигнатуры не ломаются: новые поля — необязательные параметры конструктора в конечной позиции (как
`Attachment::$filename`), значения — nullable.

1. `ChatStatus::Suspended` (R1). Единственный кандидат, который ломает **поведение** (не сигнатуру): статус, который
   раньше ронял `Chat::fromArray()`, теперь разбирается. Для потребителей с исчерпывающим `match` по
   `ChatStatus` — запись в README; в v2 решение пересмотреть (умеренная строгость).
2. `Subscription::$time` — `?int` (по спеке обязательное, значение `null`-совместимо) (R2).
3. `Callback::$timestamp`, `Callback::$user` + фолбэк `callback.user` в `Update` (как уже сделано для
   `message.sender`) (R3).
4. `FileAttachmentPayload`: `$filename`, `$size` — сначала верхний уровень подтипа, фолбэк на `payload`, согласованно с
   `Attachment::$filename` из v1.1.11; `toArray()` пишет на верхний уровень; легаси `file_size`
   остаётся до v2 (R4).
5. `VideoAttachmentPayload`: `$width`, `$height`, `$thumbnail` — верхний уровень, фолбэк на `payload`;
   `duration` переводится на тот же порядок (сейчас читается только из `payload`); `VideoUrls`:
   `mp4_1080`, `mp4_720`, `mp4_480`, `mp4_360`, `mp4_240`, `mp4_144`, `hls` (legacy `mp4`/`preview`
   сохраняются, помечены в справочнике) (R5, R5а).
6. `AudioAttachmentPayload::$transcription` — верхний уровень, фолбэк на `payload` (R6).
7. `FailedUserDetails`: чтение обеих форм — `{error_code, user_ids[]}` (спека и официальная схема) и
   `{user_id, error}` (прод); `user_ids` — `list<int>`, `error_code` — `string`; **старый конструктор
   `(int $userId, string $error)` не трогать** (тест и возможные потребители), новые поля — в конце при сохранении
   позиционных. Плюс снять `@deprecated` с `addChatMembers()` и `AddChatMembersResult` и убрать из справочника «убран из
   спеки»: метод в спеке есть, ограничен с 9 сентября 2026, в доке пометки об удалении нет (R7).

### v2.0.0 (ломающие: строгость, типизация, удаления)

Типизированная иерархия (47 классов событий/вложений/разметки + `StickerAttachmentPayload`,
`ShareAttachmentPayload`, `VideoThumbnail`), переименование DTO вложений по спеке (R9), умеренная строгость
`required` (R10), удаление `@deprecated` (`getChats`, `AddChatMembersResult`, `UploadResult`,
`MessageBody::$caption`/`$format`, `NewMessageLink::$chat`, `Update::$comment`, легаси `file_size`,
`VideoUrls::$mp4`/`$preview`), `docs/migration-v2.md`.

## 3. Открытые решения и статус (2026-10-20)

| # | Решение                                                                                                                                                                                                                                                                                                                | Статус                                     |
|---|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------------------------------------|
| 1 | Батчинг: аддитивные правки — в текущий v1.1.11, ломающие — в v2.0.0                                                                                                                                                                                                                                                    | **утверждено владельцем**                  |
| 2 | Строгость `required` в v2: умеренная — строгие геттеры для обязательных полей, толерантные типы (`Json::tolerantInt`), смоук реальных апдейтов до включения                                                                                                                                                            | **утверждено**                             |
| 3 | Имена DTO вложений в v2: `ImageAttachmentPayload` → `PhotoAttachmentPayload`; текущий `PhotoAttachmentPayload` → `VideoThumbnail` (клиентские `width`/`height` сверх спеки — оставить, при желании зафиксировать клиентскую форму `{url, width, height}` в `VideoAttachmentPayload::$thumbnail`)                       | **утверждено**                             |
| 4 | `uploadMedia(): UploadedInfo` — соответствует `UploadEndpoint` новой спеки; сигнатуру не менять, в v2 удалить `UploadResult`                                                                                                                                                                                           | рекомендация, подтверждения не требовалось |
| 5 | `Update::$comment` — удалить в v2                                                                                                                                                                                                                                                                                      | **утверждено**                             |
| 6 | Namespace `GeekCo\MaxPhpClient` без переименования; `Update` — абстрактный с фабрикой `fromArray()` (без `UpdateBase`)                                                                                                                                                                                                 | прежнее решение, в силе                    |
| 7 | Форма метаданных вложений — верхний уровень подтипа (подтверждено официальной схемой, см. 1.4)                                                                                                                                                                                                                         | прежнее решение, в силе                    |
| 8 | Сверяющий скрипт `/tmp/opencode/spec_vs_dto.py` в репозиторий не переносится                                                                                                                                                                                                                                           | **утверждено**                             |
| 9 | Источники для полей вложений: `filename`/`size`/`width`/`height`/`duration`/`thumbnail`/`transcription` — сначала верхний уровень подтипа (форма официальной схемы и прода), затем `payload` (форма `max-openapi`); `toArray()` пишет в верхний уровень — единая форма вывода, как у `Attachment::$filename` в v1.1.11 | **утверждено 2026-10-20**                  |

## 4. Этапы реализации

Каждый этап — отдельный PR `dev → dev` с зелёным Gate. Этапы 1–3 аддитивны, этап 4 ломает публичный API.

- **Этап 0 — ВЫПОЛНЕН (2026-10-20):** аддитивные правки R1–R8 реализованы поверх v1.1.11 (см. раздел 2 и 8), тесты и
  документация обновлены, полный Gate зелёный (lint 0/112, PHPStan 0, PHPUnit 389/1007, покрытие 99.82%, audit 0).
  Осталось: merge `dev → main`, тег `v1.1.11`, GitHub Release, Packagist — по отдельному запросу владельца.
- **Этап 1. Актуализация спеки — ВЫПОЛНЕН (2026-10-20):** дельта и сверка в разделах 1.2–1.4.
- **Этап 2. Каркас типизированных событий:** `Update` → `abstract readonly` с фабрикой `fromArray()` (match по
  `UpdateType`, неизвестный тип → `InvalidResponseException`); 19 подтипов в `src/Dto/Update/`; сверка
  `Message`/`CommentMessage` и общая база; `WebhookHandler::decode(): Update|list<Update>` — сигнатура сохраняется;
  `LongPollingRunner` не меняется. Поля — по таблице подтипов из исходного исследования (перепроверены 2026-10-20).
- **Этап 3. Типизированные вложения:** `AttachmentBase` + 9 подтипов ответа, `AttachmentRequestBase` + 9 подтипов
  запроса; новые `StickerAttachmentPayload`, `ShareAttachmentPayload`, `VideoThumbnail`; переименование по R9;
  метаданные — top-level (решение 7); `location` без `payload`.
- **Этап 4. Типизированная разметка:** `MarkupElement` → abstract + 10 подтипов; `MessageBody::$markup` /
  `CommentMessageBody::$markup` → `list<MarkupElement>`; enum `Markup` остаётся (опечатка `underlined` — раздел 9).
- **Этап 5. Строгость и удаление deprecated (ломающий):** строгость `required` (решение 2); удалить `getChats()`,
  `AddChatMembersResult`, `UploadResult`, `MessageBody::$caption`/`$format`, `NewMessageLink::$chat`,
  `Update::$comment`; финализировать сигнатуры `uploadMedia(): UploadedInfo`.
- **Этап 6. Документация:** `docs/migration-v2.md` (таблица «было → стало», `match` → `instanceof`, `getChats` →
  подписка `bot_added`/`bot_started`); README, `docs/api-reference.md` (иерархия подтипов, разделы 3/8/9 — см. 1.5),
  `AGENTS.md`; примеры `examples/` на v2.
- **Этап 7. Релиз:** `RELEASE_NOTES_v2.0.0.md` → PR `dev → main` → тег `v2.0.0` → GitHub Release → обновление мостов
  (Laravel/Symfony → `^2.0`).

## 5. Тесты и Gate

- Coverage ≥95% строк; обязательное покрытие всех новых классов (~50).
- `composer run lint` → 0; `vendor/bin/phpstan analyse` (level max) → 0; `vendor/bin/phpunit` → зелёные
  (failOnRisky/failOnWarning); `composer audit` → 0 уязвимых; шаг 7 Gate (сверка документации).
- Тесты по этапам: 19 фикстур подтипов `Update` (`instanceof`, unknown `update_type`, round-trip `toArray()`), по одному
  примеру каждого из 18 подтипов вложения (`location` без `payload`), 10 подтипов разметки, R1–R8 из раздела 2 — в
  релизе v1.1.11 (регрессионный на `ChatStatus::suspended`, `time` в подписке,
  `timestamp`/`user` в колбэке, метаданные и rendition-URL видео, `transcription`, обе формы `FailedUserDetails`).
- Интеграционные смоук-тесты (группа `integration`, read-only): добавить проверку `getComments()` и разбора
  `comment_created` на реальном API, а перед включением строгости (этап 5) — приём реальных апдейтов; без токена — skip.
- BC-контроль: набор «v1-примеры из README» падает до этапа 5 и зелёный после (маркер миграции).

## 6. Нюансы и риски

- `readonly`-наследование: база и подтипы `readonly`, инициализация только в декларирующем классе.
- Union-типы из спеки — через `match`/`instanceof`; в докблоках — конкретный подтип.
- Расширение `UpdateType` ломает исчерпывающий `match` у потребителей — в гайде предписать `default`.
- Рост числа классов: 54 DTO → около 100; `DtoCoverageTest` перевести на data-provider по списку классов.
- Прод-отклонения нельзя «чинить» по спеке: `link.sender` (строка/объект), `mid` на верхнем уровне, координаты вложений,
  `Callback::$message` — проверять на реальных ответах, держать в разделе 9.
- **Строгость `required` (этап 5) — главный риск для вебхука:** сначала смоук на реальных апдейтах, потом включение.

## 7. Возражения по решению владельца «всё аддитивное — в v1.1.11»

Зафиксировано честно, решение остаётся за владельцем (утверждено 2026-10-20):

1. **R1 — не аддитивная правка, а смена поведения.** `ChatStatus::Suspended` в патче меняет то, как разбирается ранее
   падавший апдейт: у потребителей с исчерпывающим `match (ChatStatus)` появится непокрытая ветка, а сценарии, где чат
   со статусом `suspended` раньше «не замечался» (исключение и повтор), начнут обрабатываться. Формально semver
   позволяет добавление case в enum; предупреждение в README обязательно.
2. **Волна изменений:** в одном релизе — 8 правок DTO, включая четыре новых класса полей, плюс README, справочник
   (разделы 3, 8, 9) и release notes. Стоимость ревью и риск регрессии выше, чем у узкого патча.
3. **Альтернатива, если передумаете:** R2–R8 уходят в v1.1.11 (действительно аддитивны), R1 — в v2.0.0 либо в v1.1.12 с
   обычным «additive patch»-описанием. План перестраивается одной правкой раздела 2.

## 8. Точный список правок этапа 0 (реализация v1.1.11)

| Файл                                                                                        | Правка                                                                                                                             |
|---------------------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------|
| `src/Enum/ChatStatus.php`                                                                   | + `case Suspended = 'suspended';`                                                                                                  |
| `src/Dto/Subscription.php`                                                                  | + `public ?int $time = null;` (в конце), чтение `Json::int($data, 'time')`                                                         |
| `src/Dto/Callback.php`                                                                      | + `?int $timestamp`, `?User $user` (в конце); чтение `timestamp`, `user`                                                           |
| `src/Dto/Update.php`                                                                        | фолбэк `callback.user` → `$user`, если `user` нет ни на верхнем уровне, ни в `message.sender`                                      |
| `src/Dto/FileAttachmentPayload.php`                                                         | + `?string $filename`, `?int $size`; чтение: top-level → `payload`; `toArray()` — top-level; `file_size` не трогать                |
| `src/Dto/VideoAttachmentPayload.php`                                                        | + `?int $width`, `?int $height`, `?PhotoAttachmentPayload $thumbnail`; `duration` — top-level → `payload`; `toArray()` — top-level |
| `src/Dto/VideoUrls.php`                                                                     | + `?string` × 7 (`mp4_1080`…`mp4_144`, `hls`); legacy `mp4`/`preview` остаются                                                     |
| `src/Dto/AudioAttachmentPayload.php`                                                        | + `?string $transcription`; чтение top-level → `payload`                                                                           |
| `src/Dto/FailedUserDetails.php`                                                             | + `?string $errorCode`, `list<int>                                                                                                 |null $userIds` (в конце, после `$userId`, `$error`); чтение обеих форм; `@deprecated` снять |
| `src/Dto/AddChatMembersResult.php`                                                          | снять `@deprecated`                                                                                                                |
| `src/ApiClient.php`                                                                         | снять `@deprecated` с `addChatMembers()`                                                                                           |
| `tests/Dto/AttachmentTest.php`, `tests/DtoCoverageTest.php`, `tests/WebhookHandlerTest.php` | тесты на обе формы каждого поля, регрессия `ChatStatus::suspended`, `callback.user`-фолбэк, `FailedUserDetails` обе формы          |
| `docs/api-reference.md`                                                                     | § 3 (`addChatMembers` больше не «убран»), § 8 `ChatStatus`/`Subscription`/`Callback`/метаданные вложений, § 9 (прод-формы)         |
| `README.md`                                                                                 | «История изменений» v1.1.11 + предупреждение про `ChatStatus`                                                                      |
| `.agents/release/RELEASE_NOTES_v1.1.11.md`                                                  | состав и изменения                                                                                                                 |

- R1 (`ChatStatus::Suspended`) меняет поведение, а не сигнатуру: у потребителей с исчерпывающим `match` по enum появится
  непокрытая ветка — предупредить в README и оставить как осознанно принятое решение владельца.
- Источники метаданных вложений (решение 9): приоритет — верхний уровень подтипа (официальная схема и прод), фолбэк —
  `payload` (форма `max-openapi`); `toArray()` пишет на верхний уровень. Обе формы фиксируются тестами;
  `duration` выравнивается под то же правило (было — только `payload`).
- Таймлайн владельцем не задан: этапы 2–4 можно вести параллельно, этапы 5–7 строго последовательны.
