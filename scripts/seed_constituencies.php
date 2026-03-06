<?php
/**
 * Generates comprehensive mock data for all 165 HoR constituencies
 * reflecting a plausible 2082 election result.
 *
 * Run: php scripts/seed_constituencies.php
 */

require_once __DIR__ . '/../api/config.php';

// Party pool with colors
$PARTIES = [
    ['abbr' => 'NC',     'name' => 'Nepali Congress',         'color' => '#1e3a8a'],
    ['abbr' => 'UML',    'name' => 'CPN-UML',                 'color' => '#dc2626'],
    ['abbr' => 'MC',     'name' => 'CPN (Maoist Centre)',     'color' => '#7f1d1d'],
    ['abbr' => 'RSP',    'name' => 'Rastriya Swatantra Party','color' => '#7c3aed'],
    ['abbr' => 'RPP',    'name' => 'Rastriya Prajatantra Party','color' => '#d97706'],
    ['abbr' => 'JP',     'name' => 'Janmat Party',            'color' => '#065f46'],
    ['abbr' => 'Others', 'name' => 'Others',                  'color' => '#6b7280'],
];

// Nepal's 165 HoR constituencies (2082) — Province → list of constituency names
$CONSTITUENCIES = [
    'Koshi' => [
        'Taplejung', 'Sankhuwasabha-1', 'Sankhuwasabha-2', 'Solukhumbu',
        'Okhaldhunga', 'Khotang-1', 'Khotang-2', 'Bhojpur', 'Dhankuta',
        'Terhathum', 'Sunsari-1', 'Sunsari-2', 'Sunsari-3', 'Morang-1',
        'Morang-2', 'Morang-3', 'Morang-4', 'Jhapa-1', 'Jhapa-2',
        'Jhapa-3', 'Jhapa-4', 'Ilam-1', 'Ilam-2', 'Panchthar',
        'Taplejung-2'
    ],
    'Madhesh' => [
        'Sarlahi-1', 'Sarlahi-2', 'Sarlahi-3', 'Sarlahi-4', 'Siraha-1',
        'Siraha-2', 'Siraha-3', 'Saptari-1', 'Saptari-2', 'Saptari-3',
        'Udayapur-1', 'Udayapur-2', 'Mahottari-1', 'Mahottari-2', 'Mahottari-3',
        'Dhanusa-1', 'Dhanusa-2', 'Dhanusa-3', 'Dhanusa-4', 'Parsa-1',
        'Parsa-2', 'Parsa-3', 'Bara-1', 'Bara-2', 'Bara-3',
        'Rautahat-1', 'Rautahat-2', 'Rautahat-3', 'Nawalpur-1', 'Nawalpur-2',
        'Chitwan-1', 'Chitwan-2'
    ],
    'Bagmati' => [
        'Kathmandu-1', 'Kathmandu-2', 'Kathmandu-3', 'Kathmandu-4', 'Kathmandu-5',
        'Kathmandu-6', 'Kathmandu-7', 'Kathmandu-8', 'Kathmandu-9', 'Kathmandu-10',
        'Lalitpur-1', 'Lalitpur-2', 'Lalitpur-3', 'Bhaktapur-1', 'Bhaktapur-2',
        'Kavrepalanchok-1', 'Kavrepalanchok-2', 'Kavrepalanchok-3', 'Sindhuli-1',
        'Sindhuli-2', 'Ramechhap-1', 'Ramechhap-2', 'Dolakha-1', 'Dolakha-2',
        'Sindhupalchok-1', 'Sindhupalchok-2', 'Sindhupalchok-3', 'Nuwakot-1',
        'Nuwakot-2', 'Rasuwa', 'Dhading-1', 'Dhading-2', 'Dhading-3',
        'Makwanpur-1', 'Makwanpur-2', 'Makwanpur-3', 'Chitwan-3', 'Chitwan-4',
        'Chitwan-5', 'Bhaktapur-3', 'Lalitpur-4', 'Kathmandu-11', 'Kathmandu-12',
        'Kathmandu-13'
    ],
    'Gandaki' => [
        'Gorkha-1', 'Gorkha-2', 'Lamjung', 'Tanahun-1', 'Tanahun-2',
        'Kaski-1', 'Kaski-2', 'Kaski-3', 'Parbat', 'Baglung-1',
        'Baglung-2', 'Myagdi', 'Mustang', 'Manang', 'Nawalpur-3',
        'Palpa-1', 'Palpa-2', 'Syangja-1', 'Syangja-2'
    ],
    'Lumbini' => [
        'Rupandehi-1', 'Rupandehi-2', 'Rupandehi-3', 'Rupandehi-4',
        'Kapilbastu-1', 'Kapilbastu-2', 'Kapilbastu-3', 'Nawalparasi-1',
        'Nawalparasi-2', 'Nawalparasi-3', 'Gulmi-1', 'Gulmi-2',
        'Arghakhanchi', 'Pyuthan-1', 'Pyuthan-2', 'Rolpa-1', 'Rolpa-2',
        'Eastern Rukum', 'Dang-1', 'Dang-2', 'Dang-3', 'Banke-1',
        'Banke-2', 'Bardiya-1', 'Bardiya-2', 'Bardiya-3'
    ],
    'Karnali' => [
        'Western Rukum', 'Salyan-1', 'Salyan-2', 'Surkhet-1', 'Surkhet-2',
        'Surkhet-3', 'Dailekh-1', 'Dailekh-2', 'Jajarkot', 'Dolpa'
    ],
    'Sudurpashchim' => [
        'Kailali-1', 'Kailali-2', 'Kailali-3', 'Kailali-4', 'Kailali-5',
        'Kanchanpur-1', 'Kanchanpur-2', 'Kanchanpur-3', 'Dadeldhura',
        'Doti-1', 'Doti-2', 'Achham-1', 'Achham-2', 'Bajhang',
        'Bajura', 'Humla', 'Mugu', 'Dolpa-Far', 'Kalikot',
        'Jajarkot-Far'
    ],
];

