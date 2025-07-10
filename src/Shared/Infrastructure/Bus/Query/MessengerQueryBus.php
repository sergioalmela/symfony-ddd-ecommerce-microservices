<?php

namespace App\Shared\Infrastructure\Bus\Query;

use App\Shared\Domain\Bus\Query\Query;
use App\Shared\Domain\Bus\Query\QueryBus;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Throwable;

final readonly class MessengerQueryBus implements QueryBus
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    public function handle(Query $query): mixed
    {
        try {
            $envelope = $this->messageBus->dispatch($query);

            /** @var HandledStamp|null $handled */
            $handled = $envelope->last(HandledStamp::class);

            return $handled?->getResult();
        } catch (HandlerFailedException $e) {
            $this->unwrapHandlerException($e);
        }
    }

    private function unwrapHandlerException(HandlerFailedException $handlerFailedException): never
    {
        $originalException = $handlerFailedException;
        while ($originalException instanceof HandlerFailedException) {
            /** @var Throwable $originalException */
            $originalException = $originalException->getPrevious();
        }

        throw $originalException;
    }
}
