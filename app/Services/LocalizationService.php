<?php

namespace App\Services;

use App\Models\Language;
use Carbon\Carbon;
use NumberFormatter;

class LocalizationService
{
    public function formatCurrency($amount, $currencyCode = 'USD', $locale = 'en_US')
    {
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($amount, $currencyCode);
    }

    public function formatDate($date, $format = 'medium', $locale = 'en_US')
    {
        Carbon::setLocale(substr($locale, 0, 2));
        $carbonDate = Carbon::parse($date);
        
        return $carbonDate->isoFormat($format === 'long' ? 'LLLL' : ($format === 'short' ? 'L' : 'LLL'));
    }

    public function formatNumber($number, $locale = 'en_US', $decimals = 2)
    {
        $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimals);
        return $formatter->format($number);
    }
}
