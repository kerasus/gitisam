<?php

namespace App\Services;

use App\Models\Unit;
use Illuminate\Support\Collection;

class InvoiceDistributionCalculatorService
{
    /**
     * Calculate distribution amounts and descriptions based on the selected method.
     *
     * @param string $distributionMethod
     * @param array $unitIds
     * @param int $totalInvoiceAmount
     * @return Collection
     * @throws \InvalidArgumentException
     */
    public function calculate(string $distributionMethod, array $unitIds, int $totalInvoiceAmount): Collection
    {
        $units = Unit::whereIn('id', $unitIds)->get();
        $results = collect();

        switch ($distributionMethod) {
            case 'equal':
                $amounts = $this->calculateEqualDistribution($units, $totalInvoiceAmount);
                $description = $this->generateEqualDescription($units, $totalInvoiceAmount);
                break;

            case 'per_person':
                $amounts = $this->calculatePerPersonDistribution($units, $totalInvoiceAmount);
                $description = $this->generatePerPersonDescription($units, $totalInvoiceAmount);
                break;

            case 'area':
                $amounts = $this->calculateAreaDistribution($units, $totalInvoiceAmount);
                $description = $this->generateAreaDescription($units, $totalInvoiceAmount);
                break;

            case 'parking':
                $amounts = $this->calculateParkingDistribution($units, $totalInvoiceAmount);
                $description = $this->generateParkingDescription($units, $totalInvoiceAmount);
                break;

            default:
                throw new \InvalidArgumentException("روش توزیع نامعتبر است.");
        }

        foreach ($units as $unit) {
            $results->push([
                'unit_id' => $unit->id,
                'amount' => $amounts[$unit->id] ?? 0,
                'description' => $description,
            ]);
        }

        return $results;
    }

    /**
     * Calculate equal distribution amounts.
     */
    private function calculateEqualDistribution(Collection $units, int $totalInvoiceAmount): array
    {
        $count = $units->count();
        $amountPerUnit = $count > 0 ? ceil($totalInvoiceAmount / $count) : 0;
        return $units->pluck('id')->mapWithKeys(fn ($id) => [$id => $amountPerUnit])->toArray();
    }

    /**
     * Generate description for equal distribution.
     */
    private function generateEqualDescription(Collection $units, int $totalInvoiceAmount): string
    {
        $count = $units->count();
        $amountPerUnit = $count > 0 ? ceil($totalInvoiceAmount / $count) : 0;
        return "فاکتور به صورت برابر بین تمامی واحدها تقسیم شده است. تعداد کل واحدها: {$count}.
                <br />
                مبلغ هر واحد از طریق فرمول زیر محاسبه شده است:
                <br />
                `مبلغ هر واحد = کل مبلغ فاکتور ÷ تعداد کل واحدها`
                <br />
                مثال: اگر کل مبلغ فاکتور {$totalInvoiceAmount} ریال و تعداد واحدها {$count} باشد، مبلغ هر واحد برابر است با:
                <br />
                `{$totalInvoiceAmount} ÷ {$count} = {$amountPerUnit} ریال`.";
    }

    /**
     * Calculate per_person distribution amounts.
     */
    private function calculatePerPersonDistribution(Collection $units, int $totalInvoiceAmount): array
    {
        $totalOccupants = $units->sum('number_of_residents');
        if ($totalOccupants == 0) {
            throw new \InvalidArgumentException("امکان توزیع مبلغ فاکتور با روش 'به ازای هر نفر' وجود ندارد، زیرا تعداد ساکنان کل صفر است.");
        }

        return $units->mapWithKeys(function ($unit) use ($totalInvoiceAmount, $totalOccupants) {
            $amount = ceil(($unit->number_of_residents / $totalOccupants) * $totalInvoiceAmount);
            return [$unit->id => $amount];
        })->toArray();
    }

