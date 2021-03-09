<?php

declare(strict_types=1);

namespace Psl\Internal;

use function error_reporting;

/**
 * @template T
 *
 * @param (pure-callable(): T) $fun
 *
 * @return T
 *
 * @pure
 *
 * @internal
 */
function suppress(callable $fun)
{
    /** @psalm-suppress ImpureFunctionCall */
    $previous_level = error_reporting(0);

    try {
        return $fun();
    } finally {
        /** @psalm-suppress ImpureFunctionCall */
        error_reporting($previous_level);
    }
}
