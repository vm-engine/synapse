<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Enums;

enum ExcelTaskStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
