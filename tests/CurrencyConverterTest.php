<?php

declare(strict_types=1);

namespace Peso\Peso\Tests;

use Arokettu\Date\Calendar;
use DateTime;
use Peso\Core\Exceptions\ConversionNotPerformedException;
use Peso\Core\Exceptions\ExchangeRateNotFoundException;
use Peso\Core\Services\ArrayService;
use Peso\Core\Types\Decimal;
use Peso\Peso\CurrencyConverter;
use Peso\Peso\Options\ConversionType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CurrencyConverterTest extends TestCase
{
    public function testConversionRate(): void
    {
        $service = new ArrayService(currentRates: [
            'EUR' => ['PHP' => '65.9745'],
        ], historicalRates: [
            '2025-06-13' => [
                'EUR' => ['PHP' => '66.1844'],
            ],
        ]);
        $converter = new CurrencyConverter($service);

        self::assertEquals('65.9745', $converter->getExchangeRate('EUR', 'PHP'));
        self::assertEquals('66.1844', $converter->getHistoricalExchangeRate('EUR', 'PHP', '2025-06-13'));
    }

    public function testConversion(): void
    {
        $service = new ArrayService(currentRates: [
            'EUR' => ['PHP' => '65.9745'],
        ], historicalRates: [
            '2025-06-13' => [
                'EUR' => ['PHP' => '66.1844'],
            ],
        ]);
        $converter = new CurrencyConverter($service);

        self::assertEquals('98961.75', $converter->convert('1500', 'EUR', 'PHP', 2));
        self::assertEquals('99276.60', $converter->convertOnDate('1500', 'EUR', 'PHP', 2, '2025-06-13'));
    }

    public function testAcceptsFloat(): void
    {
        $service = new ArrayService(currentRates: [
            'EUR' => ['PHP' => '65.9745'],
        ], historicalRates: [
            '2025-06-13' => [
                'EUR' => ['PHP' => '66.1844'],
            ],
        ]);
        $converter = new CurrencyConverter($service);

        self::assertEquals('98961.75', $converter->convert(1500.0, 'EUR', 'PHP', 2));
        self::assertEquals('99276.60', $converter->convertOnDate(1500.0, 'EUR', 'PHP', 2, '2025-06-13'));
    }

    public function testAcceptsDecimal(): void
    {
        $service = new ArrayService(currentRates: [
            'EUR' => ['PHP' => '65.9745'],
        ], historicalRates: [
            '2025-06-13' => [
                'EUR' => ['PHP' => '66.1844'],
            ],
        ]);
        $converter = new CurrencyConverter($service);

        self::assertEquals('98961.75', $converter->convert(new Decimal('1500.0'), 'EUR', 'PHP', 2));
        self::assertEquals('99276.60', $converter->convertOnDate(new Decimal('1500.0'), 'EUR', 'PHP', 2, '2025-06-13'));
    }

    public function testRateNotFound(): void
    {
        $service = new ArrayService(currentRates: [
            'EUR' => ['PHP' => '65.9745'],
        ], historicalRates: [
            '2025-06-13' => [
                'EUR' => ['PHP' => '66.1844'],
            ],
        ]);
        $converter = new CurrencyConverter($service);

        self::expectException(ExchangeRateNotFoundException::class);
        self::expectExceptionMessage('Unable to find exchange rate for PHP/EUR');

        $converter->getExchangeRate('PHP', 'EUR');
    }

    public function testRateNotFoundConv(): void
    {
        $service = new ArrayService(currentRates: [
            'EUR' => ['PHP' => '65.9745'],
        ], historicalRates: [
            '2025-06-13' => [
                'EUR' => ['PHP' => '66.1844'],
            ],
        ]);
        $converter = new CurrencyConverter($service, ConversionType::CalculatedOnly);

        self::expectException(ConversionNotPerformedException::class);
        self::expectExceptionMessage('Unable to convert 1 PHP to EUR');

        $converter->convert('1', 'PHP', 'EUR', 2);
    }

    public function testHistoricalRateNotFound(): void
    {
        $service = new ArrayService(currentRates: [
            'EUR' => ['PHP' => '65.9745'],
        ], historicalRates: [
            '2025-06-13' => [
                'EUR' => ['PHP' => '66.1844'],
            ],
        ]);
        $converter = new CurrencyConverter($service);

        self::expectException(ExchangeRateNotFoundException::class);
        self::expectExceptionMessage('Unable to find exchange rate for EUR/PHP on 2025-06-14');

        $converter->getHistoricalExchangeRate('EUR', 'PHP', '2025-06-14');
    }

    public function testHistoricalRateNotFoundConv(): void
    {
        $service = new ArrayService(currentRates: [
            'EUR' => ['PHP' => '65.9745'],
        ], historicalRates: [
            '2025-06-13' => [
                'EUR' => ['PHP' => '66.1844'],
            ],
        ]);
        $converter = new CurrencyConverter($service, ConversionType::CalculatedOnly);

        self::expectException(ConversionNotPerformedException::class);
        self::expectExceptionMessage('Unable to convert 1 EUR to PHP on 2025-06-14');

        $converter->convertOnDate('1', 'EUR', 'PHP', 2, '2025-06-14');
    }

    public static function validDates(): array
    {
        // All dates are June 13, 2025

        return [
            [Calendar::parse('2025-06-13')],
            ['2025-06-13'],
            [new DateTime('2025-06-13')],
        ];
    }

    #[DataProvider('validDates')]
    public function testAcceptsDate(mixed $date): void
    {
        $service = new ArrayService(currentRates: [
            'EUR' => ['PHP' => '65.9745'],
        ], historicalRates: [
            '2025-06-13' => [
                'EUR' => ['PHP' => '66.1844'],
            ],
        ]);
        $converter = new CurrencyConverter($service);

        self::assertEquals('66.1844', $converter->getHistoricalExchangeRate('EUR', 'PHP', $date));
    }

    public function testRoundHalfEven(): void
    {
        $service = new ArrayService(currentRates: ['EUR' => [
            'PHP' => '65.666500',
            'USD' => '1.555500',
        ]]);
        $converter = new CurrencyConverter($service);

        self::assertEquals('656.66', $converter->convert('10', 'EUR', 'PHP', 2));
        self::assertEquals('15.56', $converter->convert('10', 'EUR', 'USD', 2));
    }
}
