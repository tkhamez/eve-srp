<?php

declare(strict_types=1);

namespace Brave\EveSrp;

class FlashMessage
{
    const TYPE_SUCCESS = 'success';
    const TYPE_INFO = 'info';
    const TYPE_WARNING = 'warning';
    const TYPE_DANGER = 'danger';

    /**
     * @var SessionHandler
     */
    private $sessionHandler;

    public function __construct(SessionHandler $sessionHandler)
    {
        $this->sessionHandler = $sessionHandler;
    }

    public function addMessage(string $message, string $type = self::TYPE_INFO)
    {
        $messages = $this->sessionHandler->get('flash-messages', []);
        $messages[] = [$message, $type];
        $this->sessionHandler->set('flash-messages', $messages);
    }

    /**
     * @return array[]
     */
    public function getMessages(): array
    {
        $messages = $this->sessionHandler->get('flash-messages', []);
        $this->sessionHandler->set('flash-messages', []);
        return $messages;
    }
}
