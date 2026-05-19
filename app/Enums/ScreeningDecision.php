<?php

declare(strict_types=1);

namespace App\Enums;

enum ScreeningDecision: string
{
    case Pending = 'pending';
    case Passed = 'passed';
    case Failed = 'failed';
    case CorrectionRequired = 'correction_required';

    public function getLabel(): string
    {
        return __('statuses.screening.'.$this->value);
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
            self::Passed => 'success',
            self::Failed => 'danger',
            self::CorrectionRequired => 'warning',
        };
    }
}
