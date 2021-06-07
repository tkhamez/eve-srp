<?php

declare(strict_types=1);

namespace EveSrp\Slim;

use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;

class ErrorHandler extends \Slim\Handlers\ErrorHandler
{
    protected function writeToErrorLog(): void
    {
        if (
            $this->exception instanceof HttpNotFoundException ||
            $this->exception instanceof HttpMethodNotAllowedException
        ) {
            $this->logError(
                $this->exception->getMessage() .
                ' Request: ' . $this->request->getMethod() . ' ' . $this->request->getUri()->getPath()
            );
        } else {
            parent::writeToErrorLog();
        }
    }
}
