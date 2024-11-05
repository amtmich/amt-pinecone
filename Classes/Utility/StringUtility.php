<?php

declare(strict_types=1);

namespace Amt\AmtPinecone\Utility;


class StringUtility
{
    public static function concatString(string $text, string $text2, string $separator = '-'): string
    {
        return $text . $separator . $text2;
    }
}
