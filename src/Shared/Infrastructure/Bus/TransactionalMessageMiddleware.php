<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use Symfony\Bridge\Doctrine\Messenger\DoctrineTransactionMiddleware;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final readonly class TransactionalMessageMiddleware implements MiddlewareInterface
{
    public function __construct(private ManagerRegistry $managerRegistry)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        // Each context owns one database, so a command runs in the transaction of its context's entity manager.
        $messageClass = $envelope->getMessage()::class;
        $manager = match (true) {
            str_starts_with($messageClass, 'App\\Order\\') => 'order',
            str_starts_with($messageClass, 'App\\Invoice\\') => 'invoice',
            default => throw new LogicException(\sprintf('No database context is configured for %s', $messageClass)),
        };

        return new DoctrineTransactionMiddleware($this->managerRegistry, $manager)->handle($envelope, $stack);
    }
}
