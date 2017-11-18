<?php

namespace newlifecfo\Models;

use Carbon\Carbon;
use function foo\func;
use Illuminate\Database\Eloquent\Model;
use newlifecfo\Models\Templates\Position;

class Arrangement extends Model
{
    protected $guarded = [];

    //get it's parent engagement
    public function engagement()
    {
        return $this->belongsTo(Engagement::class);
    }

    //get the arranged consultant
    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    //get the arranged position
    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    //all the hour reports for this arrangement
    public function hours()
    {
        return $this->hasMany(Hour::class);
    }

    //all the expense reports for this arrangement
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function reportedExpenses($start = '1970-01-01', $end = '2038-01-19')
    {
        return $this->expenses()->whereBetween('report_date', [$start, $end])->get()
            ->sum(function ($exp) {
                return $exp->total();
            });
    }

    public function monthlyExpenses($start = '1970-01-01', $end = '2038-01-19')
    {
        return $this->expenses()->whereBetween('report_date', [$start, $end])->get()
            ->mapToGroups(function ($exp) {
                return [Carbon::parse($exp->report_date)->format('y-M') => $exp->total()];
            })->transform(function ($month) {
                return $month->sum();
            });
    }

    public function reportedHours($start = '1970-01-01', $end = '2038-01-19', $billable = true)
    {
        return $this->hours()->whereBetween('report_date', [$start, $end])->sum($billable ? 'billable_hours' : 'non_billable_hours');
    }

    public function monthlyHoursAndIncome($start = '1970-01-01', $end = '2038-01-19', $th = true, $both = true)
    {
        $net_rate = (1 - $this->firm_share) * $this->billing_rate;
        return $this->hours()->whereBetween('report_date', [$start, $end])->get()
            ->mapToGroups(function ($hour) use ($net_rate, $th) {
                return [Carbon::parse($hour->report_date)->format('y-M') =>
                    [$th ? $hour->billable_hours + $hour->non_billable_hours : $hour->billable_hours,
                        $hour->billable_hours * $net_rate]];
            })->transform(function ($month) use ($both) {
                return $both ? [$month->sum(0), $month->sum(1)] : $month->sum(1);
//                return $group->reduce(function ($carry, $item) {
//                    return [$carry[0] + $item[0], $carry[1] + $item[1]];
//                });
            });
    }

    public function hoursBillToClient($start = '1970-01-01', $end = '2038-01-19')
    {
        return $this->billing_rate ? $this->reportedHours($start, $end) * $this->billing_rate : 0;
    }

    public function hoursIncomeForConsultant($start = '1970-01-01', $end = '2038-01-19')
    {
        return $this->hoursBillToClient($start, $end) * (1 - $this->firm_share);
    }

}
