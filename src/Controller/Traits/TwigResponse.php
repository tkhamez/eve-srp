<?php

declare(strict_types=1);

namespace EveSrp\Controller\Traits;

use Psr\Http\Message\ResponseInterface;
use Twig\Environment;
use Twig\Error\Error;

trait TwigResponse
{
    private Environment $twig;

    protected function twigResponse(Environment $twig)
    {
        $this->twig = $twig;
    }

    protected function render(ResponseInterface $response, $template, array $variables = []): ResponseInterface
    {
        try {
            $content = $this->twig->render($template, $variables);
        } catch (Error $e) {
            \error_log("Template: $template, Exception: " . $e->getMessage());
            $content = 'Error rendering the template.';
        }
        $response->getBody()->write($content);

        return $response;
    }
}
