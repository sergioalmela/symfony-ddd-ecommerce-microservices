<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\EventListener;

use App\Order\Domain\Exception\OrderAlreadyExistsException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Shared\Domain\Exception\DomainException;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final readonly class ApiExceptionListener
{
    public function __construct(
        private LoggerInterface $logger,
        private bool $debug = false,
    ) {
    }

    public function onKernelException(ExceptionEvent $exceptionEvent): void
    {
        $throwable = $exceptionEvent->getThrowable();

        $this->logger->error(
            'API Exception: '.$throwable->getMessage(),
            [
                'exception' => $throwable,
                'request' => $exceptionEvent->getRequest()->getUri(),
            ]
        );

        $jsonResponse = $this->createApiErrorResponse($throwable);
        $exceptionEvent->setResponse($jsonResponse);
    }

    private function createApiErrorResponse(Throwable $throwable): JsonResponse
    {
        $statusCode = $this->getStatusCode($throwable);
        $errorData = [
            'statusCode' => $statusCode,
            'message' => $this->getErrorMessage($throwable),
            'timestamp' => new DateTimeImmutable()->format(DateTimeInterface::ATOM),
        ];

        if ($this->debug) {
            $errorData['debug'] = [
                'exception' => $throwable::class,
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'trace' => $throwable->getTraceAsString(),
            ];
        }

        return new JsonResponse($errorData, $statusCode);
    }

    private function getStatusCode(Throwable $throwable): int
    {
        if ($throwable instanceof HttpExceptionInterface) {
            return $throwable->getStatusCode();
        }

        if ($throwable instanceof DomainException) {
            return match (true) {
                $throwable instanceof OrderAlreadyExistsException => Response::HTTP_CONFLICT,
                $throwable instanceof OrderNotFoundException => Response::HTTP_NOT_FOUND,
                default => Response::HTTP_BAD_REQUEST,
            };
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    private function getErrorMessage(Throwable $throwable): string
    {
        if ($this->debug) {
            return $throwable->getMessage();
        }

        if ($throwable instanceof HttpExceptionInterface) {
            return $throwable->getMessage();
        }

        if ($throwable instanceof DomainException) {
            return $throwable->getMessage();
        }

        return 'An internal error occurred';
    }
}
