<?php

namespace App\Services;

use App\Models\ClusterData;
use App\Repositories\ClusterDataRepository;
use Illuminate\Support\Collection;

class ClusterDataService
{
    public function __construct(
        private readonly ClusterDataRepository $repository
    ) {}

    /**
     * All records, newest first — used to seed the dashboard DataTable on
     * first load before the AJAX feed takes over.
     */
    public function getAllOrderedByLatest(): Collection
    {
        return $this->repository->getAllOrderedByLatest();
    }

    /**
     * Summary counts for the dashboard stat cards.
     *
     * @return array{count: int, papaducks: int, mamaducks: int}
     */
    public function getDashboardStats(): array
    {
        $row = $this->repository->getStats();

        return [
            'count'     => (int) $row->total,
            'papaducks' => (int) $row->papaducks,
            'mamaducks' => (int) $row->mamaducks,
        ];
    }

    /**
     * Enriched alert/status records for the DataTable JSON feed.
     * Business logic: appends computed attributes that are not stored in the DB.
     *
     * @return array{data: Collection, totalCount: int}
     */
    public function getJsonFeed(): array
    {
        $clusters = $this->repository->getAlertStatusOrderedDesc()
            ->map(function (ClusterData $cluster) {
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
     * Latest 4 alert/status records + total count for the dashboard feed panel.
     *
     * @return array{data: Collection, totalCount: int}
     */
    public function getTimeline(): array
    {
        return [
            'data'       => $this->repository->getLatestAlertStatus(4),
            'totalCount' => $this->repository->countAlertStatus(),
        ];
    }

    /**
     * Most-recent record per duck_id (alert/status topics only).
     */
    public function getLatestPerDuck(): Collection
    {
        return $this->repository->getLatestPerDuck();
    }

    /**
     * Business logic: from the given collection of duck records return the id
     * of the one most recently seen with GPS coordinates.
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
     * The last N messages per duck_id, shaped for the history API response.
     * Business logic: groups raw records, determines direction, and formats shape.
     */
    public function getRecentMessagesPerDuck(int $limit = 5): Collection
    {
        return $this->repository
            ->getAllByTopicsOrderedDesc(['status', 'alert', 'outbound', 'dcmd'])
            ->groupBy('duck_id')
            ->map(fn($rows) => $rows->take($limit)->map(fn(ClusterData $row) => [
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
     * Last known GPS coordinates per duck_id.
     * Business logic: parses LAT/LNG from the payload string.
     */
    public function lastKnownCoordsPerDuck(): Collection
    {
        return $this->repository
            ->getAllByTopicsOrderedDesc(['status', 'alert', 'outbound', 'dcmd'])
            ->filter(fn(ClusterData $d) => $d->map_url !== null)
            ->groupBy('duck_id')
            ->map(function (Collection $rows) {
                $first = $rows->first();
                $lat   = null;
                $lng   = null;

                if (preg_match(
                    '/LAT:(-?\d+(?:\.\d+)?),LNG:(-?\d+(?:\.\d+)?)/',
                    $first->payload ?? '',
                    $m
                )) {
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
     * Message counts for each of the past 12 hours (may overlap yesterday),
     * plus a trend comparing the current slot to the previous one.
     *
     * Business logic: all slot bucketing and trend calculation is done in PHP
     * so the repository stays driver-agnostic.
     *
     * @return array{labels: string[], data: int[], trend: array{direction: string, percentage: float, current_hour: int, previous_hour: int}}
     */
    public function getHourlyMessageCounts(): array
    {
        $windowStart = now()->subHours(11)->startOfHour();
        $windowEnd   = now()->endOfHour();

        $records = $this->repository->getAllInWindow($windowStart, $windowEnd);

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

        // Trend: slot 11 (current, possibly incomplete) vs slot 10 (last full hour).
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
     * Merged per-duck history payload used by /status/history.
     * Business logic: joins messages + coords, computes last-seen status.
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
