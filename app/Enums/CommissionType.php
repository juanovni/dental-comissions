<?php

namespace App\Enums;

enum CommissionType: string
{
    case FixedPerProcedure = 'fixed_per_procedure';
    case PercentageOfInternalRate = 'percentage_of_internal_rate';
    case Mixed = 'mixed';
    case None = 'none';
}
