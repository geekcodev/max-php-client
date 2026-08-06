<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests;

use GeekCo\MaxPhpClient\Security\WebAppDataValidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WebAppDataValidatorTest extends TestCase
{
    private const TOKEN = '123456:test-bot-token';

    /**
     * @return array{data: string, hash: string}
     */
    private function validWebAppData(): array
    {
        $launchParams = "auth_date=1771409719\n"
            . "chat={\"id\":12345,\"type\":\"DIALOG\"}\n"
            . "ip=192.168.0.1\n"
            . "query_id=4c0ab423-342b-4e45-aea4-2747dbc500cd\n"
            . "user={\"id\":67890,\"first_name\":\"Max\",\"last_name\":\"User\",\"username\":null,\"language_code\":\"ru\",\"photo_url\":null}";

        $secretKey = hash_hmac('sha256', self::TOKEN, 'WebAppData', binary: true);
        $hash = hash_hmac('sha256', $launchParams, $secretKey);

        // Порядок пар не алфавитный: валидатор должен сортировать сам.
        $data = implode('&', [
            'user=' . rawurlencode('{"id":67890,"first_name":"Max","last_name":"User","username":null,"language_code":"ru","photo_url":null}'),
            'ip=192.168.0.1',
            'chat=' . rawurlencode('{"id":12345,"type":"DIALOG"}'),
            'auth_date=1771409719',
            'query_id=4c0ab423-342b-4e45-aea4-2747dbc500cd',
            'hash=' . $hash,
        ]);

        return ['data' => $data, 'hash' => $hash];
    }

    #[Test]
    public function it_verifies_web_app_data(): void
    {
        $webAppData = $this->validWebAppData();

        $validator = new WebAppDataValidator(self::TOKEN);

        $this->assertTrue($validator->verify($webAppData['data']));
    }

    #[Test]
    public function it_verifies_web_app_data_from_url(): void
    {
        $webAppData = $this->validWebAppData();
        $url = 'https://example.com/app#'
            . 'WebAppData=' . rawurlencode($webAppData['data'])
            . '&WebAppPlatform=web&WebAppVersion=26.2.8';

        $validator = new WebAppDataValidator(self::TOKEN);

        $this->assertTrue($validator->verifyFromUrl($url));
    }

    #[Test]
    public function it_rejects_a_wrong_token(): void
    {
        $webAppData = $this->validWebAppData();

        $validator = new WebAppDataValidator('another-token');

        $this->assertFalse($validator->verify($webAppData['data']));
    }

    #[Test]
    public function it_rejects_tampered_data(): void
    {
        $webAppData = $this->validWebAppData();
        $tampered = str_replace('ip=192.168.0.1', 'ip=10.0.0.1', $webAppData['data']);

        $validator = new WebAppDataValidator(self::TOKEN);

        $this->assertFalse($validator->verify($tampered));
    }

    #[Test]
    public function it_rejects_data_without_a_hash(): void
    {
        $webAppData = $this->validWebAppData();
        $withoutHash = preg_replace('/&hash=[0-9a-f]+$/', '', $webAppData['data']);

        $validator = new WebAppDataValidator(self::TOKEN);

        $this->assertFalse($validator->verify($withoutHash));
    }

    #[Test]
    public function it_rejects_a_duplicate_hash(): void
    {
        $webAppData = $this->validWebAppData();
        $duplicate = $webAppData['data'] . '&hash=00';

        $validator = new WebAppDataValidator(self::TOKEN);

        $this->assertFalse($validator->verify($duplicate));
    }

    #[Test]
    public function it_rejects_empty_and_malformed_data(): void
    {
        $validator = new WebAppDataValidator(self::TOKEN);

        $this->assertFalse($validator->verify(''));
        $this->assertFalse($validator->verify('no-equals-sign'));
        $this->assertFalse($validator->verify('=value-without-key'));
    }

    #[Test]
    public function it_rejects_an_url_without_a_fragment(): void
    {
        $validator = new WebAppDataValidator(self::TOKEN);

        $this->assertFalse($validator->verifyFromUrl('https://example.com/app'));
    }

    #[Test]
    public function it_rejects_an_url_without_web_app_data(): void
    {
        $validator = new WebAppDataValidator(self::TOKEN);

        $this->assertFalse($validator->verifyFromUrl('https://example.com/app#WebAppPlatform=web'));
    }

    #[Test]
    public function it_rejects_an_url_with_an_empty_fragment(): void
    {
        $validator = new WebAppDataValidator(self::TOKEN);

        $this->assertFalse($validator->verifyFromUrl('https://example.com/app#'));
    }

    #[Test]
    public function it_rejects_an_url_with_duplicate_fragment_keys(): void
    {
        $webAppData = $this->validWebAppData();
        $url = 'https://example.com/app#'
            . 'WebAppData=' . rawurlencode($webAppData['data'])
            . '&WebAppData=' . rawurlencode($webAppData['data']);

        $validator = new WebAppDataValidator(self::TOKEN);

        $this->assertFalse($validator->verifyFromUrl($url));
    }

    #[Test]
    public function it_rejects_an_url_with_tampered_web_app_data(): void
    {
        $webAppData = $this->validWebAppData();
        $tampered = str_replace('ip=192.168.0.1', 'ip=10.0.0.1', $webAppData['data']);
        $url = 'https://example.com/app#WebAppData=' . rawurlencode($tampered);

        $validator = new WebAppDataValidator(self::TOKEN);

        $this->assertFalse($validator->verifyFromUrl($url));
    }
}
