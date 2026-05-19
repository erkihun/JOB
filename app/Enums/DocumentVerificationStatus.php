<?php

declare(strict_types=1);

namespace App\Enums;

enum DocumentVerificationStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return __('statuses.document_verification.'.$this->value);
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getColor(): string|array|null
    {
        return $this->color();
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Verified => 'success',
            self::Rejected => 'danger',
        };
    }
}
