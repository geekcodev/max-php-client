<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests;

use GeekCo\MaxPhpClient\Security\ContactPhoneExtractor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ContactPhoneExtractorTest extends TestCase
{
    #[Test]
    public function it_extracts_a_phone_without_parameters(): void
    {
        $this->assertSame(
            '+79250557481',
            ContactPhoneExtractor::fromVcf("BEGIN:VCARD\r\nVERSION:3.0\r\nTEL:+79250557481\r\nEND:VCARD\r\n"),
        );
    }

    #[Test]
    public function it_extracts_a_phone_with_a_type_parameter(): void
    {
        $this->assertSame(
            '+79250557481',
            ContactPhoneExtractor::fromVcf("BEGIN:VCARD\r\nTEL;TYPE=CELL:+79250557481\r\nEND:VCARD\r\n"),
        );
    }

    #[Test]
    public function it_ignores_the_case_of_the_property_name_and_parameters(): void
    {
        $this->assertSame(
            '+79250557481',
            ContactPhoneExtractor::fromVcf("BEGIN:VCARD\r\ntel;type=work,voice:+79250557481\r\nEND:VCARD\r\n"),
        );
    }

    /**
     * Захват реального payload от Android-клиента, номер и имя обезличены.
     * Форма воспроизведена как есть: реальные CRLF, завершающий перевод строки,
     * `PRODID` ez-vcard, `TEL;TYPE=cell` строчными в параметре и без `+` в значении,
     * `TEL` до `FN`, не-ASCII в `FN`.
     */
    #[Test]
    public function it_extracts_a_phone_from_a_captured_android_vcard(): void
    {
        $this->assertSame('79250000000', ContactPhoneExtractor::fromVcf($this->capturedVcf()));
    }

    #[Test]
    public function it_keeps_the_captured_vcard_structure_intact(): void
    {
        $vcf = $this->capturedVcf();

        $this->assertStringStartsWith("BEGIN:VCARD\r\n", $vcf);
        $this->assertStringEndsWith("END:VCARD\r\n", $vcf);
        $this->assertStringContainsString("PRODID:ez-vcard 0.10.3\r\n", $vcf);
        $this->assertStringContainsString("TEL;TYPE=cell:79250000000\r\n", $vcf);
        $this->assertLessThan(
            (int) strpos($vcf, 'FN:'),
            (int) strpos($vcf, 'TEL;'),
            'В реальном payload TEL идёт раньше FN.',
        );
    }

    #[Test]
    public function it_extracts_the_same_phone_when_crlf_comes_as_a_literal(): void
    {
        $escaped = str_replace("\r\n", '\\r\\n', $this->capturedVcf());

        $this->assertSame('79250000000', ContactPhoneExtractor::fromVcf($escaped));
    }

    #[Test]
    public function it_extracts_a_phone_from_a_folded_line(): void
    {
        $this->assertSame(
            '+79250557481',
            ContactPhoneExtractor::fromVcf("BEGIN:VCARD\r\nTEL;TYPE=CELL:+7925\r\n 0557481\r\nEND:VCARD\r\n"),
        );
    }

    #[Test]
    public function it_extracts_a_phone_from_a_folded_line_with_a_tab(): void
    {
        $this->assertSame(
            '+74951234567',
            ContactPhoneExtractor::fromVcf("BEGIN:VCARD\r\nTEL:+7495\r\n\t1234567\r\nEND:VCARD\r\n"),
        );
    }

    /**
     * Отклонение от RFC 5545 §3.2: там снимается ровно один WSP, здесь — вся полоса
     * ведущих пробелов. Тест закрепляет выбранное поведение.
     */
    #[Test]
    public function it_drops_all_leading_whitespace_of_a_folded_line(): void
    {
        $this->assertSame(
            '+79250557481',
            ContactPhoneExtractor::fromVcf("BEGIN:VCARD\r\nTEL:+7925\r\n   0557481\r\nEND:VCARD\r\n"),
        );
    }

    #[Test]
    public function it_keeps_spaces_inside_a_value(): void
    {
        $this->assertSame(
            '+7 925 055 74 81',
            ContactPhoneExtractor::fromVcf("BEGIN:VCARD\r\nTEL;TYPE=CELL:+7 925 055 74 81\r\nEND:VCARD\r\n"),
        );
    }

    #[Test]
    public function it_extracts_a_phone_from_a_grouped_property(): void
    {
        $this->assertSame(
            '+79250557481',
            ContactPhoneExtractor::fromVcf("BEGIN:VCARD\r\nitem1.TEL;TYPE=CELL:+79250557481\r\nEND:VCARD\r\n"),
        );
    }

    #[Test]
    public function it_ignores_a_grouped_property_without_parameters(): void
    {
        $this->assertSame(
            '+79250557481',
            ContactPhoneExtractor::fromVcf("BEGIN:VCARD\r\nItem1.tel:+79250557481\r\nEND:VCARD\r\n"),
        );
    }

    #[Test]
    public function it_ignores_custom_properties_that_only_look_like_tel(): void
    {
        $this->assertNull(ContactPhoneExtractor::fromVcf("BEGIN:VCARD\r\nX-TEL:+79250557481\r\nEND:VCARD\r\n"));
        $this->assertNull(
            ContactPhoneExtractor::fromVcf("BEGIN:VCARD\r\nitem1.X-TEL:+79250557481\r\nEND:VCARD\r\n"),
        );
    }

    #[Test]
    public function it_takes_the_first_phone_of_several(): void
    {
        $this->assertSame('+79250000000', ContactPhoneExtractor::fromVcf(
            "BEGIN:VCARD\r\nTEL;TYPE=CELL:+79250000000\r\nTEL;TYPE=HOME:+74950000000\r\nEND:VCARD\r\n",
        ));
    }

    #[Test]
    public function it_trims_surrounding_whitespace_of_a_value(): void
    {
        $this->assertSame(
            '+79250557481',
            ContactPhoneExtractor::fromVcf("BEGIN:VCARD\r\nTEL;TYPE=CELL:  +79250557481 \t\r\nEND:VCARD\r\n"),
        );
    }

    #[Test]
    public function it_returns_null_when_a_tel_value_is_empty(): void
    {
        $this->assertNull(ContactPhoneExtractor::fromVcf("BEGIN:VCARD\r\nTEL;TYPE=CELL:\r\nEND:VCARD\r\n"));
        $this->assertNull(ContactPhoneExtractor::fromVcf("BEGIN:VCARD\r\nTEL:   \r\nEND:VCARD\r\n"));
    }

    #[Test]
    public function it_skips_an_empty_tel_and_takes_the_next_one(): void
    {
        $this->assertSame(
            '+79250557481',
            ContactPhoneExtractor::fromVcf("BEGIN:VCARD\r\nTEL:\r\nTEL;TYPE=HOME:+79250557481\r\nEND:VCARD\r\n"),
        );
    }

    #[Test]
    public function it_returns_null_for_a_vcard_without_tel(): void
    {
        $this->assertNull(ContactPhoneExtractor::fromVcf("BEGIN:VCARD\r\nFN:John Doe\r\nEND:VCARD\r\n"));
    }

    #[Test]
    public function it_returns_null_for_an_empty_input_and_garbage(): void
    {
        $this->assertNull(ContactPhoneExtractor::fromVcf(''));
        $this->assertNull(ContactPhoneExtractor::fromVcf('не vCard вовсе, {"json":true}'));
        $this->assertNull(ContactPhoneExtractor::fromVcf("\x00\x01\x02TEL:"));
    }

    #[Test]
    public function it_returns_a_phone_value_that_is_not_a_number(): void
    {
        $this->assertSame(
            'urn:tel:+7-925-055-74-81',
            ContactPhoneExtractor::fromVcf("BEGIN:VCARD\r\nTEL;TYPE=\"work,voice\":urn:tel:+7-925-055-74-81\r\nEND:VCARD"),
        );
    }

    /**
     * Ограничение шаблона `^TEL(?:;[^:]*)?:`: двоеточие внутри кавычек в параметре
     * обрывает параметры, и в значение попадает хвост параметра. Тест фиксирует
     * фактическое поведение, чтобы изменение было осознанным.
     */
    #[Test]
    public function it_reads_the_tail_of_a_parameter_containing_a_colon(): void
    {
        $this->assertSame(
            'work":+79250557481',
            ContactPhoneExtractor::fromVcf("BEGIN:VCARD\r\nTEL;LABEL=\"a:work\":+79250557481\r\nEND:VCARD\r\n"),
        );
    }

    private function capturedVcf(): string
    {
        $vcf = file_get_contents(__DIR__ . '/Fixtures/vcard/profile-android-real.vcf');
        $this->assertIsString($vcf);

        return $vcf;
    }
}
