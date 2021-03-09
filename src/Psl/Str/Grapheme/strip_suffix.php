<?php

declare(strict_types=1);

namespace Psl\Str\Grapheme;

/**
 * Returns the string with the given suffix removed, or the string itself if
 * it doesn't end with the suffix.
 *
 * @pure
 */
function strip_suffix(string $string, string $suffix): string
{
    if ('' === $suffix || !ends_with($string, $suffix)) {
        return $string;
    }

    /** @psalm-suppress MissingThrowsDocblock */
    return slice($string, 0, length($string) - length($suffix));
}
