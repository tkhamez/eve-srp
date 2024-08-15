<?php

declare(strict_types=1);

namespace EveSrp\Misc;

use Slim\Exception\HttpException;
use Slim\Handlers\ErrorHandler;

class SlimErrorHandler extends ErrorHandler
{
    protected function writeToErrorLog(): void
    {
        if ($this->exception instanceof HttpException) {
            $this->logError(
                get_class($this->exception) . ': ' .
                $this->exception->getMessage() .
                ' Request: ' . $this->request->getMethod() . ' ' . $this->request->getUri()->getPath()
            );
        } else {
            parent::writeToErrorLog();
        }
    }
}
