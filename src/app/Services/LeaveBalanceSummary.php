<?php

namespace App\Services;

readonly class LeaveBalanceSummary
{
    public function __construct(
        public float $entitled,
        public float $used,
        public float $pending,
        public float $bonOutstanding,
        public float $available,
    ) {}
}
