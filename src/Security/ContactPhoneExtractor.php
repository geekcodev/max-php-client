<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Security;

/**
 * Извлечение номера телефона из vCard контакта, полученного по кнопке
 * `request_contact` (поле `vcf_info`).
 *
 * Во **входящем** payload поля `vcf_phone` нет — оно есть только в исходящем
 * `ContactAttachmentRequestPayload`, который бот кладёт в своё вложение сам.
 * Поэтому номер берётся разбором `vcf_info`; проверку подписи делает
 * {@see ContactVerifier}.
 *
 * Значение `TEL` возвращается как есть: это не обязательно цифры (нормализованный
 * `urn:`, `tel:` URI, номер с внутренним расширением). Нормализации и валидации
 * номера здесь намеренно нет — иначе ядро отбрасывало бы значения, валидные для
 * бизнеса.
 *
 * Класс не логирует и не бросает исключений: `vcf_info` и номер — персональные
 * данные (OWASP A09), логирование факта действия остаётся вызывающему коду.
 */
final class ContactPhoneExtractor
{
    /**
     * Перевод строки в любой форме: реальные CRLF, CR, LF и их JSON-экранированные
     * литералы `\r\n`, `\n`, `\r` (двойное экранирование в транспорте).
     */
    private const string LINE_BREAK = '/\\\\r\\\\n|\\\\n|\\\\r|\r\n|\n|\r/';

    /**
     * Имя свойства — регистронезависимо, параметры (`;TYPE=CELL`, `;TYPE="work,voice"`)
     * пропускаются, значение берётся по первому двоеточию вне параметров. Допускается префикс
     * группы (`item1.TEL`, RFC 6350); кастомные свойства (`X-TEL`, `item1.X-TEL`) под шаблон
     * не подходят — у них нет точки перед именем свойства.
     */
    private const string TEL_PROPERTY = '/^(?:[A-Za-z0-9-]+\.)?TEL(?:;[^:]*)?:(.*)$/i';

    /**
     * Номер телефона из vCard или null, если свойства `TEL` с непустым значением нет.
     */
    public static function fromVcf(string $vcfInfo): ?string
    {
        if ($vcfInfo === '') {
            return null;
        }

        $lines = preg_split(self::LINE_BREAK, $vcfInfo) ?: [];

        foreach (self::unfold($lines) as $line) {
            if (preg_match(self::TEL_PROPERTY, $line, $matches) !== 1) {
                continue;
            }

            $phone = trim($matches[1]);

            if ($phone !== '') {
                return $phone;
            }
        }

        return null;
    }

    /**
     * Разворачивание свёрнутых строк vCard: строка, начинающаяся с пробела или
     * табуляции, продолжает предыдущую — ведущие пробелы здесь маркер свёртки,
     * а не часть значения. Без этого длинный номер разрывается.
     *
     * Отклонение от RFC 5545 §3.2: там снимается ровно один WSP, здесь — вся полоса
     * ведущих пробелов и табуляций. Для номера телефона разницы в реальных vCard
     * нет (свёртка всегда одним символом), а лишний пробел в номере хуже, чем его
     * отсутствие. Пробелы внутри значения при этом не трогаются.
     *
     * @param array<int, string> $lines
     *
     * @return list<string>
     */
    private static function unfold(array $lines): array
    {
        $unfolded = [];
        $current = null;

        foreach ($lines as $line) {
            if ($current !== null && (str_starts_with($line, ' ') || str_starts_with($line, "\t"))) {
                $current .= ltrim($line, " \t");

                continue;
            }

            if ($current !== null) {
                $unfolded[] = $current;
            }

            $current = $line;
        }

        if ($current !== null) {
            $unfolded[] = $current;
        }

        return $unfolded;
    }
}
