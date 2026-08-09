<?php

declare(strict_types=1);

namespace SwissChess\Runner;

class ParticipantNameResolver
{
    public function fixPairingNames(array $pairings, array $participants): array
    {
        $index = [];

        foreach ($participants as $p) {
            if (isset($p['number'], $p['name'])) {
                $index[(int)$p['number']] = $p['name'];
            }
        }

        foreach ($pairings as $roundIndex => $roundPairings) {
            foreach ($roundPairings as $i => $p) {
                $whiteId = (int)($p['white_id'] ?? 0);
                if (isset($index[$whiteId])) {
                    $pairings[$roundIndex][$i]['white_name'] = $index[$whiteId];
                }

                $blackId = (int)($p['black_id'] ?? 0);
                if (isset($index[$blackId])) {
                    $pairings[$roundIndex][$i]['black_name'] = $index[$blackId];
                }
            }
        }

        return $pairings;
    }

    public function fixRankingNames(array $ranking, array $participants): array
    {
        $index = $this->buildParticipantIndexByTwz($participants);

        foreach ($ranking as &$r) {
            foreach ($index as $idxKey => $fullName) {
                if (str_starts_with($idxKey, $r['twz'] . '|' . strtolower($r['name']))) {
                    $r['name'] = $fullName;
                    break;
                }
            }
        }

        return $ranking;
    }

    private function buildParticipantIndexByTwz(array $participants): array
    {
        $index = [];

        foreach ($participants as $p) {
            $key = $p['twz'] . '|' . strtolower($p['name']);
            $index[$key] = $p['name'];
        }

        return $index;
    }
}
