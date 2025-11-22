<?php

namespace App\Utils;

class CurrencyFormatter
{
    private static $currencies = [
        'SLE' => ['symbol' => 'Le', 'name' => 'Sierra Leonean Leone', 'decimals' => 2],
        'NGN' => ['symbol' => '₦', 'name' => 'Nigerian Naira', 'decimals' => 2],
        'USD' => ['symbol' => '$', 'name' => 'US Dollar', 'decimals' => 2],
        'GBP' => ['symbol' => '£', 'name' => 'British Pound', 'decimals' => 2],
        'EUR' => ['symbol' => '€', 'name' => 'Euro', 'decimals' => 2],
    ];

    public static function format($amount, $currencyCode = 'SLE')
    {
        $currency = self::$currencies[$currencyCode] ?? self::$currencies['SLE'];
        $formatted = number_format((float)$amount, $currency['decimals'], '.', ',');
        return $currency['symbol'] . $formatted;
    }

    public static function formatWithCode($amount, $currencyCode = 'SLE')
    {
        $currency = self::$currencies[$currencyCode] ?? self::$currencies['SLE'];
        $formatted = number_format((float)$amount, $currency['decimals'], '.', ',');
        return $currency['symbol'] . $formatted . ' ' . $currencyCode;
    }

    public static function getCurrencySymbol($currencyCode = 'SLE')
    {
        return self::$currencies[$currencyCode]['symbol'] ?? 'Le';
    }

    public static function getCurrencyName($currencyCode = 'SLE')
    {
        return self::$currencies[$currencyCode]['name'] ?? 'Sierra Leonean Leone';
    }

    public static function getSupportedCurrencies()
    {
        return self::$currencies;
    }
}
