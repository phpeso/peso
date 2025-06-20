<?php

declare(strict_types=1);

namespace Peso\Peso;

use Arokettu\Date\Calendar;
use Arokettu\Date\Date;
use DateTimeInterface;
use Error;
use Peso\Core\Exceptions\PesoException;
use Peso\Core\Helpers\Calculator;
use Peso\Core\Helpers\CalculatorInterface;
use Peso\Core\Requests\CurrentExchangeRateRequest;
use Peso\Core\Requests\HistoricalExchangeRateRequest;
use Peso\Core\Responses\ErrorResponse;
use Peso\Core\Responses\SuccessResponse;
use Peso\Core\Services\ExchangeRateServiceInterface;
use Peso\Core\Types\Decimal;
use UnexpectedValueException;

final readonly class CurrencyConverter
{
    private CalculatorInterface $calculator;

    public function __construct(
        private ExchangeRateServiceInterface $service,
    ) {
        $this->calculator = Calculator::instance();
    }

    /**
     * @return numeric-string
     * @throws PesoException
     */
    public function getConversionRate(string $baseCurrency, string $quoteCurrency): string
    {
        return $this->doGetConversionRate($baseCurrency, $quoteCurrency)->value;
    }

    /**
     * @throws PesoException
     */
    private function doGetConversionRate(string $baseCurrency, string $quoteCurrency): Decimal
    {
        $result = $this->service->send(new CurrentExchangeRateRequest($baseCurrency, $quoteCurrency));

        if ($result instanceof SuccessResponse) {
            return $result->rate;
        }
        if ($result instanceof ErrorResponse) {
            throw $result->exception;
        }
        throw new Error('Invalid response');
    }

    /**
     * @return numeric-string
     * @throws PesoException
     */
    public function getHistoricalConversionRate(
        string $baseCurrency,
        string $quoteCurrency,
        string|DateTimeInterface|Date $date,
    ): string {
        return $this->doGetHistoricalConversionRate($baseCurrency, $quoteCurrency, $date)->value;
    }

    /**
     * @throws PesoException
     */
    public function doGetHistoricalConversionRate(
        string $baseCurrency,
        string $quoteCurrency,
        string|DateTimeInterface|Date $date,
    ): Decimal {
        if (is_string($date)) {
            try {
                $date = Calendar::parse($date);
            } catch (UnexpectedValueException) {
                $date = Calendar::parseDateTimeString($date);
            }
        }

        if ($date instanceof DateTimeInterface) {
            $date = Calendar::fromDateTime($date);
        }

        $result = $this->service->send(new HistoricalExchangeRateRequest($baseCurrency, $quoteCurrency, $date));

        if ($result instanceof SuccessResponse) {
            return $result->rate;
        }
        if ($result instanceof ErrorResponse) {
            throw $result->exception;
        }
        throw new Error('Invalid response');
    }

    /**
     * @param numeric-string $baseAmount
     * @return numeric-string
     * @throws PesoException
     */
    public function convert(string $baseAmount, string $baseCurrency, string $quoteCurrency, int $precision): string
    {
        $amount = new Decimal($baseAmount);
        $scale = $this->doGetConversionRate($baseCurrency, $quoteCurrency);

        return $this->calculator->round($this->calculator->multiply($amount, $scale), $precision)->value;
    }

    /**
     * @param numeric-string $baseAmount
     * @return numeric-string
     * @throws PesoException
     */
    public function convertOnDate(
        string $baseAmount,
        string $baseCurrency,
        string $quoteCurrency,
        int $precision,
        string|DateTimeInterface|Date $date,
    ): string {
        $amount = new Decimal($baseAmount);
        $scale = $this->doGetHistoricalConversionRate($baseCurrency, $quoteCurrency, $date);

        return $this->calculator->round($this->calculator->multiply($amount, $scale), $precision)->value;
    }
}
