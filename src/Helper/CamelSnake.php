<?php
declare(strict_types=1);

namespace basteyy\StaticMarkdownPage\Helper;

function toSnakeCase(string $str, string $glue = '_'): string
{
    return preg_replace_callback('/[A-Z]/', fn($matches) => $glue . strtolower($matches[0]), lcfirst($str));
}