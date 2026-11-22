<?php

namespace App\Services\Verification;

use App\Enums\VerificationStatus;

final class VerificationResult
{
    public function __construct(
        public readonly string $email,
        public readonly VerificationStatus $status,
        public readonly string $detail,
        public readonly ?string $suggestion = null,
    ) {}

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'status' => $this->status->value,
            'label' => $this->status->label(),
            'color' => $this->status->color(),
            'blocking' => $this->status->isBlocking(),
            'detail' => $this->detail,
            'suggestion' => $this->suggestion,
        ];
    }
}
