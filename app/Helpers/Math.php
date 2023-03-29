<?php

namespace App\Helpers;


class Math
{
    /**
     * Determine Whether two floating point numbers are equal
     * @param float $float1
     * @param float $float2
     * @param float $floatEpsilon
     * @return bool
     */
    public static function isEqualFloats($float1, $float2, float $floatEpsilon=0.0000000001):bool
    {
        return abs($float1-$float2) < $floatEpsilon;
    }
}
