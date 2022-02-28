<?php

namespace App\EventListener;

use App\Exception\DeprecatedApiException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class DeprecatedApiExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $response = new Response();
        $response->setContent($exception->getMessage());

        if ($exception instanceof DeprecatedApiException) {
            $response->setStatusCode(Response::HTTP_GONE);
        } else {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $event->setResponse($response);
    }
}
