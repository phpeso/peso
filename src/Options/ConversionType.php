<?php

declare(strict_types=1);

namespace Peso\Peso\Options;

enum ConversionType
{
    case NativeOnly;
    case CalculatedOnly;
    case Both;
}
