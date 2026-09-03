<?php

namespace App\Services;

use App\Models\FeeRule;
use App\Models\WithdrawalMethod;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class FeeService
{
    public function amountToMinor(string $amount): int
    {
        if (! preg_match('/^\d{1,12}(?:\.\d{1,2})?$/', $amount)) {
            throw new InvalidArgumentException('Amount must contain no more than two decimal places.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $minor = ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');

        if ($minor <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        return $minor;
    }

    /**
     * Calculate an immutable quote for a money-moving event.
     *
     * @return array{fee_minor: int, tax_minor: int, net_minor: int, fee_rule_id: int|null, fee_rule_name: string|null, tax_basis: string}
     */
    public function quote(string $event, int $amountMinor, string $currency, ?WithdrawalMethod $fallbackMethod = null, ?string $country = null): array
    {
        if ($amountMinor <= 0) {
            throw new InvalidArgumentException('The amount must be greater than zero.');
        }

        $currency = strtoupper($currency);
        $rule = $this->activeRule($event, $currency, $country);

        if ($rule === null && $fallbackMethod === null) {
            $feeFixedMinor = 0;
            $feePercentBps = 0;
            $maximumFeeMinor = null;
            $taxFixedMinor = 0;
            $taxPercentBps = 0;
            $taxBasis = 'fee';
        } else {
            $feeFixedMinor = $rule?->fee_fixed_minor ?? $fallbackMethod?->fee_fixed_minor ?? 0;
            $feePercentBps = $rule?->fee_percent_bps ?? $fallbackMethod?->fee_percent_bps ?? 0;
            $maximumFeeMinor = $rule?->maximum_fee_minor;
            $taxFixedMinor = $rule?->tax_fixed_minor ?? 0;
            $taxPercentBps = $rule?->tax_percent_bps ?? 0;
            $taxBasis = $rule?->tax_basis ?? 'fee';
        }

        $feeMinor = $feeFixedMinor + $this->percentage($amountMinor, $feePercentBps);
        if ($maximumFeeMinor !== null) {
            $feeMinor = min($feeMinor, $maximumFeeMinor);
        }

        $taxableMinor = $taxBasis === 'gross' ? $amountMinor : $feeMinor;
        $taxMinor = $taxFixedMinor + $this->percentage($taxableMinor, $taxPercentBps);
        $netMinor = $amountMinor - $feeMinor - $taxMinor;

        if ($netMinor <= 0) {
            throw new InvalidArgumentException('The fee and tax must be lower than the requested amount.');
        }

        return [
            'fee_minor' => $feeMinor,
            'tax_minor' => $taxMinor,
            'net_minor' => $netMinor,
            'fee_rule_id' => $rule?->getKey(),
            'fee_rule_name' => $rule?->name,
            'tax_basis' => $taxBasis,
        ];
    }

    private function activeRule(string $event, string $currency, ?string $country): ?FeeRule
    {
        $now = Carbon::now();

        return FeeRule::query()
            ->where('event', $event)
            ->where('is_enabled', true)
            ->where(function ($query) use ($currency): void {
                $query->where('currency', $currency)->orWhereNull('currency');
            })
            ->where(function ($query) use ($country): void {
                if ($country === null) {
                    $query->whereNull('country');
                } else {
                    $query->where('country', $country)->orWhereNull('country');
                }
            })
            ->where(function ($query) use ($now): void {
                $query->whereNull('effective_from')->orWhere('effective_from', '<=', $now);
            })
            ->where(function ($query) use ($now): void {
                $query->whereNull('effective_until')->orWhere('effective_until', '>=', $now);
            })
            ->orderByRaw('CASE WHEN country = ? THEN 0 ELSE 1 END', [$country ?? ''])
            ->orderByRaw('CASE WHEN currency = ? THEN 0 ELSE 1 END', [$currency])
            ->orderByDesc('priority')
            ->orderByDesc('id')
            ->first();
    }

    private function percentage(int $amountMinor, int $basisPoints): int
    {
        return intdiv(($amountMinor * $basisPoints) + 5000, 10000);
    }
}
