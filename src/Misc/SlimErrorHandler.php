<?php

declare(strict_types=1);

namespace EveSrp\Misc;

use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Handlers\ErrorHandler;

class SlimErrorHandler extends ErrorHandler
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
