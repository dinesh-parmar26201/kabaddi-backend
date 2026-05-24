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
        public int $superRaids,
        public int $superTackles,
        public int $raiderLineoutPoints,
        public int $defenderLineoutPoints,
        public int $technicalPoints,
        public int $totalPoints
    ) {}
}
