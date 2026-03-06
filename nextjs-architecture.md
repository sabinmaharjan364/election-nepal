# Next.js Frontend Architecture — Nepal Election Dashboard

## Project Setup

```bash
npx create-next-app@latest election-dashboard --typescript --tailwind --app
cd election-dashboard
npm install recharts swr date-fns
```

## Directory Structure

```
election-dashboard/
├── app/
│   ├── layout.tsx              # Root layout, live-update banner
│   ├── page.tsx                # National summary dashboard
│   ├── constituency/
│   │   └── [id]/
│   │       └── page.tsx        # Per-constituency view
│   └── parties/
│       └── page.tsx            # Party standings
├── components/
│   ├── NationalSummary.tsx     # Seats won, % counted, leading party
│   ├── PartyChart.tsx          # Bar/pie chart via recharts
│   ├── ConstituencyTable.tsx   # Sortable candidate table
│   ├── LiveTicker.tsx          # Auto-scrolling recent changes
│   └── LastUpdated.tsx         # "Updated X seconds ago" badge
├── lib/
│   ├── api.ts                  # Typed fetch functions to your PHP API
│   └── types.ts                # TypeScript interfaces for all data shapes
└── hooks/
    └── useElectionData.ts      # SWR polling hook (refreshInterval: 30000)
```

## Key Implementation Patterns

### 1. Typed API layer — lib/api.ts

```typescript
const PHP_API = process.env.NEXT_PUBLIC_PHP_API_URL; // e.g. https://yoursite.com/api

export async function getSummary(): Promise<SummaryResponse> {
  const res = await fetch(`${PHP_API}/summary.php`, { next: { revalidate: 30 } });
  if (!res.ok) throw new Error('Failed to fetch summary');
  return res.json();
}

export async function getConstituency(id: number): Promise<ConstituencyResponse> {
  const res = await fetch(`${PHP_API}/constituency.php?id=${id}`);
  if (!res.ok) throw new Error(`Failed to fetch constituency ${id}`);
  return res.json();
}
```

### 2. SWR polling hook — hooks/useElectionData.ts

```typescript
import useSWR from 'swr';

const fetcher = (url: string) => fetch(url).then(r => r.json());

export function useSummary() {
  return useSWR(`${process.env.NEXT_PUBLIC_PHP_API_URL}/summary.php`, fetcher, {
    refreshInterval: 30_000,   // poll every 30 seconds
    revalidateOnFocus: false,
    keepPreviousData: true,    // show stale data while fetching new
  });
}

export function useConstituency(id: number) {
  return useSWR(`${process.env.NEXT_PUBLIC_PHP_API_URL}/constituency.php?id=${id}`, fetcher, {
    refreshInterval: 60_000,
  });
}
```

### 3. National summary page — app/page.tsx

```tsx
'use client';
import { useSummary } from '@/hooks/useElectionData';
import PartyChart from '@/components/PartyChart';
import LastUpdated from '@/components/LastUpdated';

export default function Home() {
  const { data, error, isLoading } = useSummary();

  if (isLoading) return <div>Loading...</div>;
  if (error)     return <div>Failed to load. Retrying...</div>;

  return (
    <main>
      <LastUpdated timestamp={data.fetched_at} />
      {data.stale && <div className="banner">Data may be delayed</div>}
      <PartyChart parties={data.data.parties} />
      {/* ... seats summary, leading candidates, etc. */}
    </main>
  );
}
```

### 4. Types — lib/types.ts

```typescript
export interface Candidate {
  id: number;
  name: string;
  party: string;
  party_symbol: string;
  votes: number;
  percentage: number;
}

export interface Constituency {
  id: number;
  name: string;
  province: string;
  total_votes_counted: number;
  candidates: Candidate[];
  winner?: Candidate;
}

export interface PartyStanding {
  party_name: string;
  party_abbr: string;
  seats_won: number;
  seats_leading: number;
  total_votes: number;
}

export interface SummaryResponse {
  fetched_at: number;
  stale: boolean;
  data: {
    election_name: string;
    total_constituencies: number;
    results_declared: number;
    parties: PartyStanding[];
  };
}
```

## Environment Variables

```env
# .env.local
NEXT_PUBLIC_PHP_API_URL=http://localhost/election/api
```

## Charts (recharts)

```tsx
// PartyChart.tsx
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer } from 'recharts';

export default function PartyChart({ parties }: { parties: PartyStanding[] }) {
  return (
    <ResponsiveContainer width="100%" height={300}>
      <BarChart data={parties}>
        <XAxis dataKey="party_abbr" />
        <YAxis />
        <Tooltip />
        <Bar dataKey="seats_won"     fill="#dc2626" name="Seats Won" />
        <Bar dataKey="seats_leading" fill="#fca5a5" name="Leading" />
      </BarChart>
    </ResponsiveContainer>
  );
}
```

## Cron Setup (Linux server)

```cron
# Run fetcher every 60 seconds
* * * * * php /var/www/html/election/fetcher/cron.php >> /var/log/ecn_fetch.log 2>&1
```

## Resilience Strategy

| Problem                          | Solution                                              |
|----------------------------------|-------------------------------------------------------|
| ECN site goes down               | Serve stale cache with `stale: true` flag in response |
| ECN changes endpoint URLs        | Update `ECN_ENDPOINTS` in `config.php` only           |
| High traffic                     | Add Nginx caching in front of PHP API (cache 30s)     |
| Session expires during counting  | ECNFetcher auto-retries with fresh session            |
| Constituency ID changes          | Re-fetch index from ECN; no hardcoding in Next.js     |
