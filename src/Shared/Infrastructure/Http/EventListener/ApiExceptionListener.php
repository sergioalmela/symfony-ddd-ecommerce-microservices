<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ApiExceptionListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly bool $debug = false
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        // Only handle API routes (you can customize this logic)
        if (!$this->isApiRequest($request)) {
            return;
        }

        $exception = $event->getThrowable();

        $this->logger->error('API Exception: ' . $exception->getMessage(), [
            'exception' => $exception,
            'request' => $request->getUri(),
        ]);

        $response = $this->createApiErrorResponse($exception);
        $event->setResponse($response);
    }

    private function isApiRequest($request): bool
    {
        // Check if request is for API endpoints
        return str_starts_with($request->getPathInfo(), '/api/') ||
            str_contains($request->getRequestUri(), '/api/') ||
            $request->headers->get('Accept') === 'application/json' ||
            $request->headers->get('Content-Type') === 'application/json';
    }

    private function createApiErrorResponse(\Throwable $exception): JsonResponse
    {
        $statusCode = $this->getStatusCode($exception);
        $errorData = [
            'error' => true,
            'message' => $this->getErrorMessage($exception),
            'code' => $statusCode,
        ];

        // Add debug information in development
        if ($this->debug) {
            $errorData['debug'] = [
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

        return match (get_class($exception)) {
            NotFoundHttpException::class => Response::HTTP_NOT_FOUND,
            MethodNotAllowedHttpException::class => Response::HTTP_METHOD_NOT_ALLOWED,
            BadRequestHttpException::class => Response::HTTP_BAD_REQUEST,
            \InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
            default => Response::HTTP_INTERNAL_SERVER_ERROR,
        };
    }

    private function getErrorMessage(\Throwable $exception): string
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getMessage() ?: 'An error occurred';
        }

        return match (get_class($exception)) {
            NotFoundHttpException::class => 'Resource not found',
            MethodNotAllowedHttpException::class => 'Method not allowed',
            BadRequestHttpException::class => 'Bad request',
            \InvalidArgumentException::class => $exception->getMessage(),
            default => $this->debug ? $exception->getMessage() : 'Internal server error',
        };
    }
}
