<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CruiseSchedule;
use Illuminate\Http\Request;

class CruiseScheduleController extends Controller
{
    public function index()
    {
        $schedules = CruiseSchedule::with('cruise')->get();
        return response()->json($schedules);
    }
}
