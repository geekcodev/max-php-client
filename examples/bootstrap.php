<?php

declare(strict_types=1);

use GeekCo\MaxPhpClient\ApiClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Фабрика клиента. Токен берётся из переменной окружения MAX_API_TOKEN
 * (например, из .env: source .env перед запуском).
 */
function max_client(): ApiClient
{
    $factory = new HttpFactory();

    return ApiClient::create(
        httpClient: new GuzzleClient(),
        requestFactory: $factory,
        streamFactory: $factory,
        uriFactory: $factory,
        accessToken: (string) getenv('MAX_API_TOKEN'),
    );
}
