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
}
