<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Upload;

use GeekCo\MaxPhpClient\Dto\UploadResult;
use GeekCo\MaxPhpClient\Enum\UploadType;
use GeekCo\MaxPhpClient\Exception\InvalidArgumentException;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use GeekCo\MaxPhpClient\Internal\Json;
use GeekCo\MaxPhpClient\Transport\HttpClient;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

final class Uploader
{
    private const int CHUNK_SIZE = 8192;

    public function __construct(
        private readonly HttpClient $http,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    public function upload(UploadType $type, string $filePath): UploadResult
    {
        $path = realpath($filePath);
        if ($path === false || !is_file($path) || !is_readable($path)) {
            throw new InvalidArgumentException(sprintf('File "%s" does not exist or is not readable.', $filePath));
        }

        // Step 1: request upload URL from the API
        $result = $this->http->request('POST', '/uploads', ['type' => $type->value]);

        if (!\is_array($result)) {
            throw new InvalidResponseException('Expected a JSON object in the response.');
        }

        $url = Json::requiredString($result, 'url');
        $tokenFromStep1 = Json::string($result, 'token');

        // Step 2: upload file to the obtained URL
        $boundary = '------------------------' . bin2hex(random_bytes(8));
        $body = $this->multipart($boundary, basename($path), $this->mimeType($path), $path);

        $uploadResult = $this->http->request(
            'POST',
            '',
            rawBody: $body,
            headers: ['Content-Type' => 'multipart/form-data; boundary=' . $boundary],
            absoluteUri: $url,
        );

        $tokenFromStep2 = null;
        if (\is_array($uploadResult)) {
            $tokenFromStep2 = Json::string($uploadResult, 'token');
        }

        return new UploadResult(
            url: $url,
            token: $tokenFromStep2 ?? $tokenFromStep1,
        );
    }

    /**
     * Multipart-тела собирается в seekable php://temp-поток: файл копируется по чанкам
     * (потоковая передача, без загрузки в память целиком), а seekable-поток безопасен
     * для повторов запроса при ретраях.
     */
    private function multipart(
        string $boundary,
        string $fileName,
        string $contentType,
        string $filePath,
        string $fieldName = 'data',
    ): StreamInterface {
        $crlf = "\r\n";
        $resource = fopen('php://temp', 'r+');
        // @codeCoverageIgnoreStart
        if ($resource === false) {
            throw new InvalidArgumentException('Unable to create a temporary stream for upload.');
        }
        // @codeCoverageIgnoreEnd

        $stream = $this->streamFactory->createStreamFromResource($resource);
        $stream->write('--' . $boundary . $crlf
            . sprintf('Content-Disposition: form-data; name="%s"; filename="%s"', $fieldName, $fileName) . $crlf
            . 'Content-Type: ' . $contentType . $crlf
            . $crlf);

        $file = $this->streamFactory->createStreamFromFile($filePath, 'r');
        while (!$file->eof()) {
            $chunk = $file->read(self::CHUNK_SIZE);
            // @codeCoverageIgnoreStart
            if ($chunk === '') {
                break;
            }
            // @codeCoverageIgnoreEnd
            $stream->write($chunk);
        }

        $stream->write($crlf . '--' . $boundary . '--' . $crlf);
        $stream->rewind();

        return $stream;
    }

    private function mimeType(string $filePath): string
    {
        $mime = mime_content_type($filePath);
        if ($mime !== false && $mime !== '') {
            return $mime;
        }

        // @codeCoverageIgnoreStart
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'mp3' => 'audio/mpeg',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            default => 'application/octet-stream',
        };
        // @codeCoverageIgnoreEnd
    }
}