// Seat allocation probability weights per province
// [NC, UML, MC, RSP, RPP, JP, Others]
$PROVINCE_WEIGHTS = [
    'Koshi'         => [30, 28, 10,  8,  5, 3, 16],
    'Madhesh'       => [20, 15,  8, 10,  5,25, 17],
    'Bagmati'       => [30, 20,  8, 22,  8, 2, 10],
    'Gandaki'       => [35, 22, 15,  8, 10, 2,  8],
    'Lumbini'       => [30, 22, 15,  8,  8, 5, 12],
    'Karnali'       => [25, 20, 30,  5,  8, 2, 10],
    'Sudurpashchim' => [25, 20, 10,  5, 25, 2, 13],
];

// Nepali name translations (partial — we'll auto-generate for most)
$NP_NAMES = [
    'Taplejung'    => 'ताप्लेजुङ',   'Sankhuwasabha' => 'सङ्खुवासभा',
    'Solukhumbu'   => 'सोलुखुम्बु',  'Okhaldhunga'   => 'ओखलढुङ्गा',
    'Khotang'      => 'खोटाङ',       'Bhojpur'       => 'भोजपुर',
    'Dhankuta'     => 'धनकुटा',      'Terhathum'     => 'तेह्रथुम',
    'Sunsari'      => 'सुनसरी',      'Morang'        => 'मोरङ',
    'Jhapa'        => 'झापा',        'Ilam'          => 'इलाम',
    'Panchthar'    => 'पाँचथर',      'Sarlahi'       => 'सर्लाही',
    'Siraha'       => 'सिरहा',       'Saptari'       => 'सप्तरी',
    'Udayapur'     => 'उदयपुर',      'Mahottari'     => 'महोत्तरी',
    'Dhanusa'      => 'धनुषा',       'Parsa'         => 'पर्सा',
    'Bara'         => 'बारा',        'Rautahat'      => 'रौतहट',
    'Nawalpur'     => 'नवलपुर',      'Chitwan'       => 'चितवन',
    'Kathmandu'    => 'काठमाडौं',    'Lalitpur'      => 'ललितपुर',
    'Bhaktapur'    => 'भक्तपुर',     'Kavrepalanchok'=> 'काभ्रेपलाञ्चोक',
    'Sindhuli'     => 'सिन्धुली',    'Ramechhap'     => 'रामेछाप',
    'Dolakha'      => 'दोलखा',       'Sindhupalchok' => 'सिन्धुपाल्चोक',
    'Nuwakot'      => 'नुवाकोट',     'Rasuwa'        => 'रसुवा',
    'Dhading'      => 'धादिङ',       'Makwanpur'     => 'मकवानपुर',
    'Gorkha'       => 'गोर्खा',      'Lamjung'       => 'लमजुङ',
    'Tanahun'      => 'तनहुँ',       'Kaski'         => 'कास्की',
    'Parbat'       => 'पर्वत',       'Baglung'       => 'बाग्लुङ',
    'Myagdi'       => 'म्याग्दी',    'Mustang'       => 'मुस्ताङ',
    'Manang'       => 'मनाङ',        'Palpa'         => 'पाल्पा',
    'Syangja'      => 'स्याङ्जा',    'Rupandehi'     => 'रुपन्देही',
    'Kapilbastu'   => 'कपिलवस्तु',   'Nawalparasi'   => 'नवलपरासी',
    'Gulmi'        => 'गुल्मी',      'Arghakhanchi'  => 'अर्घाखाँची',
    'Pyuthan'      => 'प्युठान',     'Rolpa'         => 'रोल्पा',
    'Dang'         => 'दाङ',         'Banke'         => 'बाँके',
    'Bardiya'      => 'बर्दिया',     'Rukum'         => 'रुकुम',
    'Salyan'       => 'सल्यान',      'Surkhet'       => 'सुर्खेत',
    'Dailekh'      => 'दैलेख',       'Jajarkot'      => 'जाजरकोट',
    'Dolpa'        => 'डोल्पा',      'Kailali'       => 'कैलाली',
    'Kanchanpur'   => 'कञ्चनपुर',    'Dadeldhura'    => 'डडेलधुरा',
    'Doti'         => 'डोटी',        'Achham'        => 'अछाम',
    'Bajhang'      => 'बझाङ',        'Bajura'        => 'बाजुरा',
    'Humla'        => 'हुम्ला',      'Mugu'          => 'मुगु',
    'Kalikot'      => 'कालीकोट',
];

