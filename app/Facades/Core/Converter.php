<?php


namespace App\Facades\Core;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string convertImageToPng(string $filePath)
 */
class Converter extends Facade {
    protected static function getFacadeAccessor() {
        return "arena-converter";
    }
}
