<?php

namespace App\Service;

class FormatServiceFactory
{
    public static function strongFormatService(): FormatService
    {
        return (new FormatService())->setTag('strong');
    }

    public function citeFormatService(): FormatService
    {
        return (new FormatService())->setTag('cite');
    }

    public function headerFormatService(int $level): FormatService
    {
        return (new FormatService())->setTag("h$level");
    }
}