$NUM_SUFFIX_NP = ['', '-१', '-२', '-३', '-४', '-५', '-६', '-७', '-८', '-९', '-१०',
    '-११', '-१२', '-१३'];

function toNepaliName(string $name, array $npMap, array $suffix): string {
    // "Kathmandu-3" → "काठमाडौं-३"
    if (preg_match('/^(.+?)-(\d+)$/', $name, $m)) {
        $base = $m[1]; $num = (int)$m[2];
        $npBase = $npMap[$base] ?? $base;
        return $npBase . ($suffix[$num] ?? "-$num");
    }
    // Special cases with embedded numbers handled separately
    if (str_contains($name, 'Far')) {
        $base = str_replace(['-Far', ' Far'], '', $name);
        return ($npMap[$base] ?? $base) . ' (पश्चिम)';
    }
    return $npMap[$name] ?? $name;
}

function pickParty(array $weights, array $parties): array {
    $total = array_sum($weights);
    $r = rand(1, $total);
    $cum = 0;
    foreach ($weights as $i => $w) {
        $cum += $w;
        if ($r <= $cum) return $parties[$i];
    }
    return $parties[0];
}

// Decide how many are "counting" vs "declared"
// Let's say ~90% declared, ~10% counting for realism
$constituencies = [];
$id = 1;

$provinceOrder = ['Koshi','Madhesh','Bagmati','Gandaki','Lumbini','Karnali','Sudurpashchim'];

foreach ($provinceOrder as $province) {
    $seats = $CONSTITUENCIES[$province];
    $weights = $PROVINCE_WEIGHTS[$province];

    foreach ($seats as $seatName) {
        $status = rand(1,100) <= 88 ? 'declared' : 'counting';
        $party  = pickParty($weights, $PARTIES);

        $constituencies[] = [
            'id'                  => $id,
            'name'                => $seatName,
            'name_np'             => toNepaliName($seatName, $NP_NAMES, $NUM_SUFFIX_NP),
            'province'            => $province,
            'province_no'         => array_search($province, $provinceOrder) + 1,
            'status'              => $status,
            'leading_party'       => $party['abbr'],
            'leading_party_color' => $party['color'],
        ];
        $id++;
    }
}

