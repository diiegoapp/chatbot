<?php

namespace App\Support;

use League\CommonMark\CommonMarkConverter;

class Markdown
{
    private static ?CommonMarkConverter $converter = null;

    public static function toHtml(string $text): string
    {
        if (self::$converter === null) {
            self::$converter = new CommonMarkConverter([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
                'max_nesting_level' => 20,
            ]);
        }

        return self::$converter->convert($text)->getContent();
    }
}
