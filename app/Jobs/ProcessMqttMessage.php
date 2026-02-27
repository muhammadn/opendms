<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\ClusterData;
use App\Jobs\SyncRecordToCloud;
use Illuminate\Support\Facades\Log;

class ProcessMqttMessage implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     *
     * Deployment-aware write strategy:
     *   - Production / standalone offline: `synced` left as null (not applicable).
     *     SyncRecordToCloud is never dispatched.
     *   - Hybrid (CENTRAL_DMS_URL configured): `synced` set to false (pending).
     *     SyncRecordToCloud is dispatched and will retry until delivery is confirmed.
     */
    public function handle(): void
    {
	    Log::info("Processing ClusterDuck Data...");
	    $data = json_decode($this->payload, true);

	    if (isset($data["payload"]["path"])) {
  	      $path = implode(",", $data["payload"]["path"]);
            } else {
              $path = null;
	    }

        $isHybrid = !empty(config('services.central_dms.url'));

	    $record = ClusterData::create([
	      'duck_id'    => $data["payload"]["DeviceID"],
              'topic'      => $data["eventType"],
              'message_id' => $data["MessageID"],
              'payload'    => $data["payload"]["Message"] ?? null,
	      'path'       => $path,
              'hops'       => $data["payload"]["hops"],
              'duck_type'  => $data["payload"]["duckType"],
              // null = not applicable; false = pending sync (hybrid mode only)
              'synced'     => $isHybrid ? false : null,
	    ]);

        // Only enqueue the outbox sync job in hybrid mode.
        if ($isHybrid) {
            SyncRecordToCloud::dispatch($record->id)
                ->onQueue('sync')
                ->delay(now()->addSeconds(5));

            Log::info("ProcessMqttMessage: queued sync for record {$record->id}");
        }
    }
}
