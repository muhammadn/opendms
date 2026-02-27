<?php

namespace App\Repositories;

use App\Models\ClusterData;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ClusterDataRepository
{
    /**
     * All records, newest first.
     */
    public function getAllOrderedByLatest(): Collection
    {
        return ClusterData::orderBy('id', 'desc')->get();
    }

    /**
     * Single-query aggregate: total messages + distinct PapaDuck (type 1)
     * and MamaDuck (type 2) counts.
     * Uses standard SQL CASE expressions — works on SQLite, MySQL, PostgreSQL.
     */
    public function getStats(): ClusterData
    {
        return ClusterData::selectRaw("
            COUNT(*) as total,
            COUNT(DISTINCT CASE WHEN duck_type = 1 THEN duck_id END) as papaducks,
            COUNT(DISTINCT CASE WHEN duck_type = 2 THEN duck_id END) as mamaducks
        ")->first();
    }

    /**
     * All alert/status records, newest first.
     */
    public function getAlertStatusOrderedDesc(): Collection
    {
        return ClusterData::whereIn('topic', ['alert', 'status'])
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Latest N alert/status records.
     */
    public function getLatestAlertStatus(int $limit): Collection
    {
        return ClusterData::whereIn('topic', ['alert', 'status'])
            ->orderBy('id', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Total count of alert/status records.
     */
    public function countAlertStatus(): int
    {
        return ClusterData::whereIn('topic', ['alert', 'status'])->count();
    }

    /**
     * One record per duck_id — the row with the highest id among alert/status
     * topics (excludes MSG_READ receipts and outbound operator messages).
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
     * All records matching the given topics, newest first.
     */
    public function getAllByTopicsOrderedDesc(array $topics): Collection
    {
        return ClusterData::whereIn('topic', $topics)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * All records whose created_at falls within the given window.
     * Only the specified columns are fetched (defaults to created_at only
     * which is all that is needed for the hourly chart).
     *
     * @param  string[]  $columns
     */
    public function getAllInWindow(
        CarbonInterface $start,
        CarbonInterface $end,
        array $columns = ['created_at']
    ): Collection {
        return ClusterData::where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->get($columns);
    }
}
