<?php

/**
 * @copyright 2025 Anton Smirnov
 * @license MIT https://spdx.org/licenses/MIT.html
 */

declare(strict_types=1);

namespace Peso\Peso\Options;

enum ConversionType
{
    case NativeOnly;
    case CalculatedOnly;
    case Fallback;

    /**
     * @deprecated
     */
    // phpcs:ignore Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
    public const Both = self::Fallback;
}
