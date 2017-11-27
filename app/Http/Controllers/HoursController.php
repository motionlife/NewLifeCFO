<?php

namespace newlifecfo\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use newlifecfo\Models\Engagement;
use newlifecfo\Models\Hour;

class HoursController extends Controller
{
    /**
     * Create a new controller instance.
     * Hour Controller
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('verifiedConsultant');
    }


    public function index(Request $request)
    {
        $consultant = Auth::user()->consultant;
        $hours = $this->paginate($consultant->recentHourOrExpenseReports($request->get('start'),
            $request->get('end'), $request->get('eid'), true), 25);
        return view('hours', ['hours' => $hours,
            'clientIds' => $consultant->EngagementByClient()]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        //today's report
        $consultant = Auth::user()->consultant;
        $hours = $consultant->justCreatedHourReports(Carbon::today()->startOfDay(), Carbon::today()->endOfDay());

        if ($request->ajax()) {
            if ($request->get('fetch') == 'position') {
                return $consultant->getArrInfoByEid($request->get('eid'));
            }
        }

        return view('new-hour', [
            'hours' => $hours,
            'clientIds' => $consultant->EngagementByClient()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $consultant = Auth::user()->consultant;
        //same reported hours
        $feedback = [];
        $eid = $request->get('eid');
        $pid = $request->get('pid');
        if ($request->ajax()) {
            //business logic validation is important
            //1. check the if the reported engagement is his valid engagement
            $eng = Engagement::find($eid);
            if (!$eng) {
                $feedback['code'] = 0;
                $feedback['message'] = 'Engagement not found.';
            } else if ($eng->state() == 'closed') {
                $feedback['code'] = 1;
                $feedback['message'] = 'Non-active Engagement!!!, has it been closed or still pending? Please contact supervisor.';
            } else {
                $arr = $consultant->getArrangementByEidPid($eid, $pid);
                if (!$arr) {
                    $feedback['code'] = 2;
                    $feedback['message'] = 'You are not in this engagement';
                } else {
                    $hour = (new Hour(['arrangement_id' => $arr->id]))->fill($request->except(['eid', 'pid']));
                    if ($hour->save()) {
                        $feedback['code'] = 7;
                        $feedback['message'] = 'success';
                        $feedback['data'] = ['billable_hours' => number_format($hour->billable_hours, 1),
                            'created_at' => Carbon::parse($hour->created_at)->diffForHumans(),
                            'ename' => $eng->name, 'cname' => $eng->client->name, 'hid' => $hour->id];
                    } else {
                        $feedback['code'] = 3;
                        $feedback['message'] = 'unknown error happened while saving';
                    }
                }
            }
            return json_encode($feedback);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        $consultant = Auth::user()->consultant;
        if ($request->ajax()) {
            $hour = Hour::find($id);
            //must check if this hour record belong to the consultant!!!
            if ($hour && $hour->arrangement->consultant_id == $consultant->id) {
                return json_encode($hour);
            }
            //else illegal request!
        }
        return view('wage');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, Request $request)
    {
        //
        $user = Auth::user();
        if ($request->ajax()) {
            $hour = Hour::find($id);
            //must check if this hour record belong to the consultant!!!
            if ($user->can('view',$hour)) {
                $arr = $hour->arrangement;
                $hour->report_date = Carbon::parse($hour->report_date)->format('m/d/Y');
                return json_encode(['ename' => $arr->engagement->name, 'task_id' => $hour->task_id, 'report_date' => $hour->report_date,
                    'billable_hours' => number_format($hour->billable_hours, 1), 'non_billable_hours' => number_format($hour->non_billable_hours, 1),
                    'description' => $hour->description, 'review_state' => $hour->review_state, 'position' => $arr->position->name,
                    'billing_rate'=>$arr->billing_rate,'firm_share'=>$arr->firm_share
                ]);
            }
            //else illegal request!
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        //same reported hours
        $feedback = [];
        if ($request->ajax()) {
            //business logic validation is important
            //1. check the if the reported engagement is his valid engagement
            $hour = Hour::find($id);
            if ($user->can('update', $hour)) {
                if ($hour->update($request->all())) {
                    $feedback['code'] = 7;
                    $feedback['message'] = 'Record Update Success';
                    $feedback['record'] = ['ename' => str_limit($hour->arrangement->engagement->name, 19),
                        'cname' => str_limit($hour->arrangement->engagement->client->name, 19),
                        'report_date' => $hour->report_date,
                        'task' => str_limit($hour->task->getDesc(), 23),
                        'billable_hours' => number_format($hour->billable_hours, 1),
                        'id' => $hour->id,
                        'description' => $hour->description,
                        'status' => $hour->getStatus()
                    ];
                } else {
                    $feedback['code'] = 4;
                    $feedback['message'] = 'unknown error during updating';
                }

            } else {
                $feedback['code'] = 1;
                $feedback['message'] = 'Cannot be updated now, no authorization';
            }
            return json_encode($feedback);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {
        //
        $user = Auth::user();
        if ($request->ajax()) {
            $hour = Hour::find($id);
            //must check if this hour record belong to the consultant!!!
            if ($user->can('delete', $hour)) {
                if ($hour->delete()) return json_encode(['message' => 'succeed']);
            }
            return json_encode(['message' => 'delete_failed, no authorization']);
        }
    }
}