// Tally seats and regenerate parties.json to be consistent
$tally = [];
foreach ($PARTIES as $p) {
    $tally[$p['abbr']] = ['won' => 0, 'leading' => 0];
}

foreach ($constituencies as $c) {
    $abbr = $c['leading_party'];
    if ($c['status'] === 'declared') {
        $tally[$abbr]['won']++;
    } else {
        $tally[$abbr]['leading']++;
    }
}

// Build parties data
$voteBase = [
    'NC'     => 1823456, 'UML'    => 1654321, 'MC'     => 812345,
    'RSP'    => 743210,  'RPP'    => 534100,  'JP'     => 312440,
    'Others' => 987230,
];
$vpBase = ['NC'=>22.1,'UML'=>20.1,'MC'=>9.9,'RSP'=>9.0,'RPP'=>6.5,'JP'=>3.8,'Others'=>12.0];

$partiesData = [];
foreach ($PARTIES as $idx => $p) {
    $partiesData[] = [
        'party_id'       => $idx + 1,
        'party_name'     => match($p['abbr']) {
            'NC'     => 'नेपाली कांग्रेस',
            'UML'    => 'नेकपा (एमाले)',
            'MC'     => 'नेकपा (माओवादी केन्द्र)',
            'RSP'    => 'राष्ट्रिय स्वतन्त्र पार्टी',
            'RPP'    => 'राष्ट्रिय प्रजातन्त्र पार्टी',
            'JP'     => 'जनमत पार्टी',
            default  => 'अन्य दल',
        },
        'party_name_en'  => $p['name'],
        'party_abbr'     => $p['abbr'],
        'party_color'    => $p['color'],
        'seats_won'      => $tally[$p['abbr']]['won'],
        'seats_leading'  => $tally[$p['abbr']]['leading'],
        'total_votes'    => $voteBase[$p['abbr']] ?? 200000,
        'vote_percentage'=> $vpBase[$p['abbr']] ?? 2.0,
    ];
}

// Sort by total seats
usort($partiesData, fn($a,$b) =>
    ($b['seats_won']+$b['seats_leading']) <=> ($a['seats_won']+$a['seats_leading'])
);

// Summary data
$declared  = count(array_filter($constituencies, fn($c) => $c['status'] === 'declared'));
$counting  = count(array_filter($constituencies, fn($c) => $c['status'] === 'counting'));
$topParty  = $partiesData[0];

$now = time();
$envelope = ['fetched_at' => $now, 'ttl' => 3600]; // 1 hour TTL for dev

// Write constituencies index
$cacheDir = __DIR__ . '/../cache/constituencies';
if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);

file_put_contents(
    $cacheDir . '/index.json',
    json_encode($envelope + ['data' => $constituencies], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);
echo "Written constituencies/index.json (" . count($constituencies) . " entries)\n";

// Write parties
file_put_contents(
    __DIR__ . '/../cache/parties.json',
    json_encode($envelope + ['data' => $partiesData], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);
echo "Written cache/parties.json\n";

// Write summary
$summary = [
    'fetched_at' => $now, 'ttl' => 300,
    'data' => [
        'election_name'            => 'प्रतिनिधि सभा निर्वाचन २०८२',
        'election_name_en'         => 'House of Representatives Election 2082',
        'total_constituencies'     => count($constituencies),
        'results_declared'         => $declared,
        'counting_in_progress'     => $counting,
        'total_votes_counted'      => 8241532,
        'total_registered_voters'  => 17891245,
        'turnout_percentage'       => 61.4,
        'leading_party'            => $topParty['party_name_en'],
        'leading_party_seats'      => $topParty['seats_won'],
        'last_updated'             => date('c'),
    ],
];
file_put_contents(
    __DIR__ . '/../cache/summary.json',
    json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);
echo "Written cache/summary.json\n";

echo "\nSeat tally:\n";
foreach ($partiesData as $p) {
    echo "  {$p['party_abbr']}: {$p['seats_won']} won + {$p['seats_leading']} leading = " .
         ($p['seats_won']+$p['seats_leading']) . "\n";
}
echo "\nTotal: $declared declared, $counting counting\n";
