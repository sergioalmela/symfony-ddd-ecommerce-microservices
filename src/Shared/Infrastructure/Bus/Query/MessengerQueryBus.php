<?php

namespace App\Shared\Infrastructure\Bus\Query;

use Throwable;
use App\Shared\Domain\Bus\Query\Query;
use App\Shared\Domain\Bus\Query\QueryBus;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class MessengerQueryBus implements QueryBus
{
    public function __construct(private MessageBusInterface $queryBus)
    {
    }

    public function handle(Query $query): mixed
    {
        try {
            $envelope = $this->queryBus->dispatch($query);

            /** @var HandledStamp|null $handled */
            $handled = $envelope->last(HandledStamp::class);

            return $handled?->getResult();
        } catch (HandlerFailedException $e) {
            $this->unwrapHandlerException($e);
        }
    }

    private function unwrapHandlerException(HandlerFailedException $e): never
    {
        $originalException = $e;
        while ($originalException instanceof HandlerFailedException) {
            /** @var Throwable $originalException */
            $originalException = $originalException->getPrevious();
        }

        throw $originalException;
    }
}
