<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests;

use GeekCo\MaxPhpClient\Security\ContactVerifier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ContactVerifierTest extends TestCase
{
    #[Test]
    public function it_verifies_contact_hash_over_raw_vcf_bytes(): void
    {
        $token = 'secret';
        $vcf = "BEGIN:VCARD\r\nVERSION:3.0\r\nN:John Doe\r\nEND:VCARD";

        $hash = hash_hmac('sha256', $vcf, $token);

        $this->assertTrue((new ContactVerifier($token))->verify($vcf, $hash));
    }

    #[Test]
    public function it_verifies_contact_hash_over_literal_crlf_restored_to_crlf(): void
    {
        $token = 'secret';
        $vcf = 'BEGIN:VCARD\r\nVERSION:3.0\r\nN:John Doe\r\nEND:VCARD';

        $hash = hash_hmac('sha256', "BEGIN:VCARD\r\nVERSION:3.0\r\nN:John Doe\r\nEND:VCARD", $token);

        $this->assertTrue((new ContactVerifier($token))->verify($vcf, $hash));
    }

    #[Test]
    public function it_verifies_contact_hash_with_trailing_crlf_as_sent_by_max(): void
    {
        $token = 'secret';
        $vcf = "BEGIN:VCARD\r\nVERSION:3.0\r\nTEL;TYPE=cell:79250557481\r\nFN:Евгений\r\nEND:VCARD\r\n";

        $hash = hash_hmac('sha256', $vcf, $token);

        $this->assertTrue((new ContactVerifier($token))->verify($vcf, $hash));
    }

    #[Test]
    public function it_rejects_a_wrong_hash(): void
    {
        $verifier = new ContactVerifier('secret');

        $this->assertFalse($verifier->verify('BEGIN:VCARD', hash_hmac('sha256', 'BEGIN:VCARD', 'other')));
    }

    #[Test]
    public function it_verifies_contact_hash_in_standard_base64(): void
    {
        $expected = hash_hmac('sha256', 'BEGIN:VCARD', 'secret', true);

        $this->assertTrue((new ContactVerifier('secret'))->verify(
            'BEGIN:VCARD',
            base64_encode($expected),
        ));
    }

    #[Test]
    public function it_verifies_contact_hash_in_standard_base64_without_padding(): void
    {
        $expected = hash_hmac('sha256', 'BEGIN:VCARD', 'secret', true);

        $this->assertTrue((new ContactVerifier('secret'))->verify(
            'BEGIN:VCARD',
            rtrim(base64_encode($expected), '='),
        ));
    }

    #[Test]
    public function it_verifies_contact_hash_in_url_safe_base64(): void
    {
        $expected = hash_hmac('sha256', 'BEGIN:VCARD', 'secret', true);

        $this->assertTrue((new ContactVerifier('secret'))->verify(
            'BEGIN:VCARD',
            strtr(base64_encode($expected), '+/', '-_'),
        ));
    }

    #[Test]
    public function it_verifies_contact_hash_in_url_safe_base64_without_padding(): void
    {
        $expected = hash_hmac('sha256', 'BEGIN:VCARD', 'secret', true);

        $this->assertTrue((new ContactVerifier('secret'))->verify(
            'BEGIN:VCARD',
            rtrim(strtr(base64_encode($expected), '+/', '-_'), '='),
        ));
    }

    #[Test]
    public function it_rejects_a_garbage_hash(): void
    {
        $this->assertFalse((new ContactVerifier('secret'))->verify('BEGIN:VCARD', '!!!not-a-hash!!!'));
    }

    #[Test]
    public function it_rejects_an_empty_hash_or_vcf(): void
    {
        $verifier = new ContactVerifier('secret');

        $this->assertFalse($verifier->verify('', hash_hmac('sha256', '', 'secret')));
        $this->assertFalse($verifier->verify('BEGIN:VCARD', ''));
    }
}
