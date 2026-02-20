<?php

namespace App\DTO;

class TeamBreakdownDTO
{
    public function __construct(
        public int $teamId,
        public string $teamName,
        public int $raidPoints,
        public int $tacklePoints,
        public int $allOutPoints,
        public int $extraPoints,
        public int $totalPoints
    ) {}
}
