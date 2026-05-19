<?php

declare(strict_types=1);

namespace App\Enums;

enum EducationLevel: string
{
    case Certificate = 'certificate';
    case Diploma = 'diploma';
    case Degree = 'degree';
    case Masters = 'masters';
    case PhD = 'phd';

    public function getLabel(): string
    {
        return __('statuses.education_level.'.$this->value);
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])
            ->all();
    }
}
