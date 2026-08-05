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
    public function it_rejects_a_wrong_hash(): void
    {
        $verifier = new ContactVerifier('secret');

        $this->assertFalse($verifier->verify('BEGIN:VCARD', hash_hmac('sha256', 'BEGIN:VCARD', 'other')));
    }
}
