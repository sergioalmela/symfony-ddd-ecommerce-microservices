<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Controller;

use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\Shared\Domain\Bus\Query\Query;
use App\Shared\Domain\Bus\Query\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseController
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly CommandBus $commandBus,
    ) {
    }

    protected function ask(Query $query): mixed
    {
        return $this->queryBus->handle($query);
    }

    protected function dispatch(Command $command): void
    {
        $this->commandBus->dispatch($command);
    }

    /**
     * @param array<string, string> $headers
     */
    protected function jsonResponse(mixed $data, int $status = Response::HTTP_OK, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }
}
