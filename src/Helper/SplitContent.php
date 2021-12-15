<?php
declare(strict_types=1);

namespace basteyy\StaticMarkdownPage\Helper;

use JetBrains\PhpStorm\Pure;

class SplitContent
{
    /**
     * @var string Pattern of splitting file
     */
    private static string $pattern = '/\s+={3,}\s+/';

    /**
     * Get the Metadata from $context
     * @param string $context
     * @return string
     */
    #[Pure] static public function getMeta(string $context): string
    {
        return self::get($context)[0];
    }

    /**
     * Get Metadata and Body as an array
     * @param string $context
     * @return array
     */
    static public function get(string $context): array
    {
        return preg_split(self::$pattern, $context);
    }

    /**
     * Get the body from $context
     * @param string $context
     * @return string
     */
    #[Pure] static public function getBody(string $context): string
    {
        return self::get($context)[1];
    }
}