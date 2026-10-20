# v1.1.11 — контакт без `hash` в webhook не роняет апдейт, метаданные вложений и колбэка

Статус: **в работе** (2026-10-20). Код и Gate готовы; merge `dev → main`, тег `v1.1.11`, GitHub Release и публикация
Packagist выполняются по явному запросу пользователя. Целевая версия — patch после v1.1.10: существующие публичные
сигнатуры не менялись, добавлены только необязательные параметры конструкторов в конечных позициях
(`Attachment::$filename`,
`Subscription::$time`, `Callback::$timestamp`/`$user`, метаданные payload-классов, `FailedUserDetails::$errorCode`/
`$userIds`)
и одно значение enum. Единственная правка поведения — `ChatStatus::Suspended` (см. ниже).

## Изменение

Регрессия из наблюдения интегратора (filament-max-chat, хост i2tech): контакт, отправленный пользователем в чат, не
доходит до оператора. Официальная OpenAPI-спека MAX объявляет `ContactAttachmentPayload.hash` nullable и не включает его
в `required`, а `fromArray()` требовал строку: при `hash: null` или отсутствии поля выбрасывался
`InvalidResponseException: Field "hash" must be a string.`, контроллер вебхука в `laravel-max-client` отвечал
`400 invalid_payload`, и апдейт терялся целиком. Строгое требование вводилось в `bdfc828` (`fix!`) по тогдашним
наблюдаемым ответам, где поле присутствовало.

- `ContactAttachmentPayload::fromArray()`: `hash: Json::string($data, 'hash') ?? ''` — отсутствие или `null` дают пустую
  строку; неверный тип (не строка и не `null`) по-прежнему бросает `InvalidResponseException`.
- `toArray()`: пустой `hash` не сериализуется — в исходящих раунд-трипах не появляется `hash: ""`.
- `ContactVerifier::verify()` с пустым хэшем и раньше возвращает `false`; `MaxContactService` в `laravel-max-client`
  без изменений — неподписанный телефон остаётся неподтверждённым.

## Изменение 2: `Attachment::$filename` — имя файлового вложения

Симптом из интеграционного проекта (i2tech): в админке файл из мессенджера отображался под служебным именем
`getfile` — так имя получалось из последнего сегмента URL скачивания, потому что DTO имя вообще не нёс. Официальная
`schema.yaml` объявляет `filename` (и `size`) на верхнем уровне `FileAttachment`, спека max-openapi держит их в
`payload`; клиент разбор ни одного из мест не читал.

- `Attachment::$filename` (новый необязательный параметр конструктора в конечной позиции): `fromArray()` читает
  `filename` с верхнего уровня, при отсутствии — из `payload`; приоритет у верхнего уровня.
- `toArray()` отдаёт `filename` на верхнем уровне (официальная форма), поле с `null` не сериализуется.
- Формат не строка — `InvalidResponseException`, как у остальных строковых полей.

Поле `size` и легаси `file_size` из `FileAttachmentPayload` не менялись: клиент по-прежнему читает `file_size`, который
официальной схемой не подтверждён, — это отдельная задача, зафиксирована в журнале.

## Изменение 3: синхронизация с `max-openapi@9ef38a6` (2026-10-20)

Спека обновилась после v1.1.10 (два коммита от `745aaf2`, +184 строки, закреплена официальная схема
`x-official-schema: v0.0.33`). Сверка `components/schemas` с DTO нашла поля и значение, которые клиент терял или на
которых падал; всё исправлено аддитивно, без изменения существующих сигнатур.

- `ChatStatus::Suspended` (`suspended`). Раньше значение отсутствовало в enum, и `Chat::fromArray()` бросал
  `InvalidResponseException` на чате со статусом `suspended`. **Внимание потребителям:** у исчерпывающего
  `match (ChatStatus)` появится непокрытая ветка — нужен `default`. Это единственная правка поведения в релизе.
- `Subscription::$time` — поле `time`, объявленное обязательным в спеке и официальной `schema.yaml`.
- `Callback::$timestamp`, `Callback::$user`; `Update::$user` теперь берётся и из `callback.user`, если его нет ни на
  верхнем уровне, ни в `message.sender` (раньше фолбэк работал только для `message.sender`).
- Метаданные вложений: `FileAttachmentPayload::$filename`/`$size`, `VideoAttachmentPayload::$width`/`$height`/
  `$thumbnail`, `AudioAttachmentPayload::$transcription`. Официальная схема объявляет их на верхнем уровне подтипа,
  спека max-openapi — внутри `payload`; разбираются обе формы с приоритетом верхнего уровня, `toArray()` пишет их в
  `payload`. `duration` видео и аудио читается с верхнего уровня наравне с `payload`.
