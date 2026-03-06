<?php
/**
 * Converts raw Hamro Patro election data into our internal API cache format.
 *
 * NEW flat input shape (areas array — no nested area/result):
 *   {
 *     lastFetched: number,
 *     areas: [
 *       {
 *         areaId, areaName, areaNameEnglish, registeredVoters,
 *         districtId, districtName, districtEnglishName,
 *         stateId, stateName, stateNumber,
 *         electionResultStatus, resultFinal,
 *         totalCastVotes, totalCountedVotes,
 *         winnerCandidateId, winnerPartyId,
 *         candidateResults: [
 *           { candidateId, name, englishName, areaId, partyId, partyName,
 *             votes, winner, leading, resultFinal, imageUrl, slug, voteGain }
 *         ]
 *       }
 *     ]
 *   }
 */
class HamroPatroNormalizer
{
    // Province name → our canonical name
    private const PROVINCE_MAP = [
        'Koshi Province'         => 'Koshi',
        'Madhesh Province'       => 'Madhesh',
        'Bagmati Province'       => 'Bagmati',
        'Gandaki Province'       => 'Gandaki',
        'Lumbini Province'       => 'Lumbini',
        'Karnali Province'       => 'Karnali',
        'Sudurpashchim Province' => 'Sudurpashchim',
    ];

    // State ID → our province name fallback
    private const STATEID_MAP = [
        '1' => 'Koshi', '2' => 'Madhesh', '3' => 'Bagmati',
        '4' => 'Gandaki', '5' => 'Lumbini', '6' => 'Karnali', '7' => 'Sudurpashchim',
    ];

    // Nepali party name → display info (HP now uses Nepali names in partyName)
    private const PARTY_INFO = [
        'राष्ट्रिय स्वतन्त्र पार्टी'                               => ['abbr' => 'RSP',  'color' => '#7c3aed', 'en' => 'Rastriya Swatantra Party'],
        'नेपाली काँग्रेस'                                           => ['abbr' => 'NC',   'color' => '#1e3a8a', 'en' => 'Nepali Congress'],
        'नेपाल कम्युनिष्ट पार्टी (एकीकृत मार्क्सवादी लेनिनवादी)' => ['abbr' => 'UML',  'color' => '#dc2626', 'en' => 'CPN-UML'],
        'नेपाली कम्युनिष्ट पार्टी'                                 => ['abbr' => 'MC',   'color' => '#7f1d1d', 'en' => 'CPN (Maoist Centre)'],
        'राष्ट्रिय प्रजातन्त्र पार्टी'                             => ['abbr' => 'RPP',  'color' => '#d97706', 'en' => 'Rastriya Prajatantra Party'],
        'जनमत पार्टी'                                               => ['abbr' => 'JP',   'color' => '#065f46', 'en' => 'Janmat Party'],
        'जनता समाजवादी पार्टी, नेपाल'                              => ['abbr' => 'JSP',  'color' => '#0891b2', 'en' => 'Janata Samajwadi Party Nepal'],
        'उज्यालो नेपाल पार्टी'                                      => ['abbr' => 'UNP',  'color' => '#f97316', 'en' => 'Ujyalo Nepal Party'],
        'श्रम संस्कृति पार्टी'                                      => ['abbr' => 'LSP',  'color' => '#84cc16', 'en' => 'Shram Sanskriti Party'],
        'नेपाल मजदुर किसान पार्टी'                                  => ['abbr' => 'NMKP', 'color' => '#10b981', 'en' => 'Nepal Majdur Kisan Party'],
        'स्वतन्त्र'                                                  => ['abbr' => 'IND',  'color' => '#6b7280', 'en' => 'Independent'],
        'Independent'                                               => ['abbr' => 'IND',  'color' => '#6b7280', 'en' => 'Independent'],
    ];

    private array $raw;

    public function __construct(array $raw)
    {
        $this->raw = $raw;
    }

    private function partyInfo(string $name): array
    {
        return self::PARTY_INFO[$name] ?? [
            'abbr'  => mb_substr($name, 0, 4, 'UTF-8'),
            'color' => '#6b7280',
            'en'    => $name,
        ];
    }

    private function provinceName(array $entry): string
    {
        $hp = $entry['stateName'] ?? '';
        return self::PROVINCE_MAP[$hp]
            ?? self::STATEID_MAP[(string)($entry['stateId'] ?? '')] ?? 'Unknown';
    }

