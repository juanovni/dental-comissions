<?php

namespace App\Services;

use App\Models\PaymentMethod;
use Illuminate\Support\Str;

class PaymentMethodResolver
{
    public function resolve(?string $value): ?PaymentMethod
    {
        $normalized = $this->normalize($value ?? '');

        if (!$normalized) {
            return null;
        }

        return PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->first(function (PaymentMethod $paymentMethod) use ($normalized): bool {
                $candidates = array_filter([
                    $paymentMethod->name,
                    $paymentMethod->code,
                    ...($paymentMethod->aliases ?? []),
                ]);

                foreach ($candidates as $candidate) {
                    if ($this->normalize($candidate) === $normalized) {
                        return true;
                    }
                }

                return false;
            });
    }

    public function findInMessage(string $messageBody): ?string
    {
        $normalizedMessage = $this->normalize($messageBody);

        return PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->flatMap(fn (PaymentMethod $paymentMethod): array => array_filter([
                $paymentMethod->name,
                $paymentMethod->code,
                ...($paymentMethod->aliases ?? []),
            ]))
            ->first(fn (string $candidate): bool => preg_match(
                '/\b' . preg_quote($this->normalize($candidate), '/') . '\b/u',
                $normalizedMessage,
            ) === 1);
    }

    private function normalize(string $value): string
    {
        return Str::of($value)->lower()->ascii()->squish()->toString();
    }
}
