<?php

namespace ThibaudDauce\MattermostLogger;

use Illuminate\Support\Facades\URL;
use ThibaudDauce\Mattermost\Attachment;
use ThibaudDauce\Mattermost\Message as MattermostMessage;

class Message
{
    public $record;
    public $options;
    public $message;

    public function __construct($record, $options)
    {
        $this->record = $record;
        $this->options = $options;
    }

    public static function fromArrayAndOptions($record, $options)
    {
        $messageBuilder = new self($record, $options);

        $messageBuilder->createBaseMessage();
        $messageBuilder->addTitleText();
        $messageBuilder->addExceptionAttachment();
        $messageBuilder->addContextAttachment();

        return $messageBuilder->message;
    }

    public function createBaseMessage()
    {
        $this->message = (new MattermostMessage)
            ->channel($this->options['channel'])
            ->username($this->options['username'])
            ->iconUrl(URL::to($this->options['icon_url']));
    }

    public function addTitleText()
    {
        $this->message->text($this->title());
    }

    public function title()
    {
        $title = sprintf('**[%s]** %s', $this->record['level_name'], $this->record['message']);

        if ($this->shouldMention()) {
            $title .= sprintf(' (ping %s)', $this->mentions());
        }

        return $title;
    }

    public function mentions()
    {
        $mentions = array_map(function ($mention) {
            return str_start($mention, '@');
        }, $this->options['mentions']);

        return implode(', ', $mentions);
    }

    public function addExceptionAttachment()
    {
        if (! $exception = $this->popException()) {
            return;
        }

        $this->attachment(function (Attachment $attachment) use ($exception) {
            $attachment->text(
                substr($exception->getTraceAsString(), 0, $this->options['max_attachment_length'])
            );
        });
    }

    public function popException()
    {
        if (! isset($this->record['context']['exception'])) {
            return;
        }

        $exception = $this->record['context']['exception'];
        unset($this->record['context']['exception']);

        return $exception;
    }

    public function addContextAttachment()
    {
        if (! isset($this->record['context']) or empty($this->record['context'])) {
            return;
        }

        $this->attachment(function (Attachment $attachment) {
            foreach ($this->record['context'] as $key => $value) {
                $stringifyValue = is_string($value) ? $value : json_encode($value);
                $attachment->field($key, $stringifyValue, strlen($stringifyValue) < $this->options['short_field_length']);
            }
        });
    }

    public function shouldMention()
    {
        return $this->record['level'] >= $this->options['level_mention'];
    }

    public function attachment($callback)
    {
        $this->message->attachment(function (Attachment $attachment) use ($callback) {
            if ($this->shouldMention()) {
                $attachment->error();
            }

            $callback($attachment);
        });
    }
}
