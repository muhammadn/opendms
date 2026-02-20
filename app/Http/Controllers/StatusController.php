<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClusterData;
use Illuminate\Support\Facades\DB;

class StatusController extends Controller
{
    public function index()
    {

$mamaducks = ClusterData::whereIn('id', function (
    $query
) {
    $query->selectRaw('max(id)')
        ->from('cluster_data') // Use the actual table name
        ->groupBy('duck_id'); // Replace with your column name (e.g., 'user_id')
})->get();

        return view('status', compact(['mamaducks']));
    }
}
