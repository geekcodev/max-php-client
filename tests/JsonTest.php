<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests;

use GeekCo\MaxPhpClient\Enum\UpdateType;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use GeekCo\MaxPhpClient\Internal\Json;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class JsonTest extends TestCase
{
    #[Test]
    public function it_reads_required_int(): void
    {
        $this->assertSame(5, Json::requiredInt(['n' => 5], 'n'));
    }

    #[Test]
    #[DataProvider('missingIntProvider')]
    public function it_rejects_a_missing_or_wrong_int(array $data): void
    {
        $this->expectException(InvalidResponseException::class);

        Json::requiredInt($data, 'n');
    }

    public static function missingIntProvider(): array
    {
        return [
            'missing' => [[]],
            'string' => [['n' => '5']],
        ];
    }

    #[Test]
    public function it_reads_optional_int(): void
    {
        $this->assertNull(Json::int([], 'n'));
        $this->assertSame(5, Json::int(['n' => 5], 'n'));
    }

    #[Test]
    public function it_rejects_a_wrong_optional_int(): void
    {
        $this->expectException(InvalidResponseException::class);

        Json::int(['n' => '5'], 'n');
    }

    #[Test]
    public function it_reads_required_string(): void
    {
        $this->assertSame('hi', Json::requiredString(['s' => 'hi'], 's'));
    }

    #[Test]
    #[DataProvider('missingStringProvider')]
    public function it_rejects_a_missing_or_wrong_string(array $data): void
    {
        $this->expectException(InvalidResponseException::class);

        Json::requiredString($data, 's');
    }

    public static function missingStringProvider(): array
    {
        return [
            'missing' => [[]],
            'int' => [['s' => 5]],
        ];
    }

    #[Test]
    public function it_reads_optional_string(): void
    {
        $this->assertNull(Json::string([], 's'));
        $this->assertSame('hi', Json::string(['s' => 'hi'], 's'));
    }

    #[Test]
    public function it_rejects_a_wrong_optional_string(): void
    {
        $this->expectException(InvalidResponseException::class);

        Json::string(['s' => true], 's');
    }

    #[Test]
    public function it_reads_required_bool(): void
    {
        $this->assertTrue(Json::requiredBool(['b' => true], 'b'));
    }

    #[Test]
    #[DataProvider('missingBoolProvider')]
    public function it_rejects_a_missing_or_wrong_bool(array $data): void
    {
        $this->expectException(InvalidResponseException::class);

        Json::requiredBool($data, 'b');
    }

    public static function missingBoolProvider(): array
    {
        return [
            'missing' => [[]],
            'int' => [['b' => 1]],
        ];
    }

    #[Test]
    public function it_reads_optional_bool(): void
    {
        $this->assertNull(Json::bool([], 'b'));
        $this->assertFalse(Json::bool(['b' => false], 'b'));
    }

    #[Test]
    public function it_rejects_a_wrong_optional_bool(): void
    {
        $this->expectException(InvalidResponseException::class);

        Json::bool(['b' => 'yes'], 'b');
    }

    #[Test]
    public function it_reads_float_from_int_and_float(): void
    {
        $this->assertNull(Json::float([], 'f'));
        $this->assertSame(1.0, Json::float(['f' => 1], 'f'));
        $this->assertSame(1.5, Json::float(['f' => 1.5], 'f'));
    }

    #[Test]
    public function it_rejects_a_wrong_float(): void
    {
        $this->expectException(InvalidResponseException::class);

        Json::float(['f' => '1.5'], 'f');
    }

    #[Test]
    public function it_reads_required_array(): void
    {
        $this->assertSame([1, 2], Json::requiredArray(['a' => [1, 2]], 'a'));
    }

    #[Test]
    #[DataProvider('missingArrayProvider')]
    public function it_rejects_a_missing_or_wrong_array(array $data): void
    {
        $this->expectException(InvalidResponseException::class);

        Json::requiredArray($data, 'a');
    }

    public static function missingArrayProvider(): array
    {
        return [
            'missing' => [[]],
            'string' => [['a' => 'x']],
        ];
    }

    #[Test]
    public function it_reads_optional_array(): void
    {
        $this->assertNull(Json::array_([], 'a'));
        $this->assertSame([1], Json::array_(['a' => [1]], 'a'));
    }

    #[Test]
    public function it_rejects_a_wrong_optional_array(): void
    {
        $this->expectException(InvalidResponseException::class);

        Json::array_(['a' => 5], 'a');
    }

    #[Test]
    public function it_reads_an_array_of_strings(): void
    {
        $this->assertNull(Json::arrayOfStrings([], 'a'));
        $this->assertSame(['x'], Json::arrayOfStrings(['a' => ['x']], 'a'));
    }

    #[Test]
    public function it_rejects_an_array_containing_a_non_string(): void
    {
        $this->expectException(InvalidResponseException::class);

        Json::arrayOfStrings(['a' => ['x', 1]], 'a');
    }

    #[Test]
    public function it_reads_an_array_of_ints(): void
    {
        $this->assertNull(Json::arrayOfInts([], 'a'));
        $this->assertSame([1, 2], Json::arrayOfInts(['a' => [1, 2]], 'a'));
    }

    #[Test]
    public function it_rejects_an_array_containing_a_non_int(): void
    {
        $this->expectException(InvalidResponseException::class);

        Json::arrayOfInts(['a' => [1, 'x']], 'a');
    }

    #[Test]
    public function it_reads_an_enum(): void
    {
        $this->assertNull(Json::enum(UpdateType::class, [], 'e'));
        $this->assertSame(UpdateType::MessageCreated, Json::enum(UpdateType::class, ['e' => 'message_created'], 'e'));
    }

    #[Test]
    #[DataProvider('badEnumProvider')]
    public function it_rejects_a_bad_enum(array $data): void
    {
        $this->expectException(InvalidResponseException::class);

        Json::enum(UpdateType::class, $data, 'e');
    }

    public static function badEnumProvider(): array
    {
        return [
            'wrong type' => [['e' => 5]],
            'unsupported value' => [['e' => 'unknown']],
        ];
    }

    #[Test]
    public function it_maps_an_optional_list(): void
    {
        $mapper = static fn (mixed $item): string => (string) $item;

        $this->assertNull(Json::map([], 'a', $mapper));
        $this->assertSame(['1', '2'], Json::map(['a' => [1, 2]], 'a', $mapper));
    }
}
