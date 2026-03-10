<?php

namespace App\Notifications\Messages;

class TelegramMessage
{
    protected string $parseMode = 'HTML';

    protected bool $disableWebPagePreview = true;

    protected bool $disableNotification = false;

    protected ?int $replyToMessageId = null;

    /**
     * Create a new telegram message instance.
     */
    public function __construct(protected string $content = '')
    {
    }

    /**
     * Create a new telegram message instance.
     */
    public static function create(string $content = ''): static
    {
        return new static($content);
    }

    /**
     * Set the message content.
     */
    public function content(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Append content to the message.
     */
    public function line(string $line): static
    {
        $this->content .= $line."\n";

        return $this;
    }

    /**
     * Add an empty line.
     */
    public function lineBreak(): static
    {
        $this->content .= "\n";

        return $this;
    }

    /**
     * Add bold text.
     */
    public function bold(string $text): static
    {
        $this->content .= "<b>{$text}</b>";

        return $this;
    }

    /**
     * Add italic text.
     */
    public function italic(string $text): static
    {
        $this->content .= "<i>{$text}</i>";

        return $this;
    }

    /**
     * Add a link.
     */
    public function link(string $text, string $url): static
    {
        $this->content .= "<a href=\"{$url}\">{$text}</a>";

        return $this;
    }

    /**
     * Set parse mode to HTML.
     */
    public function html(): static
    {
        $this->parseMode = 'HTML';

        return $this;
    }

    /**
     * Set parse mode to Markdown.
     */
    public function markdown(): static
    {
        $this->parseMode = 'Markdown';

        return $this;
    }

    /**
     * Set parse mode to MarkdownV2.
     */
    public function markdownV2(): static
    {
        $this->parseMode = 'MarkdownV2';

        return $this;
    }

    /**
     * Enable web page preview.
     */
    public function enableWebPagePreview(): static
    {
        $this->disableWebPagePreview = false;

        return $this;
    }

    /**
     * Disable web page preview.
     */
    public function disableWebPagePreview(): static
    {
        $this->disableWebPagePreview = true;

        return $this;
    }

    /**
     * Send the message silently.
     */
    public function silent(): static
    {
        $this->disableNotification = true;

        return $this;
    }

    /**
     * Reply to a specific message.
     */
    public function replyTo(int $messageId): static
    {
        $this->replyToMessageId = $messageId;

        return $this;
    }

    /**
     * Get the message content.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Get the message options for the Telegram API.
     */
    public function getOptions(): array
    {
        $options = [
            'parse_mode' => $this->parseMode,
            'disable_web_page_preview' => $this->disableWebPagePreview,
            'disable_notification' => $this->disableNotification,
        ];

        if ($this->replyToMessageId) {
            $options['reply_to_message_id'] = $this->replyToMessageId;
        }

        return $options;
    }
}
