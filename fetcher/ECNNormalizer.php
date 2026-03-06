<?php
/**
 * ECNNormalizer
 *
 * Maps the raw ECN JSON response format into our own consistent API format.
 *
 * Because the ECN portal changes its data shape between elections (and sometimes
 * mid-election), all field mapping is centralised here. If ECN renames a field,
 * you only need to update this file.
 *
 * HOW TO USE:
 *   After fetching raw data from ECN, pass it through the relevant method before
 *   writing to cache. The output shape matches what the PHP API endpoints return
 *   and what the Next.js TypeScript types expect.
 *
 * HOW TO DISCOVER FIELD NAMES:
 *   1. Run the fetcher once with raw logging: var_dump($rawData)
 *   2. Check what keys the ECN JSON actually uses
 *   3. Update the array-key lookups below accordingly
 */
class ECNNormalizer
{
    // ─── Parties ─────────────────────────────────────────────────────────────

    /**
     * Normalize ECN party top-5 response into our PartyStanding[] format.
     *
     * TODO: Inspect actual ECN response keys and update the mappings below.
     * Common ECN field names (may vary): PartyName, PartySymbol, WonCount,
     * LeadCount, TotalVotes, VotePercent, PartyColor
     */
    public static function parties(array $raw): array
    {
        // If the raw response is wrapped in a key, unwrap it
        $list = $raw['data'] ?? $raw['parties'] ?? $raw['result'] ?? $raw;

        if (!is_array($list)) {
            return [];
        }

        return array_values(array_map(function ($p, $i) {
            return [
                'party_id'       => $p['PartyId']       ?? $p['party_id']   ?? ($i + 1),
                'party_name'     => $p['PartyName']      ?? $p['party_name'] ?? 'Unknown',
                'party_name_en'  => $p['PartyNameEng']   ?? $p['party_name_en'] ?? $p['PartyName'] ?? 'Unknown',
                'party_abbr'     => $p['PartyAbbr']      ?? $p['Symbol']     ?? strtoupper(substr($p['PartyName'] ?? 'UNK', 0, 3)),
                'party_color'    => $p['PartyColor']     ?? $p['party_color'] ?? '#6b7280',
                'seats_won'      => (int)($p['WonCount']    ?? $p['seats_won']    ?? 0),
                'seats_leading'  => (int)($p['LeadCount']   ?? $p['seats_leading'] ?? 0),
                'total_votes'    => (int)($p['TotalVotes']  ?? $p['total_votes']  ?? 0),
                'vote_percentage'=> (float)($p['VotePercent'] ?? $p['vote_percentage'] ?? 0),
            ];
        }, $list, array_keys($list)));
    }

    // ─── Summary ─────────────────────────────────────────────────────────────

    /**
     * Normalize ECN summary response.
     * TODO: Map actual ECN field names once you have a real response.
     */
    public static function summary(array $raw): array
    {
        $d = $raw['data'] ?? $raw['summary'] ?? $raw;

        return [
            'election_name'            => $d['ElectionName']    ?? 'प्रतिनिधि सभा निर्वाचन २०८२',
            'election_name_en'         => $d['ElectionNameEng'] ?? 'House of Representatives Election 2082',
            'total_constituencies'     => (int)($d['TotalConstituency'] ?? $d['total_constituencies'] ?? 165),
            'results_declared'         => (int)($d['DeclaredCount']     ?? $d['results_declared']     ?? 0),
            'counting_in_progress'     => (int)($d['CountingCount']     ?? $d['counting_in_progress'] ?? 0),
            'total_votes_counted'      => (int)($d['TotalVotesCounted'] ?? $d['total_votes_counted']  ?? 0),
            'total_registered_voters'  => (int)($d['TotalVoters']       ?? $d['total_registered_voters'] ?? 0),
            'turnout_percentage'       => (float)($d['TurnoutPercent']  ?? $d['turnout_percentage']   ?? 0),
            'leading_party'            => $d['LeadingParty']    ?? '',
            'leading_party_seats'      => (int)($d['LeadingSeats'] ?? 0),
            'last_updated'             => date('c'),
        ];
    }

    // ─── Constituency detail ──────────────────────────────────────────────────

    /**
     * Normalize a per-constituency ECN response.
     * TODO: Map actual ECN field names for candidates list.
     */
    public static function constituency(int $id, array $raw): array
    {
        $d          = $raw['data'] ?? $raw['constituency'] ?? $raw;
        $candidates = $raw['candidates'] ?? $d['Candidates'] ?? $d['candidates'] ?? [];

        $normalizedCandidates = array_values(array_map(function ($c, $pos) {
            $votes = (int)($c['VoteCount'] ?? $c['votes'] ?? 0);
            return [
                'id'          => $c['CandidateId']   ?? $c['id']          ?? ($pos + 1),
                'name'        => $c['CandidateName']  ?? $c['name']        ?? 'Unknown',
                'name_np'     => $c['CandidateNameNp'] ?? $c['name_np']   ?? '',
                'party'       => $c['PartyName']      ?? $c['party']       ?? 'Independent',
                'party_abbr'  => $c['PartyAbbr']      ?? $c['party_abbr']  ?? 'IND',
                'party_color' => $c['PartyColor']     ?? $c['party_color'] ?? '#6b7280',
                'votes'       => $votes,
                'percentage'  => (float)($c['VotePercent'] ?? $c['percentage'] ?? 0),
                'position'    => $pos + 1,
                'status'      => ($c['Status'] ?? $c['status'] ?? '') === 'Won' ? 'won'
                              : (($c['Status'] ?? '') === 'Lead' ? 'leading' : 'lost'),
            ];
        }, $candidates, array_keys($candidates)));

        // Sort by votes descending and assign positions
        usort($normalizedCandidates, fn($a, $b) => $b['votes'] - $a['votes']);
        foreach ($normalizedCandidates as $i => &$c) {
            $c['position'] = $i + 1;
        }

        $winner = count($normalizedCandidates) > 0 && $normalizedCandidates[0]['status'] === 'won'
            ? [
                'id'     => $normalizedCandidates[0]['id'],
                'name'   => $normalizedCandidates[0]['name'],
                'party'  => $normalizedCandidates[0]['party'],
                'votes'  => $normalizedCandidates[0]['votes'],
                'margin' => count($normalizedCandidates) > 1
                    ? $normalizedCandidates[0]['votes'] - $normalizedCandidates[1]['votes']
                    : 0,
              ]
            : null;

        return [
            'id'                      => $id,
            'name'                    => $d['ConstituencyName']   ?? $d['name']       ?? "Constituency $id",
            'name_np'                 => $d['ConstituencyNameNp'] ?? $d['name_np']    ?? '',
            'province'                => $d['ProvinceName']        ?? $d['province']   ?? '',
            'total_votes_counted'     => (int)($d['TotalVotesCounted'] ?? $d['total_votes_counted'] ?? 0),
            'total_registered_voters' => (int)($d['TotalVoters']       ?? $d['total_registered_voters'] ?? 0),
            'counting_complete'       => ($d['Status'] ?? '') === 'Declared',
            'candidates'              => $normalizedCandidates,
            'winner'                  => $winner,
        ];
    }
}
