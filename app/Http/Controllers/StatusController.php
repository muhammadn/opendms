<?php

namespace App\Http\Controllers;

use App\Models\ClusterData;
use App\Services\ClusterDataService;
use App\Services\MqttService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    public function __construct(
        private ClusterDataService $clusterDataService,
        private MqttService $mqttService,
    ) {}

    public function index()
    {
        $mamaducks      = $this->clusterDataService->getLatestPerDuck();
        $latestCoordsId = $this->clusterDataService->latestWithCoordsId($mamaducks);

        return view('status', compact('mamaducks', 'latestCoordsId'));
    }

    public function history(): JsonResponse
    {
        return response()->json($this->clusterDataService->buildHistoryResponse());
    }

    public function message(Request $request): JsonResponse
    {
        $message = $request->input('message');
        $duckId  = $request->input('duck_id');

        $this->mqttService->sendCommand(
            message: $message,
            target:  $duckId,
        );

        // Persist the operator-sent message so it appears in history
        // and can be matched against MSG_READ receipts from the duck.
        ClusterData::create([
            'duck_id'    => $duckId,
            'topic'      => 'outbound',
            'message_id' => uniqid('OUT-'),
            'payload'    => 'MSG,TEXT:' . $message,
            'hops'       => 0,
            'duck_type'  => 'operator',
        ]);

        return response()->json(['message' => 'Form submitted successfully!']);
    }
}