- `VideoUrls` — потоки `mp4_1080`, `mp4_720`, `mp4_480`, `mp4_360`, `mp4_240`, `mp4_144` и `hls`; легаси `mp4` и
  `preview` сохранены (встречаются в старых ответах, будут удалены в v2.0.0).
- `FailedUserDetails` — разбирается форма спеки `{error_code, user_ids[]}` наряду с прод-формой `{user_id, error}`;
  конструктор `(int $userId, string $error)` сохранён. Ослаблен контракт обязательности: раньше отсутствие `user_id`/
  `error` роняло разбор, теперь поля получают `0`/`''`. Известное ограничение: `userId === 0` означает «не пришло»,
  поэтому реальное `user_id: 0` (в проде не встречается, идентификаторы с 1) неотличимо и не попадает в `toArray()`;
  строгие nullable-поля — задача v2.0.0.
- Снят `@deprecated` с `addChatMembers()` и `AddChatMembersResult`: метод присутствует в спеке и официальной схеме, с 9
  сентября 2026 ограничен со стороны MAX, пометки об удалении нет. **Отменяет пункт v1.1.6**, где
  `addChatMembers()`, `AddChatMembersResult` и `FailedUserDetails` были перечислены как устаревшие до v2.0.0.

## Затронутые сценарии

- Webhook `message_created` с contact-вложением без `hash` или с `hash: null` теперь разбирается и доходит до
  обработчика; раньше апдейт отклонялся с 400 и не доставлялся повторно после срабатывания попыток MAX.
- Файловое вложение в вебхуке: `Attachment::$filename` несёт имя загруженного файла, потребитель больше не вынужден
  выводить его из URL. Размер — `FileAttachmentPayload::$size` (легаси `file_size` сохранён).
- Видео: `VideoAttachmentPayload` несёт `width`/`height`/`thumbnail` и `duration`, а `VideoUrls` — потоки `mp4_*` и
  `hls`; раньше rendition-ссылки и метаданные терялись. Аудио: `transcription` больше не теряется.
- Чат со статусом `suspended` разбирается вместо исключения (см. предупреждение о `match` выше).
- `message_callback`: у колбэка доступны `timestamp` и `user`, событие несёт отправителя, даже если верхнего `user` и
  `message.sender` нет.
- Подпись контакта (`request_contact`) проверяется как раньше: с реальным хэшем — по HMAC, без хэша — fail-closed
  (`false`), телефон из неподписанного контакта не принимается как подтверждённый.
- Контакты по кнопке (где `hash` присутствует) не затронуты.

## Качество

Gate (2026-10-20, dev-контейнер): lint 0 из 112 файлов, PHPStan level max — 0 ошибок, PHPUnit — 389 тестов / 1007
проверок зелёные, покрытие строк 99.82% (1668/1671, порог 95%), `composer audit` — 0 уязвимостей (через pick_ip-рецепт
раздела 8 AGENTS.md). Интеграционные смоук-тесты группы `integration` не запускались (без `MAX_API_TOKEN`).

Регрессионные тесты: `AttachmentTest` (отсутствующий и `null`-`hash` разбираются в пустую строку, неверный тип —
исключение; `filename` с верхнего уровня и из `payload`, приоритет верхнего уровня, неверный тип — исключение;
метаданные видео и аудио с верхнего уровня и из `payload`, приоритет непустого значения в `payload`; rendition-URL
видео; неверный тип `transcription`), `WebhookHandlerTest` (полный webhook-запрос с contact без `hash` декодируется,
`toArray()` не отдаёт пустой `hash`), `DtoCoverageTest` (`ChatStatus::suspended`, `Subscription::$time`,
`Callback::$timestamp`/`$user` и фолбэк `callback.user` в `Update`, обе формы `FailedUserDetails`).

## Состав

- `src/Enum/ChatStatus.php`
- `src/Dto/Attachment.php`, `src/Dto/ContactAttachmentPayload.php`, `src/Dto/FileAttachmentPayload.php`,
  `src/Dto/VideoAttachmentPayload.php`, `src/Dto/VideoUrls.php`, `src/Dto/AudioAttachmentPayload.php`,
  `src/Dto/Subscription.php`, `src/Dto/Callback.php`, `src/Dto/Update.php`, `src/Dto/FailedUserDetails.php`,
  `src/Dto/AddChatMembersResult.php`, `src/ApiClient.php`
- `tests/Dto/AttachmentTest.php`, `tests/DtoCoverageTest.php`, `tests/WebhookHandlerTest.php`
- `docs/api-reference.md` (разделы 3, 8, 9), `README.md` («История изменений»)
