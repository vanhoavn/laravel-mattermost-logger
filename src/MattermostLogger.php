<?php

namespace ThibaudDauce\MattermostLogger;

use Monolog\Logger as Monolog;
use ThibaudDauce\Mattermost\Mattermost;

class MattermostLogger
{
    public $mattermost;

    public function __construct(Mattermost $mattermost)
    {
        $this->mattermost = $mattermost;
    }

    public function __invoke($options)
    {
        return new Monolog('mattermost', [new MattermostHandler($this->mattermost, $options)]);
    }
}
