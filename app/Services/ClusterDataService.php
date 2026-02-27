<?php

namespace App\Services;

use App\Models\ClusterData;
use Illuminate\Support\Collection;

class ClusterDataService
{
    /**
     * Return all ClusterData records ordered by newest first.
     */
    public function getAllOrderedByLatest(): Collection
    {
        return ClusterData::orderBy('id', 'desc')->get();
    }

    /**
     * Return total message count plus distinct PapaDuck and MamaDuck counts
     * in a single database round-trip using standard conditional aggregates
     * (compatible with SQLite, MySQL, and PostgreSQL).
     *
     * @return array{count: int, papaducks: int, mamaducks: int}
     */
    public function getDashboardStats(): array
    {
        $row = ClusterData::selectRaw("
            COUNT(*) as total,
            COUNT(DISTINCT CASE WHEN duck_type = 1 THEN duck_id END) as papaducks,
            COUNT(DISTINCT CASE WHEN duck_type = 2 THEN duck_id END) as mamaducks
        ")->first();

        return [
            'count'     => (int) $row->total,
            'papaducks' => (int) $row->papaducks,
            'mamaducks' => (int) $row->mamaducks,
        ];
    }

    /**
     * Return alert/status records (all) for the DataTable JSON feed.
     *
     * @return array{data: Collection, totalCount: int}
     */
    public function getJsonFeed(): array
    {
        $clusters = ClusterData::whereIn('topic', ['alert', 'status'])
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($cluster) {
                $urgency = $cluster->urgency;
                return array_merge($cluster->toArray(), [
                    'display_text'  => $cluster->display_text,
                    'urgency_value' => $urgency?->value,
                    'urgency_label' => $urgency?->label(),
                    'map_embed_url' => $cluster->map_embed_url,
                ]);
            });

        return ['data' => $clusters, 'totalCount' => $clusters->count()];
    }

    /**
     * Return the latest 4 alert/status records and their total count
     * for the dashboard timeline.
     *
     * @return array{data: Collection, totalCount: int}
     */
    public function getTimeline(): array
    {
        $data  = ClusterData::whereIn('topic', ['alert', 'status'])
            ->orderBy('id', 'desc')
            ->take(4)
            ->get();

        $total = ClusterData::whereIn('topic', ['alert', 'status'])->count();

        return ['data' => $data, 'totalCount' => $total];
    }

    /**
     * Fetch the latest ClusterData record per duck_id.
     * Topic 22 (MSG_READ receipts) and 'outbound' (operator-sent messages)
     * are excluded so they do not override the displayed current status on the card.
     */
    public function getLatestPerDuck(): Collection
    {
        return ClusterData::whereIn('id', function ($query) {
            $query->selectRaw('max(id)')
                ->from('cluster_data')
                ->whereIn('topic', ['status', 'alert'])
                ->groupBy('duck_id');
        })->get();
    }

    /**
     * From the given collection, return the ID of the most recently created
     * record whose payload contains LAT/LNG coordinates.
     */
    public function latestWithCoordsId(Collection $ducks): ?int
    {
        return $ducks
            ->filter(fn(ClusterData $d) => $d->map_url !== null)
            ->sortByDesc('created_at')
            ->first()
            ?->id;
    }

    /**
     * Return the last N ClusterData records for every duck_id, keyed by duck_id.
     */
    public function getRecentMessagesPerDuck(int $limit = 5): Collection
    {
        return ClusterData::orderByDesc('id')
            ->whereIn('topic', ['status', 'alert', 'outbound', 'dcmd'])
            ->get()
            ->groupBy('duck_id')
            ->map(fn($rows) => $rows->take($limit)->map(fn($row) => [
                'id'         => $row->id,
                'message_id' => $row->message_id,
                'topic'      => $row->topic,
                'payload'    => $row->payload,
                'text'       => $row->display_text,
                'map_url'    => $row->map_url,
                'created_at' => $row->created_at,
                'direction'  => $row->topic === 'outbound' ? 'outbound' : 'inbound',
            ])->values());
    }

