<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Command;

use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Bus\Command\CommandBus;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

final class MessengerCommandBus implements CommandBus
{
    public function __construct(private MessageBusInterface $commandBus)
    {
    }

    public function dispatch(Command $command): void
    {
        try {
            $this->commandBus->dispatch($command);
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
