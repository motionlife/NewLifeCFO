<?php

namespace newlifecfo\Models;

use Carbon\Carbon;
use function foo\func;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use newlifecfo\Models\Templates\Position;

class Arrangement extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    protected $guarded = [];

    //get it's parent engagement
    public function engagement()
    {
        return $this->belongsTo(Engagement::class)->withDefault(function ($engagement) {
            $engagement->name = "Deleted";
        });//should we add with default?
    }

    //get the arranged consultant
    public function consultant()
    {
        return $this->belongsTo(Consultant::class)->withDefault(function ($consultant) {
            $consultant->first_name = "Deleted";
            $consultant->last_name = "Deleted";
        });
    }

    //get the arranged position
    public function position()
    {
        return $this->belongsTo(Position::class)->withDefault();
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

    public function reportedExpenses($start = null, $end = null, $state = null)
    {
        return (isset($state) ? $this->expenses()->where('review_state', $state) : $this->expenses())
            ->whereBetween('report_date', [$start ?: '1970-01-01', $end ?: '2038-01-19'])->get()
            ->sum(function ($exp) {
                return $exp->total();
            });
    }

    public function monthlyExpenses($start = '1970-01-01', $end = '2038-01-19', $state = null)
    {
        return (isset($state) ? $this->expenses()->where('review_state', $state) : $this->expenses())
            ->whereBetween('report_date', [$start, $end])->get()
            ->mapToGroups(function ($exp) {
                return [Carbon::parse($exp->report_date)->format('y-M') => $exp->total()];
            })->transform(function ($month) {
                return $month->sum();
            });
    }

    public function dailyHoursAndIncome($start = '1970-01-01', $end = '2038-01-19', $state = null)
    {
        $eid = $this->engagement->id;
        return (isset($state) ? $this->hours()->where('review_state', $state) : $this->hours())
            ->whereBetween('report_date', [$start, $end])->get()
            ->mapToGroups(function ($hour) {
                return [Carbon::parse($hour->report_date)->format('M d') =>
                    [$hour->billable_hours, $hour->non_billable_hours, $hour->earned()]];
            })->transform(function ($day) use ($eid) {
                return [$day->sum(0), $day->sum(1), $day->sum(2), $eid];
            });
    }

    public function monthlyHoursAndIncome($start = '1970-01-01', $end = '2038-01-19', $th = true)
    {
        return $this->hours()->whereBetween('report_date', [$start, $end])->where('review_state',1)->get()
            ->mapToGroups(function ($hour) use ($th) {
                return [Carbon::parse($hour->report_date)->format('y-M') =>
                    [$th ? $hour->billable_hours + $hour->non_billable_hours : $hour->billable_hours, $hour->earned()]];
            })->transform(function ($month) {
                return [$month->sum(0), $month->sum(1)];
            });
    }

    public function hoursIncomeForConsultant($start = null, $end = null, $review_state = null, &$hrs = null)
    {
        $total = 0;
        $hours = $this->hours()->whereBetween('report_date', [$start ?: '1970-01-01', $end ?: '2038-01-19'])
            ->where('review_state', isset($review_state) ? '=' : '<>', isset($review_state) ? $review_state : 7)->get();
        foreach ($hours as $hour) {
            $total += $hour->earned();
            if (isset($hrs)) {
                $hrs[0] += $hour->billable_hours;
                $hrs[1] += $hour->non_billable_hours;
            }
        }
        return $total;
    }


}
