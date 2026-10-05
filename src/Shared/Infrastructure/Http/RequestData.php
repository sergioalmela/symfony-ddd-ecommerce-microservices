<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use JsonException;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class RequestData
{
    private function __construct(private array $values)
    {
    }

    public static function json(Request $request): self
    {
        try {
            $values = json_decode($request->getContent(), false, 512, \JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new BadRequestHttpException('Request body must contain valid JSON', $exception);
        }

        if (!$values instanceof stdClass) {
            throw new BadRequestHttpException('Request body must be a JSON object');
        }

        return new self((array) $values);
    }

    public static function query(Request $request): self
    {
        return new self($request->query->all());
    }

    public static function form(Request $request): self
    {
        return new self($request->request->all());
    }

    public function string(string $field): string
    {
        $value = $this->values[$field] ?? null;
        if (!\is_string($value) || '' === mb_trim($value)) {
            throw new BadRequestHttpException(\sprintf('%s must be a non-empty string', $field));
        }

        return $value;
    }

    public function integer(string $field): int
    {
        $value = $this->values[$field] ?? null;
        if (!\is_int($value)) {
            throw new BadRequestHttpException(\sprintf('%s must be an integer', $field));
        }

        return $value;
    }

    public function number(string $field): float
    {
        $value = $this->values[$field] ?? null;
        if ((!\is_int($value) && !\is_float($value)) || !is_finite((float) $value)) {
            throw new BadRequestHttpException(\sprintf('%s must be a finite number', $field));
        }

        return (float) $value;
    }
}
