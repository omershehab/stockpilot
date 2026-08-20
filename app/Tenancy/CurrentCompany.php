<?php

namespace App\Tenancy;

use App\Models\Company;
use LogicException;

class CurrentCompany
{
    private ?Company $company = null;

    public function set(Company $company): void
    {
        $this->company = $company;
    }

    public function has(): bool
    {
        return $this->company !== null;
    }

    public function get(): Company
    {
        return $this->company ?? throw new LogicException('No company is active for this request.');
    }
}
