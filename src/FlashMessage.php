<?php

declare(strict_types=1);

namespace EveSrp;

use SlimSession\Helper;

class FlashMessage
{
    const TYPE_SUCCESS = 'success';
    const TYPE_INFO = 'info';
    const TYPE_WARNING = 'warning';
    const TYPE_DANGER = 'danger';

    public function __construct(private Helper $session)
    {
    }

    public function addMessage(string $message, string $type = self::TYPE_INFO): void
    {
        $messages = $this->session->get('flash-messages', []);
        $messages[] = [$message, $type];
        $this->session->set('flash-messages', $messages);
    }

    /**
     * @return array[]
     */
    public function getMessages(): array
    {
        $messages = $this->session->get('flash-messages', []);
        $this->session->set('flash-messages', []);
        return $messages;
    }
}