    private function statusToOurs(string $status): string
    {
        return match ($status) {
            'RESULT_DECLARED'      => 'declared',
            'COUNTING_IN_PROGRESS' => 'counting',
            default                => 'pending',
        };
    }

    /** Build constituencies/index.json data */
    public function constituenciesIndex(): array
    {
        $list = [];

        foreach ($this->raw['areas'] as $entry) {
            $areaId   = (int) ($entry['areaId'] ?? 0);
            if (!$areaId) continue;

            $areaNum  = $this->extractAreaNumber($entry['areaNameEnglish'] ?? '');
            $district = $entry['districtEnglishName'] ?? '';
            $name     = $district . ($areaNum ? "-$areaNum" : '');
            $province = $this->provinceName($entry);
            $status   = $this->statusToOurs($entry['electionResultStatus'] ?? '');

            // Find the leading candidate / winner
            $leading = null;
            $winner  = null;
            foreach ($entry['candidateResults'] ?? [] as $c) {
                if (!empty($c['winner']))  { $winner  = $c; break; }
                if (!empty($c['leading'])) { $leading = $c; }
            }

            $topCandidate = $winner ?? $leading;
            $partyName    = $topCandidate['partyName'] ?? '';
            $partyInfo    = $partyName ? $this->partyInfo($partyName) : null;

            $list[] = [
                'id'                  => $areaId,
                'name'                => $name,
                'name_np'             => $entry['areaName'] ?? '',
                'province'            => $province,
                'province_no'         => (int) ($entry['stateId'] ?? 0),
                'district'            => $district,
                'status'              => $status,
                'leading_party'       => $partyInfo['abbr'] ?? null,
                'leading_party_color' => $partyInfo['color'] ?? null,
                'leading_party_name'  => $partyName ?: null,
                'total_cast_votes'    => (int) ($entry['totalCastVotes'] ?? 0),
                'total_counted_votes' => (int) ($entry['totalCountedVotes'] ?? 0),
                'registered_voters'   => (int) ($entry['registeredVoters'] ?? 0),
            ];
        }

        usort($list, fn($a, $b) => $a['id'] <=> $b['id']);
        return $list;
    }

    /** Build a single constituency/{id}.json */
    public function constituency(array $entry): array
    {
        $areaId   = (int) ($entry['areaId'] ?? 0);
        $areaNum  = $this->extractAreaNumber($entry['areaNameEnglish'] ?? '');
        $district = $entry['districtEnglishName'] ?? '';
        $name     = $district . ($areaNum ? "-$areaNum" : '');
        $province = $this->provinceName($entry);
        $status   = $this->statusToOurs($entry['electionResultStatus'] ?? '');

        // Build candidates sorted by votes desc
        $sortedCands = $entry['candidateResults'] ?? [];
        usort($sortedCands, fn($a, $b) => (int)$b['votes'] <=> (int)$a['votes']);

        $totalVotes = array_sum(array_column($sortedCands, 'votes'));

        $candidates = [];
        foreach ($sortedCands as $i => $c) {
            $partyInfo = $this->partyInfo($c['partyName'] ?? '');
            $cStatus   = !empty($c['winner']) ? 'won' : (!empty($c['leading']) ? 'leading' : 'trailing');
            $votes     = (int) $c['votes'];

            $candidates[] = [
                'id'          => (int) $c['candidateId'],
                'name'        => $c['englishName'] ?? '',
                'name_np'     => $c['name'] ?? '',
                'party'       => $partyInfo['en'],
                'party_abbr'  => $partyInfo['abbr'],
                'party_color' => $partyInfo['color'],
                'party_name'  => $c['partyName'] ?? '',
                'image_url'   => $c['imageUrl'] ?? null,
                'votes'       => $votes,
                'percentage'  => $totalVotes > 0 ? round(($votes / $totalVotes) * 100, 1) : 0,
                'position'    => $i + 1,
                'status'      => $cStatus,
            ];
        }

        // Winner info
        $winner = null;
        if (!empty($entry['winnerCandidateId'])) {
            $wc = null;
            foreach ($entry['candidateResults'] ?? [] as $c) {
                if ((int)$c['candidateId'] === (int)$entry['winnerCandidateId']) { $wc = $c; break; }
            }
            if ($wc) {
                $margin = isset($sortedCands[1]['votes'])
                    ? (int)$wc['votes'] - (int)$sortedCands[1]['votes']
                    : 0;
                $winner = [
                    'id'     => (int) $wc['candidateId'],
                    'name'   => $wc['englishName'] ?? '',
                    'party'  => $this->partyInfo($wc['partyName'] ?? '')['en'],
                    'votes'  => (int) $wc['votes'],
                    'margin' => $margin,
                ];
            }
        }

        return [
            'id'                       => $areaId,
            'name'                     => $name,
            'name_np'                  => $entry['areaName'] ?? '',
            'province'                 => $province,
            'district'                 => $district,
            'total_votes_counted'      => (int) ($entry['totalCountedVotes'] ?? 0),
            'total_registered_voters'  => (int) ($entry['registeredVoters'] ?? 0),
            'total_cast_votes'         => (int) ($entry['totalCastVotes'] ?? 0),
            'counting_complete'        => ($entry['resultFinal'] ?? false) === true,
            'status'                   => $status,
            'candidates'               => $candidates,
            'winner'                   => $winner,
        ];
    }

