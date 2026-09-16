<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests;

use GeekCo\MaxPhpClient\Security\ContactVerifier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ContactVerifierTest extends TestCase
{
    #[Test]
    public function it_verifies_contact_hash(): void
    {
        $token = 'secret';
        $vcf = 'BEGIN:VCARD\r\nVERSION:3.0\r\nN:John Doe\r\nEND:VCARD';

        $hash = hash_hmac('sha256', "BEGIN:VCARD\nVERSION:3.0\nN:John Doe\nEND:VCARD", $token);

        $verifier = new ContactVerifier($token);

        $this->assertTrue($verifier->verify($vcf, $hash));
    }

    #[Test]
    public function it_verifies_contact_hash_with_real_crlf_bytes(): void
    {
        $token = 'secret';
        $vcf = "BEGIN:VCARD\r\nVERSION:3.0\r\nN:John Doe\r\nEND:VCARD";

        $hash = hash_hmac('sha256', "BEGIN:VCARD\nVERSION:3.0\nN:John Doe\nEND:VCARD", $token);

        $verifier = new ContactVerifier($token);

        $this->assertTrue($verifier->verify($vcf, $hash));
    }

    #[Test]
    public function it_verifies_contact_hash_with_mixed_literal_and_real_crlf(): void
    {
        $token = 'secret';
        $vcf = "BEGIN:VCARD\r\nVERSION:3.0\nN:John Doe\r\nFULLNAME:John\r\nEND:VCARD";

        $hash = hash_hmac('sha256', "BEGIN:VCARD\nVERSION:3.0\nN:John Doe\nFULLNAME:John\nEND:VCARD", $token);

        $verifier = new ContactVerifier($token);

        $this->assertTrue($verifier->verify($vcf, $hash));
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
