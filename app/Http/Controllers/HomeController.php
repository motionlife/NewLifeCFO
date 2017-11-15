<?php

namespace newlifecfo\Http\Controllers;

use Illuminate\Http\Request;
use newlifecfo\Models\Consultant;
use newlifecfo\Models\Arrangement;
use newlifecfo\Models\Client;
use newlifecfo\Models\Engagement;
use newlifecfo\Models\Templates\Position;
use newlifecfo\Models\Templates\Task;
use newlifecfo\Models\Templates\Taskgroup;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->get('verify')) {
            $out = [];
            foreach (Consultant::all() as $con) {
                $temp = $this->verify($con->fullname());
                if ($temp)
                    array_push($out, [$con->fullname() => $temp]);
            }

            return json_encode($out);
        }

        $consultants = Consultant::all();
        $output = [];
        foreach ($consultants as $consultant) {
            $totalpay = 0;
            $totalbillablehour = 0;
            $totalnonbillablehour = 0;
            foreach ($consultant->arrangements as $arrangement) {
                $billing_rate = $arrangement->billing_rate;
                $firm_share = $arrangement->firm_share;
                $hourlypay = $billing_rate * (1 - $firm_share);
                foreach ($arrangement->hourReports as $hour) {
                    $totalpay += $hour->billable_hours * $hourlypay;
                    $totalbillablehour += $hour->billable_hours;
                    $totalnonbillablehour += $hour->non_billable_hours;
                }
            }
            array_push($output, array('name' => $consultant->fullname(),
                'totalbh' => $totalbillablehour,
                'totalnbh' => $totalnonbillablehour,
                'totalpay' => $totalpay));
        }

        return view('test', ['consultants' => $consultants, 'result' => $output,'csv'=>$this->getTotalFromCSV()]);
    }

    private function getTotalFromCSV()
    {
        $out = [];
        if (($handle = fopen('C:\Users\HaoXiong\PhpstormProjects\NewLifeCFO\database\seeds\data\payroll\Payroll_Hours2017-11-13.csv', "r")) !== FALSE) {
            while (($line = fgetcsv($handle, 0, ",")) !== FALSE) {
                if (str_contains($line[0], 'Total')) {
                    array_push($out, $this->number($line[12]));
                }
            }
        }
        return $out;
    }

    private function verify($consultant)
    {
        $log = [];
        if (($handle = fopen('C:\Users\HaoXiong\PhpstormProjects\NewLifeCFO\database\seeds\data\payroll\Payroll_Hours2017-11-13.csv', "r")) !== FALSE) {
            $client_name = '';
            $con_name = '';
            $eng_name = '';
            $group = '';
            $con_id = 0;
            $client_id = 0;
            $eng = null;
            $arr = null;
            $in = false;
            fgetcsv($handle, 0, ",");//move the cursor one step because of header
            $row = 1;
            while (($line = fgetcsv($handle, 0, ",")) !== FALSE) {
                $row++;
                $skip = false;
                foreach ($line as $j => $entry) {
                    if ($j > 4) continue;
                    if (stripos($entry, 'Total')) {
                        if ($in && str_contains($entry, $consultant)) $in = false;
                        $skip = true;
                        break;
                    }
                }
                if ($skip) continue;

                if ($line[0]) {
                    $con_name = $line[0];
                    if ($con_name == $consultant) $in = true;
                } else if ($line[1] && $in) {
                    $client_name = $line[1];
                } else if ($line[2] && $in) {
                    $eng_name = $line[2];
                    $con_id = $this->get_consultant_id($con_name);
                    $client_id = $this->get_client_id($client_name);
                    $eng = Engagement::where(['client_id' => $client_id, 'name' => $eng_name])->first();
                    if (!$eng) {
                        array_push($log, ['w-row$' => $row,
                            'client' => $client_name . '(' . $client_id . ')',
                            'consultant' => $con_name . '(' . $con_id . ')']);
                    }
                } else if ($line[3] && $in) {
                    $arr = Arrangement::where(['engagement_id' => $eng->id,
                        'consultant_id' => $con_id,
                        'position_id' => $this->get_pos_id($line[3])])->first();
                    //what if we can't find the arrangement
                    if (!$arr) {
                        array_push($log, ['engagement' => $eng_name . '(' . $eng->id . ')',
                            'client' => $client_name . '(' . $client_id . ')',
                            'consultant' => $con_name . '(' . $con_id . ')']);
                    }
                } else if ($line[5] && $in) {
                    $bh = $this->number($line[7]);
                    $nbh = $this->number($line[8]);
                    if ($bh || $nbh)
                        if (!$arr->hourReports->where('report_date', date('Y-m-d', strtotime($line[5])))
                            ->where('billable_hours', $bh)
                            ->where('non_billable_hours', $nbh)
                            ->first()) {
                            array_push($log, ['r#' => $row, 'bh' => $line[7], 'nbh' => $line[8], 'task' => $line[6]]);
                        }
                }
            }
            fclose($handle);
        }
        return $log;
    }

    public
    function get_task_id($group, $desc)
    {
        $g = Taskgroup::firstOrCreate(['name' => $group]);
        return Task::firstOrCreate(['taskgroup_id' => $g->id], ['description' => $desc])->id;
    }

    public
    function get_client_id($name)
    {
        return Client::where('name', $name)->first()->id;
    }

    public
    function get_consultant_id($name)
    {
        return Consultant::all()->first(function ($con) use ($name) {
            return $con->fullname() == $name;
        })->id;
    }

    public
    function get_pos_id($pos)
    {
        return Position::firstOrCreate(['name' => $pos])->id;
    }

    public
    function number($str)
    {
        return (float)filter_var($str, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

}
