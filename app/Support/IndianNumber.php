<?php

namespace App\Support;

/**
 * Indian number formatting: "Rupees Twenty-Six Thousand Two Hundred Only"
 * for net pay in words.
 */
final class IndianNumber
{
    private const ONES = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve',
        'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];

    private const TENS = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    public static function rupeesInWords(float $amount): string
    {
        $rupees = (int) floor(abs($amount));
        $paise = (int) round((abs($amount) - $rupees) * 100);

        $words = 'Rupees '.($rupees ? self::words($rupees) : 'Zero');
        if ($paise) {
            $words .= ' and '.self::words($paise).' Paise';
        }

        return ($amount < 0 ? 'Minus ' : '').$words.' Only';
    }

    private static function words(int $n): string
    {
        $parts = [];
        foreach ([10000000 => 'Crore', 100000 => 'Lakh', 1000 => 'Thousand', 100 => 'Hundred'] as $size => $name) {
            if ($n >= $size) {
                $parts[] = self::words(intdiv($n, $size)).' '.$name;
                $n %= $size;
            }
        }
        if ($n >= 20) {
            $parts[] = self::TENS[intdiv($n, 10)].($n % 10 ? '-'.self::ONES[$n % 10] : '');
        } elseif ($n > 0) {
            $parts[] = self::ONES[$n];
        }

        return implode(' ', $parts);
    }
}
