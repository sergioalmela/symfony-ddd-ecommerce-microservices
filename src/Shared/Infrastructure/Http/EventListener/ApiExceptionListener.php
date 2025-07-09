<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\EventListener;

use App\Order\Domain\Exception\OrderAlreadyExistsException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Shared\Domain\Exception\DomainException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final readonly class ApiExceptionListener
{
    public function __construct(
        private LoggerInterface $logger,
        private bool $debug = false,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $this->logger->error(
            'API Exception: '.$exception->getMessage(),
            [
                'exception' => $exception,
                'request' => $event->getRequest()->getUri(),
            ]
        );

        $response = $this->createApiErrorResponse($exception);
        $event->setResponse($response);
    }

    private function createApiErrorResponse(\Throwable $exception): JsonResponse
    {
        $statusCode = $this->getStatusCode($exception);
        $errorData = [
            'statusCode' => $statusCode,
            'message' => $this->getErrorMessage($exception),
            'timestamp' => new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
        ];

        if ($this->debug) {
            $errorData['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        return new JsonResponse($errorData, $statusCode);
    }

    private function getStatusCode(\Throwable $exception): int
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof DomainException) {
            return match (true) {
                $exception instanceof OrderAlreadyExistsException => Response::HTTP_CONFLICT,
                $exception instanceof OrderNotFoundException => Response::HTTP_NOT_FOUND,
                default => Response::HTTP_BAD_REQUEST,
            };
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    private function getErrorMessage(\Throwable $exception): string
    {
        if ($this->debug) {
            return $exception->getMessage();
        }

        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getMessage();
        }

        if ($exception instanceof DomainException) {
            return $exception->getMessage();
        }

        return 'An internal error occurred';
    }
}
