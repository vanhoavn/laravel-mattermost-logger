<?php

namespace ThibaudDauce\MattermostLogger;

use Monolog\Logger;
use ThibaudDauce\Mattermost\Mattermost;
use Monolog\Handler\AbstractProcessingHandler;

class MattermostHandler extends AbstractProcessingHandler
{
    public function __construct(Mattermost $mattermost, $options = [])
    {
        $this->options = array_merge([
            'webhook' => null,
            'channel' => 'town-square',
            'icon_url' => null,
            'username' => 'Laravel Logs',
            'level' => Logger::INFO,
            'level_mention' => Logger::ERROR,
            'mentions' => ['@here'],
            'short_field_length' => 62,
            'max_attachment_length' => 6000,
        ], $options);

        $this->mattermost = $mattermost;
    }

    public function write(array $record): void
    {
        if ($record['level'] < $this->options['level']) {
            return;
        }

        $message = Message::fromArrayAndOptions($record, $this->options);

        $this->mattermost->send($message, $this->options['webhook']);
    }
}
