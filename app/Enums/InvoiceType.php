<?php

namespace App\Enums;

enum InvoiceType: string
{
    case MonthlyCharge = 'monthly_charge';
    case PlannedExpense = 'planned_expense';
    case UnexpectedExpense = 'unexpected_expense';

    /**
     * Get the label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::MonthlyCharge => 'شارژ ماهانه',
            self::PlannedExpense => 'هزینه برنامه‌ریزی شده',
            self::UnexpectedExpense => 'هزینه غیرمنتظره',
        };
    }
}