    /** Build parties.json — aggregated seat + vote tallies */
    public function parties(): array
    {
        $tally = [];

        foreach ($this->raw['areas'] as $entry) {
            foreach ($entry['candidateResults'] ?? [] as $c) {
                $partyName = $c['partyName'] ?? 'Unknown';
                $info = $this->partyInfo($partyName);

                if (!isset($tally[$partyName])) {
                    $tally[$partyName] = [
                        'party_name'    => $partyName,
                        'party_name_en' => $info['en'],
                        'party_abbr'    => $info['abbr'],
                        'party_color'   => $info['color'],
                        'seats_won'     => 0,
                        'seats_leading' => 0,
                        'total_votes'   => 0,
                    ];
                }

                $tally[$partyName]['total_votes'] += (int)($c['votes'] ?? 0);
                if (!empty($c['winner']))       $tally[$partyName]['seats_won']++;
                elseif (!empty($c['leading']))  $tally[$partyName]['seats_leading']++;
            }
        }

        $list = array_values($tally);
        usort($list, fn($a, $b) =>
            ($b['seats_won'] + $b['seats_leading']) <=> ($a['seats_won'] + $a['seats_leading'])
        );

        $totalVotes = array_sum(array_column($list, 'total_votes'));
        foreach ($list as $i => &$p) {
            $p['party_id']        = $i + 1;
            $p['vote_percentage'] = $totalVotes > 0
                ? round(($p['total_votes'] / $totalVotes) * 100, 1)
                : 0;
        }

        return $list;
    }

    /** Build summary.json */
    public function summary(array $parties): array
    {
        $areas             = $this->raw['areas'];
        $declared          = 0;
        $counting          = 0;
        $totalCastVotes    = 0;
        $totalCountedVotes = 0;
        $totalRegistered   = 0;

        foreach ($areas as $entry) {
            $status = $this->statusToOurs($entry['electionResultStatus'] ?? '');
            if ($status === 'declared') $declared++;
            if ($status === 'counting') $counting++;

            $totalCastVotes    += (int) ($entry['totalCastVotes'] ?? 0);
            $totalCountedVotes += (int) ($entry['totalCountedVotes'] ?? 0);
            $totalRegistered   += (int) ($entry['registeredVoters'] ?? 0);
        }

        $topParty = $parties[0] ?? null;
        $turnout  = $totalRegistered > 0
            ? round(($totalCastVotes / $totalRegistered) * 100, 1)
            : 0;

        return [
            'election_name'            => 'प्रतिनिधि सभा निर्वाचन २०८२',
            'election_name_en'         => 'House of Representatives Election 2082',
            'total_constituencies'     => count($areas),
            'results_declared'         => $declared,
            'counting_in_progress'     => $counting,
            'total_votes_counted'      => $totalCountedVotes,
            'total_cast_votes'         => $totalCastVotes,
            'total_registered_voters'  => $totalRegistered,
            'turnout_percentage'       => $turnout,
            'leading_party'            => $topParty['party_name_en'] ?? '',
            'leading_party_seats'      => $topParty ? $topParty['seats_won'] + $topParty['seats_leading'] : 0,
            'last_updated'             => date('c', intval(($this->raw['lastFetched'] ?? time() * 1000) / 1000)),
            'data_source'              => 'Hamro Patro',
        ];
    }

    private function extractAreaNumber(string $englishName): ?int
    {
        if (preg_match('/Area\s+(\d+)/i', $englishName, $m)) {
            return (int) $m[1];
        }
        return null;
    }
}
