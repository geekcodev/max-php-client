<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests\Dto;

use GeekCo\MaxPhpClient\Dto\Attachment;
use GeekCo\MaxPhpClient\Dto\Attachment\AudioAttachment;
use GeekCo\MaxPhpClient\Dto\Attachment\ContactAttachment;
use GeekCo\MaxPhpClient\Dto\Attachment\FileAttachment;
use GeekCo\MaxPhpClient\Dto\Attachment\InlineKeyboardAttachment;
use GeekCo\MaxPhpClient\Dto\Attachment\LocationAttachment;
use GeekCo\MaxPhpClient\Dto\Attachment\PhotoAttachment;
use GeekCo\MaxPhpClient\Dto\Attachment\ShareAttachment;
use GeekCo\MaxPhpClient\Dto\Attachment\StickerAttachment;
use GeekCo\MaxPhpClient\Dto\Attachment\VideoAttachment;
use GeekCo\MaxPhpClient\Dto\AttachmentRequest;
use GeekCo\MaxPhpClient\Dto\AttachmentRequest\AudioAttachmentRequest;
use GeekCo\MaxPhpClient\Dto\AttachmentRequest\ContactAttachmentRequest;
use GeekCo\MaxPhpClient\Dto\AttachmentRequest\FileAttachmentRequest;
use GeekCo\MaxPhpClient\Dto\AttachmentRequest\InlineKeyboardAttachmentRequest;
use GeekCo\MaxPhpClient\Dto\AttachmentRequest\LocationAttachmentRequest;
use GeekCo\MaxPhpClient\Dto\AttachmentRequest\PhotoAttachmentRequest;
use GeekCo\MaxPhpClient\Dto\AttachmentRequest\ShareAttachmentRequest;
use GeekCo\MaxPhpClient\Dto\AttachmentRequest\StickerAttachmentRequest;
use GeekCo\MaxPhpClient\Dto\AttachmentRequest\VideoAttachmentRequest;
use GeekCo\MaxPhpClient\Dto\Button;
use GeekCo\MaxPhpClient\Dto\Button\CallbackButton;
use GeekCo\MaxPhpClient\Dto\Button\ClipboardButton;
use GeekCo\MaxPhpClient\Dto\Button\LinkButton;
use GeekCo\MaxPhpClient\Dto\Button\MessageButton;
use GeekCo\MaxPhpClient\Dto\Button\OpenAppButton;
use GeekCo\MaxPhpClient\Dto\Button\RequestContactButton;
use GeekCo\MaxPhpClient\Dto\Button\RequestGeoLocationButton;
use GeekCo\MaxPhpClient\Dto\ContactAttachmentPayload;
use GeekCo\MaxPhpClient\Dto\ContactAttachmentRequestPayload;
use GeekCo\MaxPhpClient\Dto\InlineKeyboardAttachmentRequestPayload;
use GeekCo\MaxPhpClient\Dto\Keyboard;
use GeekCo\MaxPhpClient\Dto\MediaAttachmentPayload;
use GeekCo\MaxPhpClient\Dto\PhotoAttachmentPayload;
use GeekCo\MaxPhpClient\Dto\PhotoAttachmentRequestPayload;
use GeekCo\MaxPhpClient\Dto\ShareAttachmentPayload;
use GeekCo\MaxPhpClient\Dto\StickerAttachmentPayload;
use GeekCo\MaxPhpClient\Dto\StickerAttachmentRequestPayload;
use GeekCo\MaxPhpClient\Dto\UploadedInfo;
use GeekCo\MaxPhpClient\Dto\VideoThumbnail;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AttachmentTest extends TestCase
{
    #[Test]
    public function it_parses_a_photo_attachment(): void
    {
        $attachment = Attachment::fromArray([
            'type' => 'image',
            'payload' => ['photo_id' => 1, 'token' => 't', 'url' => 'https://i'],
        ]);

        $this->assertInstanceOf(PhotoAttachment::class, $attachment);
        $this->assertSame(1, $attachment->payload->photoId);
        $this->assertSame('image', $attachment->toArray()['type']);
    }

    #[Test]
    public function it_parses_video_metadata_from_the_payload(): void
    {
        $attachment = Attachment::fromArray([
            'type' => 'video',
            'payload' => ['url' => 'https://v', 'token' => 'tv'],
            'thumbnail' => ['url' => 'https://thumb'],
            'width' => 640,
            'height' => 480,
            'duration' => 12,
        ]);

        $this->assertInstanceOf(VideoAttachment::class, $attachment);
        $this->assertSame('https://v', $attachment->payload->url);
        $this->assertSame(640, $attachment->width);
        $this->assertInstanceOf(VideoThumbnail::class, $attachment->thumbnail);
        $this->assertSame('video', $attachment->toArray()['type']);
    }

    #[Test]
    public function it_reads_video_metadata_from_the_nested_payload_as_fallback(): void
    {
        $attachment = Attachment::fromArray([
            'type' => 'video',
            'payload' => [
                'url' => 'https://v',
                'token' => 'tv',
                'width' => 100,
                'height' => 50,
                'thumbnail' => ['url' => 'https://thumb'],
            ],
        ]);

        $this->assertSame(100, $attachment->width);
        $this->assertSame(50, $attachment->height);
        $this->assertSame('https://thumb', $attachment->thumbnail?->url);
    }

    #[Test]
    public function it_parses_audio_transcription(): void
    {
        $attachment = Attachment::fromArray([
            'type' => 'audio',
            'payload' => ['url' => 'https://a', 'token' => 'ta'],
            'transcription' => 'hello',
        ]);

        $this->assertInstanceOf(AudioAttachment::class, $attachment);
        $this->assertSame('hello', $attachment->transcription);
    }

    #[Test]
    public function it_parses_file_filename_and_size_with_payload_fallback(): void
    {
        $attachment = Attachment::fromArray([
            'type' => 'file',
            'payload' => ['url' => 'https://f', 'token' => 'tf', 'size' => 10],
        ]);

        $this->assertInstanceOf(FileAttachment::class, $attachment);
        $this->assertSame(10, $attachment->size);
        $this->assertNull($attachment->filename);
    }

    #[Test]
    public function it_parses_sticker_without_dimensions(): void
    {
        $attachment = Attachment::fromArray([
            'type' => 'sticker',
            'payload' => ['code' => 'c', 'url' => 'https://s'],
        ]);

        $this->assertInstanceOf(StickerAttachment::class, $attachment);
        $this->assertSame('c', $attachment->payload->code);
        $this->assertNull($attachment->width);
    }

    #[Test]
    public function it_parses_contact_attachment(): void
    {
        $attachment = Attachment::fromArray([
            'type' => 'contact',
            'payload' => ['hash' => 'h', 'vcf_info' => 'BEGIN:VCARD', 'max_info' => ['name' => 'Ivan']],
        ]);

        $this->assertInstanceOf(ContactAttachment::class, $attachment);
        $this->assertInstanceOf(ContactAttachmentPayload::class, $attachment->payload);
        $this->assertSame('h', $attachment->payload->hash);
    }

    #[Test]
    public function it_parses_inline_keyboard_attachment(): void
    {
        $attachment = Attachment::fromArray([
            'type' => 'inline_keyboard',
            'payload' => ['buttons' => [[['type' => 'callback', 'text' => 'Go', 'payload' => 'p']]]],
        ]);

        $this->assertInstanceOf(InlineKeyboardAttachment::class, $attachment);
        $this->assertInstanceOf(Keyboard::class, $attachment->payload);
        $this->assertInstanceOf(CallbackButton::class, $attachment->payload->buttons[0][0]);
    }

    #[Test]
    public function it_parses_share_attachment(): void
    {
        $attachment = Attachment::fromArray([
            'type' => 'share',
            'payload' => ['url' => 'https://s', 'token' => 'ts'],
            'title' => 'Title',
            'description' => 'Desc',
            'image_url' => 'https://i',
        ]);

        $this->assertInstanceOf(ShareAttachment::class, $attachment);
        $this->assertSame('Title', $attachment->title);
        $this->assertSame('https://i', $attachment->imageUrl);
    }

    #[Test]
    public function it_parses_location_attachment_without_payload(): void
    {
        $attachment = Attachment::fromArray(['type' => 'location', 'latitude' => 1.5, 'longitude' => 2.5]);

        $this->assertInstanceOf(LocationAttachment::class, $attachment);
        $this->assertSame(1.5, $attachment->latitude);
        $this->assertSame('location', $attachment->toArray()['type']);
    }

    #[Test]
    public function it_rejects_an_unknown_attachment_type(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Unsupported attachment type "poll".');

        Attachment::fromArray(['type' => 'poll']);
    }

    #[Test]
    public function it_parses_attachment_requests(): void
    {
        $photo = AttachmentRequest::fromArray(['type' => 'image', 'payload' => ['token' => 't']]);
        $video = AttachmentRequest::fromArray(['type' => 'video', 'payload' => ['token' => 't']]);
        $audio = AttachmentRequest::fromArray(['type' => 'audio', 'payload' => ['token' => 't']]);
        $file = AttachmentRequest::fromArray(['type' => 'file', 'payload' => ['token' => 't']]);
        $sticker = AttachmentRequest::fromArray(['type' => 'sticker', 'payload' => ['code' => 'c']]);
        $contact = AttachmentRequest::fromArray(['type' => 'contact', 'payload' => ['vcf_info' => 'v']]);
        $keyboard = AttachmentRequest::fromArray(['type' => 'inline_keyboard', 'payload' => ['buttons' => [[['type' => 'message', 'text' => 'M']]]]]);
        $share = AttachmentRequest::fromArray(['type' => 'share', 'payload' => ['url' => 'https://s']]);
        $location = AttachmentRequest::fromArray(['type' => 'location', 'latitude' => 1.0, 'longitude' => 2.0]);

        $this->assertInstanceOf(PhotoAttachmentRequest::class, $photo);
        $this->assertInstanceOf(VideoAttachmentRequest::class, $video);
        $this->assertInstanceOf(AudioAttachmentRequest::class, $audio);
        $this->assertInstanceOf(FileAttachmentRequest::class, $file);
        $this->assertInstanceOf(StickerAttachmentRequest::class, $sticker);
        $this->assertInstanceOf(ContactAttachmentRequest::class, $contact);
        $this->assertInstanceOf(InlineKeyboardAttachmentRequest::class, $keyboard);
        $this->assertInstanceOf(ShareAttachmentRequest::class, $share);
        $this->assertInstanceOf(LocationAttachmentRequest::class, $location);
        $this->assertSame('location', $location->toArray()['type']);
    }

    #[Test]
    public function it_roundtrips_request_payloads(): void
    {
        $photo = new PhotoAttachmentRequestPayload(token: 't', url: 'https://u');
        $this->assertSame(['token' => 't', 'url' => 'https://u'], $photo->toArray());

        $sticker = new StickerAttachmentRequestPayload('c');
        $this->assertSame(['code' => 'c'], $sticker->toArray());

        $contact = new ContactAttachmentRequestPayload(name: 'Ivan', contactId: 1, vcfInfo: 'v', vcfPhone: '7');
        $this->assertSame('Ivan', $contact->toArray()['name']);

        $keyboard = new InlineKeyboardAttachmentRequestPayload([[new MessageButton('M')]]);
        $this->assertSame('message', $keyboard->toArray()['buttons'][0][0]['type']);

        $uploaded = new UploadedInfo('token');
        $this->assertSame(['token' => 'token'], $uploaded->toArray());

        $share = new ShareAttachmentPayload('https://s', 't');
        $this->assertSame('https://s', $share->toArray()['url']);
    }

    #[Test]
    public function it_roundtrips_all_buttons(): void
    {
        $buttons = [
            new CallbackButton('C', 'p'),
            new LinkButton('L', 'https://l'),
            new MessageButton('M'),
            new RequestContactButton('R'),
            new RequestGeoLocationButton('G', true),
            new OpenAppButton('O', 'app', 'p', 5),
            new ClipboardButton('B', 'p'),
        ];

        foreach ($buttons as $button) {
            $parsed = Button::fromArray($button->toArray());
            $this->assertSame($button->toArray(), $parsed->toArray());
        }

        $this->assertTrue(RequestGeoLocationButton::fromArray(['text' => 'G', 'quick' => true])->quick);
        $this->assertSame('https://l', LinkButton::fromArray(['text' => 'L', 'url' => 'https://l'])->url);
        $this->assertSame('app', OpenAppButton::fromArray(['text' => 'O', 'web_app' => 'app'])->webApp);
    }

    #[Test]
    public function it_rejects_an_unknown_button_type(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Unsupported button type "video_note".');

        Button::fromArray(['type' => 'video_note', 'text' => 'X']);
    }

    #[Test]
    public function it_parses_keyboard_and_media_payloads(): void
    {
        $keyboard = Keyboard::fromArray(['buttons' => [[['type' => 'link', 'text' => 'L', 'url' => 'https://l']]]]);
        $this->assertInstanceOf(LinkButton::class, $keyboard->buttons[0][0]);

        $media = MediaAttachmentPayload::fromArray(['url' => 'https://m', 'token' => 't']);
        $this->assertSame('https://m', $media->url);

        $photo = PhotoAttachmentPayload::fromArray(['photo_id' => 1, 'token' => 't', 'url' => 'https://p']);
        $this->assertSame(1, $photo->photoId);

        $sticker = StickerAttachmentPayload::fromArray(['url' => 'https://s', 'code' => 'c']);
        $this->assertSame('c', $sticker->code);

        $thumbnail = VideoThumbnail::fromArray(['url' => 'https://t', 'width' => 1, 'height' => 2]);
        $this->assertSame(1, $thumbnail->width);
    }

    #[Test]
    public function it_rejects_invalid_required_payload_fields(): void
    {
        $this->expectException(InvalidResponseException::class);

        MediaAttachmentPayload::fromArray(['url' => 'https://m']);
    }
}
