<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Upload;

use GeekCo\MaxPhpClient\Dto\UploadResult;
use GeekCo\MaxPhpClient\Enum\UploadType;
use GeekCo\MaxPhpClient\Exception\InvalidArgumentException;
use GeekCo\MaxPhpClient\Transport\HttpClient;

final class Uploader
{
    public function __construct(
        private readonly HttpClient $http,
    ) {
    }

    public function upload(UploadType $type, string $filePath): UploadResult
    {
        $path = realpath($filePath);
        if ($path === false || !is_file($path) || !is_readable($path)) {
            throw new InvalidArgumentException(sprintf('File "%s" does not exist or is not readable.', $filePath));
        }

        $contents = file_get_contents($path);
        // @codeCoverageIgnoreStart
        if ($contents === false) {
            throw new InvalidArgumentException(sprintf('Unable to read file "%s".', $filePath));
        }
        // @codeCoverageIgnoreEnd

        $boundary = '------------------------' . bin2hex(random_bytes(8));

        $body = $this->multipart($boundary, basename($path), $this->mimeType($path), $contents);

        $result = $this->http->request(
            'POST',
            '/uploads',
            ['type' => $type->value],
            rawBody: $body,
            headers: ['Content-Type' => 'multipart/form-data; boundary=' . $boundary],
        );

        if (!\is_array($result)) {
            throw new \GeekCo\MaxPhpClient\Exception\InvalidResponseException('Expected a JSON object in the response.');
        }

        return UploadResult::fromArray($result);
    }

    private function multipart(string $boundary, string $fileName, string $contentType, string $contents): string
    {
        $crlf = "\r\n";

        return '--' . $boundary . $crlf
            . sprintf('Content-Disposition: form-data; name="file"; filename="%s"', $fileName) . $crlf
            . 'Content-Type: ' . $contentType . $crlf
            . $crlf
            . $contents
            . $crlf
            . '--' . $boundary . '--' . $crlf;
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
