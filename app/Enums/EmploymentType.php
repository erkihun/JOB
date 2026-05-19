<?php

declare(strict_types=1);

namespace App\Enums;

enum EmploymentType: string
{
    case Permanent = 'permanent';
    case Contract = 'contract';
    case Temporary = 'temporary';
    case Internship = 'internship';
    case PartTime = 'part_time';

    public function getLabel(): string
    {
        return __('statuses.employment_type.'.$this->value);
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