    /**
     * Return the last known map_url per duck_id, keyed by duck_id.
     * Searches all records, not just the latest per duck.
     */
    public function lastKnownCoordsPerDuck(): Collection
    {
        return ClusterData::orderByDesc('id')
            ->whereIn('topic', ['status', 'alert', 'outbound', 'dcmd'])
            ->get()
            ->filter(fn(ClusterData $d) => $d->map_url !== null)
            ->groupBy('duck_id')
            ->map(function ($rows) {
                $first   = $rows->first();
                $lat     = null;
                $lng     = null;
                if (preg_match('/LAT:(-?\d+(?:\.\d+)?),LNG:(-?\d+(?:\.\d+)?)/', $first->payload ?? '', $m)) {
                    $lat = $m[1];
                    $lng = $m[2];
                }
                return [
                    'map_url'    => $first->map_url,
                    'created_at' => $first->created_at,
                    'lat'        => $lat,
                    'lng'        => $lng,
                ];
            });
    }

    /**
     * Return message counts for each of the past 12 hours (can overlap into
     * yesterday), plus a trend comparing the current hour to the previous one.
     *
     * Uses only Eloquent + PHP-level grouping so the query works with any
     * database driver (SQLite, MySQL, PostgreSQL, etc.).
     *
     * @return array{labels: string[], data: int[], trend: array{direction: string, percentage: float, current_hour: int, previous_hour: int}}
     */
    public function getHourlyMessageCounts(): array
    {
        // Build the 12 hourly slots ending at the current (incomplete) hour.
        $windowStart = now()->subHours(11)->startOfHour();
        $windowEnd   = now()->endOfHour();

        // Fetch only created_at for the window — Eloquent casts to Carbon automatically.
        $records = ClusterData::where('created_at', '>=', $windowStart)
            ->where('created_at', '<=', $windowEnd)
            ->get(['created_at']);

        $labels = [];
        $data   = [];

        for ($i = 0; $i < 12; $i++) {
            $slotStart = $windowStart->copy()->addHours($i);
            $slotEnd   = $slotStart->copy()->endOfHour();

            $labels[] = $slotStart->format('H:i');
            $data[]   = $records->filter(
                fn($r) => $r->created_at->between($slotStart, $slotEnd)
            )->count();
        }

        // Trend: last complete slot (index 10) vs current slot (index 11).
        $currentCount  = $data[11] ?? 0;
        $previousCount = $data[10] ?? 0;

        $direction  = $currentCount >= $previousCount ? 'up' : 'down';
        $percentage = $previousCount > 0
            ? round(abs(($currentCount - $previousCount) / $previousCount) * 100, 1)
            : ($currentCount > 0 ? 100.0 : 0.0);

        return [
            'labels' => $labels,
            'data'   => $data,
            'trend'  => [
                'direction'     => $direction,
                'percentage'    => $percentage,
                'current_hour'  => $currentCount,
                'previous_hour' => $previousCount,
            ],
        ];
    }

    /**
     * Build the merged per-duck history payload used by /status/history.
     *
     * @return Collection<string, array>
     */
    public function buildHistoryResponse(): Collection
    {
        $messages   = $this->getRecentMessagesPerDuck(50);
        $lastCoords = $this->lastKnownCoordsPerDuck();
        $allDucks   = $messages->keys()->merge($lastCoords->keys())->unique();

        return $allDucks->mapWithKeys(function ($duckId) use ($messages, $lastCoords) {
            $latestMessage = $messages->get($duckId, collect())->first();

            return [$duckId => [
                'messages'  => $messages->get($duckId, collect())->values(),
                'last_seen' => $latestMessage ? [
                    'created_at'            => $latestMessage['created_at'],
                    'created_at_for_humans' => $latestMessage['created_at']->diffForHumans(),
                    'is_online'             => $latestMessage['created_at']->gt(now()->subHour()),
                ] : null,
                'last_coords' => $lastCoords->has($duckId) ? [
                    'map_url'               => $lastCoords[$duckId]['map_url'],
                    'created_at'            => $lastCoords[$duckId]['created_at'],
                    'created_at_for_humans' => $lastCoords[$duckId]['created_at']->diffForHumans(),
                    'lat'                   => $lastCoords[$duckId]['lat'],
                    'lng'                   => $lastCoords[$duckId]['lng'],
                ] : null,
            ]];
        });
    }
}
