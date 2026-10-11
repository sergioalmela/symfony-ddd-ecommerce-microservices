<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Http;

use App\Shared\Infrastructure\Http\RequestData;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class RequestDataTest extends TestCase
{
    #[DataProvider('invalidBodies')]
    public function testRejectsInvalidBodies(string $body): void
    {
        $this->expectException(BadRequestHttpException::class);
        RequestData::json(Request::create('/', 'POST', content: $body));
    }

    public static function invalidBodies(): iterable
    {
        foreach (['', '{', '[]', 'null', 'true', '42', '"string"'] as $body) {
            yield [$body];
        }
    }

    #[DataProvider('invalidFields')]
    public function testRejectsIncorrectFieldTypes(string $method, mixed $value): void
    {
        $data = RequestData::json(Request::create('/', 'POST', content: json_encode(['field' => $value], \JSON_THROW_ON_ERROR)));
        $this->expectException(BadRequestHttpException::class);
        $data->$method('field');
    }

    public static function invalidFields(): iterable
    {
        foreach ([null, [], true, '', ' '] as $value) {
            yield ['string', $value];
        }
        foreach ([null, [], true, '1', 1.5] as $value) {
            yield ['integer', $value];
        }
        foreach ([null, [], true, '10.50'] as $value) {
            yield ['number', $value];
        }
    }

    public function testAcceptsValidValues(): void
    {
        $data = RequestData::json(Request::create('/', 'POST', content: '{"id":"value","quantity":2,"price":10.5}'));
        $this->assertSame('value', $data->string('id'));
        $this->assertSame(2, $data->integer('quantity'));
        $this->assertSame(10.5, $data->number('price'));
        $this->assertSame('value', RequestData::query(Request::create('/?id=value'))->string('id'));
        $this->assertSame('value', RequestData::form(Request::create('/', 'POST', ['id' => 'value']))->string('id'));
    }
}
