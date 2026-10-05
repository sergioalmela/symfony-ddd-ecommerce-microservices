<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Invoice\Application\Command\SendInvoice\SendInvoiceCommand;
use App\Invoice\Application\Command\UploadInvoice\UploadInvoiceCommand;
use App\Order\Application\Command\CreateOrder\CreateOrderCommand;
use App\Order\Application\Command\UpdateOrderStatus\UpdateOrderStatusCommand;
use App\Shared\Domain\Event\DomainEvent;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Messenger\DoctrineTransactionMiddleware;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

final readonly class TransactionalMessageMiddleware implements MiddlewareInterface
{
    public function __construct(private ManagerRegistry $managerRegistry)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        $manager = match (true) {
            $message instanceof CreateOrderCommand, $message instanceof UpdateOrderStatusCommand => 'order',
            $message instanceof UploadInvoiceCommand, $message instanceof SendInvoiceCommand => 'invoice',
            $message instanceof DomainEvent && $envelope->last(ReceivedStamp::class) instanceof ReceivedStamp => 'invoice',
            default => null,
        };

        if (null === $manager) {
            return $stack->next()->handle($envelope, $stack);
        }

        return new DoctrineTransactionMiddleware($this->managerRegistry, $manager)->handle($envelope, $stack);
    }
}
