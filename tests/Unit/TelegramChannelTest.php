<?php

use App\Notifications\Messages\TelegramMessage;

describe('TelegramMessage', function (): void {
    it('creates a message with content', function (): void {
        $message = new TelegramMessage('Hello World');

        expect($message->getContent())->toBe('Hello World');
    });

    it('creates message using static method', function (): void {
        $message = TelegramMessage::create('Hello');

        expect($message)->toBeInstanceOf(TelegramMessage::class);
        expect($message->getContent())->toBe('Hello');
    });

    it('sets content using fluent method', function (): void {
        $message = TelegramMessage::create()
            ->content('New content');

        expect($message->getContent())->toBe('New content');
    });

    it('appends lines to content', function (): void {
        $message = TelegramMessage::create()
            ->line('Line 1')
            ->line('Line 2');

        expect($message->getContent())->toBe("Line 1\nLine 2\n");
    });

    it('adds line breaks', function (): void {
        $message = TelegramMessage::create()
            ->line('Line 1')
            ->lineBreak()
            ->line('Line 2');

        expect($message->getContent())->toBe("Line 1\n\nLine 2\n");
    });

    it('adds bold text', function (): void {
        $message = TelegramMessage::create()
            ->bold('Bold Text');

        expect($message->getContent())->toBe('<b>Bold Text</b>');
    });

    it('adds italic text', function (): void {
        $message = TelegramMessage::create()
            ->italic('Italic Text');

        expect($message->getContent())->toBe('<i>Italic Text</i>');
    });

    it('adds links', function (): void {
        $message = TelegramMessage::create()
            ->link('Click here', 'https://example.com');

        expect($message->getContent())->toBe('<a href="https://example.com">Click here</a>');
    });

    it('defaults to HTML parse mode', function (): void {
        $message = TelegramMessage::create();
        $options = $message->getOptions();

        expect($options['parse_mode'])->toBe('HTML');
    });

    it('can set markdown parse mode', function (): void {
        $message = TelegramMessage::create()->markdown();
        $options = $message->getOptions();

        expect($options['parse_mode'])->toBe('Markdown');
    });

    it('can set markdownV2 parse mode', function (): void {
        $message = TelegramMessage::create()->markdownV2();
        $options = $message->getOptions();

        expect($options['parse_mode'])->toBe('MarkdownV2');
    });

    it('disables web page preview by default', function (): void {
        $message = TelegramMessage::create();
        $options = $message->getOptions();

        expect($options['disable_web_page_preview'])->toBeTrue();
    });

    it('can enable web page preview', function (): void {
        $message = TelegramMessage::create()->enableWebPagePreview();
        $options = $message->getOptions();

        expect($options['disable_web_page_preview'])->toBeFalse();
    });

    it('notifications are enabled by default', function (): void {
        $message = TelegramMessage::create();
        $options = $message->getOptions();

        expect($options['disable_notification'])->toBeFalse();
    });

    it('can send silently', function (): void {
        $message = TelegramMessage::create()->silent();
        $options = $message->getOptions();

        expect($options['disable_notification'])->toBeTrue();
    });

    it('can set reply to message id', function (): void {
        $message = TelegramMessage::create()->replyTo(123);
        $options = $message->getOptions();

        expect($options['reply_to_message_id'])->toBe(123);
    });

    it('does not include reply_to_message_id when not set', function (): void {
        $message = TelegramMessage::create();
        $options = $message->getOptions();

        expect($options)->not->toHaveKey('reply_to_message_id');
    });

    it('supports method chaining', function (): void {
        $message = TelegramMessage::create('Start')
            ->line('Line 1')
            ->bold('Bold')
            ->lineBreak()
            ->italic('Italic')
            ->link('Link', 'https://example.com')
            ->markdown()
            ->silent()
            ->replyTo(456);

        expect($message)->toBeInstanceOf(TelegramMessage::class);
        expect($message->getOptions()['parse_mode'])->toBe('Markdown');
        expect($message->getOptions()['disable_notification'])->toBeTrue();
        expect($message->getOptions()['reply_to_message_id'])->toBe(456);
    });
});
