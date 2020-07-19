<?php

namespace App\Traits;

use App\Models\Stakeholder;

trait StakeholderTrait
{
    public function checkStakeholder($sid)
    {
        $stakeholder = Stakeholder::firstWhere('stakeholder_uid', $sid);
        return $stakeholder;
    }
}