    /**
     * Generate description for per_person distribution.
     */
    private function generatePerPersonDescription(Collection $units, int $totalInvoiceAmount): string
    {
        $totalOccupants = $units->sum('number_of_residents');

        $description = "فاکتور بر اساس تعداد ساکنان هر واحد توزیع شده است. تعداد کل ساکنان: {$totalOccupants}.
                    <br />
            مبلغ هر واحد از طریق فرمول زیر محاسبه شده است:
                    <br />
            `مبلغ هر واحد = (تعداد ساکنان واحد ÷ تعداد کل ساکنان) × کل مبلغ فاکتور`
                    <br />
            لیست واحدها و مبالغ آنها: ";

        foreach ($units as $unit) {
            $calculatedAmount = ceil(($unit->number_of_residents / $totalOccupants) * $totalInvoiceAmount);
            $description .= "
                    <br />
                - واحد {$unit->id}: تعداد ساکنان = {$unit->number_of_residents} →
                `{({$unit->number_of_residents} ÷ {$totalOccupants}) × {$totalInvoiceAmount}} = {$calculatedAmount} ریال`";
        }

        return $description;
    }

    /**
     * Calculate area-based distribution amounts.
     */
    private function calculateAreaDistribution(Collection $units, int $totalInvoiceAmount): array
    {
        $totalArea = $units->sum('area');
        if ($totalArea == 0) {
            throw new \InvalidArgumentException("امکان توزیع مبلغ فاکتور با روش 'بر اساس متراژ' وجود ندارد، زیرا مجموع متراژ واحدها صفر است.");
        }

        return $units->mapWithKeys(function ($unit) use ($totalInvoiceAmount, $totalArea) {
            $amount = ceil(($unit->area / $totalArea) * $totalInvoiceAmount);
            return [$unit->id => $amount];
        })->toArray();
    }

    /**
     * Generate description for area-based distribution.
     */
    private function generateAreaDescription(Collection $units, int $totalInvoiceAmount): string
    {
        $totalArea = $units->sum('area');

        $description = "فاکتور بر اساس متراژ هر واحد توزیع شده است. مجموع کل متراژ: {$totalArea}.
                    <br />
            مبلغ هر واحد از طریق فرمول زیر محاسبه شده است:
                    <br />
            `مبلغ هر واحد = (متراژ واحد ÷ مجموع کل متراژ) × کل مبلغ فاکتور`
                    <br />
            لیست واحدها و مبالغ آنها: ";

        foreach ($units as $unit) {
            $calculatedAmount = ceil(($unit->area / $totalArea) * $totalInvoiceAmount);
            $description .= "
                    <br />
                - واحد {$unit->id}: متراژ = {$unit->area} →
                `{({$unit->area} ÷ {$totalArea}) × {$totalInvoiceAmount}} = {$calculatedAmount} ریال`";
        }

        return $description;
    }

    /**
     * Calculate parking-based distribution amounts.
     */
    private function calculateParkingDistribution(Collection $units, int $totalInvoiceAmount): array
    {
        $totalParkingSpaces = $units->sum('parking_spaces');
        if ($totalParkingSpaces == 0) {
            throw new \InvalidArgumentException("امکان توزیع مبلغ فاکتور با روش 'بر اساس پارکینگ' وجود ندارد، زیرا تعداد پارکینگ‌ها صفر است.");
        }

        return $units->mapWithKeys(function ($unit) use ($totalInvoiceAmount, $totalParkingSpaces) {
            $amount = ceil(($unit->parking_spaces / $totalParkingSpaces) * $totalInvoiceAmount);
            return [$unit->id => $amount];
        })->toArray();
    }

    /**
     * Generate description for parking-based distribution.
     */
    private function generateParkingDescription(Collection $units, int $totalInvoiceAmount): string
    {
        $totalParkingSpaces = $units->sum('parking_spaces');

        $description = "فاکتور بر اساس تعداد پارکینگ‌های هر واحد توزیع شده است. تعداد کل پارکینگ‌ها: {$totalParkingSpaces}.
                    <br />
            مبلغ هر واحد از طریق فرمول زیر محاسبه شده است:
                    <br />
            `مبلغ هر واحد = (تعداد پارکینگ‌های واحد ÷ تعداد کل پارکینگ‌ها) × کل مبلغ فاکتور`
                    <br />
            لیست واحدها و مبالغ آنها: ";

        foreach ($units as $unit) {
            $calculatedAmount = ceil(($unit->parking_spaces / $totalParkingSpaces) * $totalInvoiceAmount);
            $description .= "
                    <br />
                - واحد {$unit->id}: تعداد پارکینگ‌ها = {$unit->parking_spaces} →
                `{({$unit->parking_spaces} ÷ {$totalParkingSpaces}) × {$totalInvoiceAmount}} = {$calculatedAmount} ریال`";
        }

        return $description;
    }
}
