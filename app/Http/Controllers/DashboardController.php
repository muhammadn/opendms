<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClusterData;
use Illuminate\Support\Number;

class DashboardController extends Controller
{
    public function index()
    {
        $clusters = ClusterData::orderBy('id', 'desc')->get(); 
	$count = $clusters->count();
	$mamaducks = ClusterData::where(['duck_type' => 2])
		->distinct('duck_id')
		->count();

	return view('dashboard', compact(['clusters','mamaducks','count']));
    }

    public function json()
    {
        $clusters = ClusterData::orderBy('id', 'desc')->get();
	$count = $clusters->count();

	$data = ["data" => $clusters, "totalCount" => $count];
	return response()->json($data, 200);
    }

    public function timeline()
    {
        $clusters = ClusterData::orderBy('id','desc')->take(4)->get();
	$count = ClusterData::count();
	return response()->json(["data" => $clusters, "totalCount" => $count], 200);
    }
}
