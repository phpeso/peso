<?php

declare(strict_types=1);

namespace Peso\Peso;

use Arokettu\Date\Calendar;
use Arokettu\Date\Date;
use DateTimeInterface;
use Peso\Core\Exceptions\PesoException;
use Peso\Core\Helpers\Calculator;
use Peso\Core\Helpers\CalculatorInterface;
use Peso\Core\Requests\CurrentConversionRequest;
use Peso\Core\Requests\CurrentExchangeRateRequest;
use Peso\Core\Requests\HistoricalConversionRequest;
use Peso\Core\Requests\HistoricalExchangeRateRequest;
use Peso\Core\Responses\ConversionResponse;
use Peso\Core\Responses\ExchangeRateResponse;
use Peso\Core\Services\ChainService;
use Peso\Core\Services\ConversionService;
use Peso\Core\Services\PesoServiceInterface;
use Peso\Core\Types\Decimal;
use Peso\Peso\Options\ConversionType;

final readonly class CurrencyConverter
{
    private PesoServiceInterface $rateService;
    private PesoServiceInterface $conversionService;
    private CalculatorInterface $calculator;

    public function __construct(
        PesoServiceInterface $service,
        ConversionType $conversionType = ConversionType::Fallback,
    ) {
        $this->rateService = $service;
        $this->conversionService = match ($conversionType) {
            ConversionType::NativeOnly => $service,
            ConversionType::CalculatedOnly => new ConversionService($service),
            ConversionType::Fallback => new ChainService($service, new ConversionService($service)),
        };
        $this->calculator = Calculator::instance();
    }

    /**
     * @return numeric-string
     * @throws PesoException
     */
    public function getExchangeRate(string $baseCurrency, string $quoteCurrency): string
    {
        return $this->doGetExchangeRate(new CurrentExchangeRateRequest($baseCurrency, $quoteCurrency));
    }

    /**
     * @return numeric-string
     * @throws PesoException
     * @deprecated getExchangeRate()
     */
    public function getConversionRate(string $baseCurrency, string $quoteCurrency): string
    {
        return $this->getExchangeRate($baseCurrency, $quoteCurrency);
    }

    /**
     * @return numeric-string
     * @throws PesoException
     */
    public function getHistoricalExchangeRate(
        string $baseCurrency,
        string $quoteCurrency,
        string|DateTimeInterface|Date $date,
    ): string {
        $date = $this->normalizeDate($date);
        return $this->doGetExchangeRate(new HistoricalExchangeRateRequest($baseCurrency, $quoteCurrency, $date));
    }

    /**
     * @return numeric-string
     * @throws PesoException
     * @deprecated getHistoricalExchangeRate()
     */
    public function getHistoricalConversionRate(
        string $baseCurrency,
        string $quoteCurrency,
        string|DateTimeInterface|Date $date,
    ): string {
        return $this->getHistoricalExchangeRate($baseCurrency, $quoteCurrency, $date);
    }

    /**
     * @return numeric-string
     * @throws PesoException
     */
    private function doGetExchangeRate(CurrentExchangeRateRequest|HistoricalExchangeRateRequest $request): string
    {
        // handle a trivial conversion
        if ($request->baseCurrency === $request->quoteCurrency) {
            return '1';
        }

        $result = $this->rateService->send($request);

        if ($result instanceof ExchangeRateResponse) {
            return $result->rate->value;
        }

        throw $result->exception;
    }

    private function normalizeDate(string|DateTimeInterface|Date $date): Date
    {
        if (\is_string($date)) {
            return Calendar::parse($date);
        }
        if ($date instanceof DateTimeInterface) {
            return Calendar::fromDateTime($date);
        }
        return $date;
    }

    /**
     * @param numeric-string|float|Decimal $baseAmount
     * @return numeric-string
     * @throws PesoException
     */
    public function convert(
        string|float|Decimal $baseAmount,
        string $baseCurrency,
        string $quoteCurrency,
        int $precision,
    ): string {
        $amount = Decimal::init($baseAmount);

        return $this->doConvert(
            new CurrentConversionRequest($amount, $baseCurrency, $quoteCurrency),
            $precision,
        );
    }

    /**
     * @param numeric-string|float|Decimal $baseAmount
     * @return numeric-string
     * @throws PesoException
     */
    public function convertOnDate(
        string|float|Decimal $baseAmount,
        string $baseCurrency,
        string $quoteCurrency,
        int $precision,
        string|DateTimeInterface|Date $date,
    ): string {
        $amount = Decimal::init($baseAmount);
        $date = $this->normalizeDate($date);

        return $this->doConvert(
            new HistoricalConversionRequest($amount, $baseCurrency, $quoteCurrency, $date),
            $precision,
        );
    }

    /**
     * @return numeric-string
     * @throws PesoException
     */
    private function doConvert(CurrentConversionRequest|HistoricalConversionRequest $request, int $precision): string
    {
        // handle a trivial conversion
        if ($request->baseCurrency === $request->quoteCurrency) {
            $response = new ConversionResponse($request->baseAmount, Date::today());
        } else {
            $response = $this->conversionService->send($request);
        }

        if ($response instanceof ConversionResponse) {
            return $this->calculator->round($response->amount, $precision)->value;
        }

        throw $response->exception;
    }
}
