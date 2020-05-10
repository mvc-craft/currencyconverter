<?php

namespace App\Http\Controllers;

use App\ExchangeRate;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExchangeRateController extends Controller
{
    const SUPPORTED_CURRENCIES = [
        "CAD",
        "JPY",
        "USD",
        "GBP",
        "EUR",
        "RUB",
        "HKD",
        "CHF"
    ];

    /**
     * @return JsonResponse
     */
    public function info(): JsonResponse
    {
        return response()->json([
            'error' => 0,
            'msg' => 'API written by Ajay Surti'
        ], JsonResponse::HTTP_OK);
    }

    /**
     * @param $amount
     * @param string $fromCurrency
     * @param string $toCurrency
     * @return JsonResponse
     */
    public function exchangeRate($amount,string $fromCurrency,string $toCurrency)
    {
        if ($this->isValidCurrencies($fromCurrency,$toCurrency) !== true) {
            return response()->json([
                'error' => 1,
                'msg' => $this->isValidCurrencies($fromCurrency,$toCurrency)
            ],JsonResponse::HTTP_NOT_ACCEPTABLE);
        }

        $exchangeRate = $this->getExchangeRate($fromCurrency, $toCurrency);

        return response()->json([
            'error' => 0,
            'amount' => $this->cleanCurrencyValue(
                $this->cleanCurrencyValue($exchangeRate['multiplier']) * $amount
                ),
            'fromCache' => $exchangeRate['fromCache']
        ],JsonResponse::HTTP_OK);
    }

    /**
     * @param $fromCurrency
     * @param $toCurrency
     * @return mixed
     */
    protected function getExchangeRate($fromCurrency,$toCurrency)
    {
        $ratesFromDb = $this->getExchangeRateFromDB($fromCurrency,$toCurrency);
        if ($ratesFromDb) {
            $exchangeRate['multiplier'] = $ratesFromDb;
            $exchangeRate['fromCache'] = 1;
        } else {
            $ratesFromSrc = $this->getExchangeRateFromSrc($fromCurrency, $toCurrency);

            $this->storeExchangeRate($fromCurrency, $toCurrency, $ratesFromSrc);
            $exchangeRate['multiplier'] = $ratesFromSrc;
            $exchangeRate['fromCache'] = 0;
        }

        return $exchangeRate;
    }

    /**
     * @param $fromCurrency
     * @param $toCurrency
     */
    protected function checkForSameCurrency($fromCurrency,$toCurrency)
    {
        $fromCurrency === $toCurrency ? true : false;
    }

    /**
     * @param mixed ...$currencyNames
     * @return bool|string
     */
    protected function isValidCurrencies(...$currencyNames)
    {
        $notSupportedCurrency = [];
        foreach ($currencyNames as $currencyName) {
            if (!in_array($currencyName, self::SUPPORTED_CURRENCIES)) {
                $notSupportedCurrency[] = $currencyName;
            }
        }

        if(empty($notSupportedCurrency)) {
            return true;
        } else {
            return "currency code ".implode(",",$notSupportedCurrency)." not supported";
        }
    }

    /**
     * @param string $fromCurrency
     * @param string $toCurrency
     * @param $baseRate
     * @return bool
     */
    protected function storeExchangeRate(string $fromCurrency, string $toCurrency, $baseRate): bool
    {
        $exchangeRate = new ExchangeRate();
        $exchangeRate->from_currency = $fromCurrency;
        $exchangeRate->to_currency = $toCurrency;
        $exchangeRate->base_rate = $baseRate;
        $exchangeRate->expiry_at = Carbon::now()->addHours(config('currencyconverter.cacheLife'));
        return $exchangeRate->save();
    }

    /**
     * @param string $fromCurrency
     * @param string $toCurrency
     * @return int
     */
    protected function getExchangeRateFromDB(string $fromCurrency, string $toCurrency)
    {
        $this->flushExpiredExchangeRate();
        $exchangeRate = ExchangeRate::select(
                DB::raw("*, (CASE
                WHEN from_currency = '{$fromCurrency}' and to_currency = '{$toCurrency}'
                    THEN base_rate
                ELSE (1/base_rate) END) as rate")
            )
            ->whereIn('from_currency', [$fromCurrency,$toCurrency])
            ->whereIn('to_currency', [$fromCurrency,$toCurrency])
            ->first();

        return $exchangeRate ? $this->cleanCurrencyValue($exchangeRate->rate): 0;
    }

    /**
     * @param string $fromCurrency
     * @param string $toCurrency
     * @return string
     */
    protected function getExchangeRateFromSrc(string $fromCurrency,string $toCurrency)
    {
        $client = new Client();
        $clientResponse = $client->get(config('currencyconverter.src_url')."?base=$fromCurrency");
        $response = json_decode($clientResponse->getBody(), false);
        return $response->rates->$toCurrency;
        //return $this->cleanCurrencyValue($response->rates->$toCurrency);
    }

    /**
     * Flush the expired data from the cache table.
     */
    protected function flushExpiredExchangeRate()
    {
        ExchangeRate::where('expiry_at', '<=', Carbon::now())->delete();
    }

    /**
     * @param $multiplier
     * @return string
     */
    protected function cleanCurrencyValue($multiplier)
    {
        return number_format($multiplier,2);
    }
}
