@extends('layouts.app')
@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row" style="margin-bottom: -2.4em;">
                <div class="col-md-3">
                    <h3 class="page-title">Working Time Report</h3>
                </div>
                <div class="col-md-9">
                    <a href="javascript:void(0)" class="btn btn-default"
                       id="day-week">Daily&nbsp;<i class="fa fa-arrows-h" aria-hidden="true">&nbsp;Weekly</i></a>
                </div>
            </div>
            <hr>
            <div class="row daily-weekly-view" style="display: none">
                <div class="col-md-8">
                    <div class="panel panel-headline">
                        <div class="panel-heading">
                            <h3 class="panel-title">Consultant: {{Auth::user()->fullname()}}</h3>
                        </div>
                        <form method="POST" id="hour-form" action="/hour">
                            <div class="panel-body">
                                @component('components.hour-form',['clientIds'=>$clientIds])
                                @endcomponent
                            </div>
                            <div class="panel-footer">
                                <button class="btn btn-primary" id="report-button" type="submit"
                                        data-loading-text="<i class='fa fa-spinner fa-spin'></i> Processing">Submit
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="panel panel-scrolling">
                        <div class="panel-heading">
                            <h3 class="panel-title">Today's Reports</h3>
                            <p class="panel-subtitle">{{$hours->count()?$hours->count()." reports":"No report today"}}</p>
                            <div class="right">
                                <button type="button" class="btn-toggle-collapse"><i class="lnr lnr-chevron-up"></i>
                                </button>
                                <button type="button" class="btn-remove"><i class="lnr lnr-cross"></i></button>
                            </div>
                        </div>
                        <div class="panel-body">
                            <ul class="list-unstyled activity-list" id="today-board">
                                @foreach($hours as $hour)
                                    <li>
                                        <?php $eng = $hour->arrangement->engagement ?>
                                        <div class="pull-left avatar">
                                            <a href="javascript:void(0);"><strong>{{number_format($hour->billable_hours,1)}}</strong></a>
                                        </div>
                                        <p> billable hours reported for the work of
                                            <strong>{{$eng->name}}</strong> ({{$eng->client->name}})<span
                                                    class="timestamp">{{\Carbon\Carbon::parse($hour->created_at)->diffForHumans()}}
                                                <a href="javascript:deleteTodaysReport({{$hour->id}});"><i
                                                            class="fa fa-times pull-right"></i></a></span>

                                        </p>
                                    </li>
                                @endforeach
                            </ul>
                            <a type="button" href="/hour" class="btn btn-primary btn-bottom center-block">See All</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row daily-weekly-view">
                <div class="panel panel-headline">
                    <div class="input-group">
                        <span class="input-group-btn"><button class="btn btn-primary" type="button"
                                                              id="week-picker"><i
                                        class="fa fa-calendar-minus-o">&nbsp;Select Week</i></button></span>
                        <div class="form-control" id="week-info" style="border: dashed #5fdbff 0.1em;">
                            Week
                            <span class="badge bg-success">{{\Carbon\Carbon::now()->weekOfYear}}</span>&nbsp;<strong>{{\Carbon\Carbon::now()->startOfWeek()->subDay()->format('m/d/Y').' - '.\Carbon\Carbon::now()->endOfWeek()->subDay()->format('m/d/Y')}}</strong>
                        </div>
                    </div>
                    <div class="panel-body" id="hours-roll">
                        <table class="table table-responsive">
                            <thead>
                            <tr>
                                <th>Engagement / Task</th>
                                @for($i=0;$i<7;$i++)
                                    @php $date = \Carbon\Carbon::now()->startOfWeek()->subDay(); @endphp
                                    <th>{{substr($date->addDay($i)->format('l'),0,3)}}<span>{{$date->format('M d')}}</span></th>
                                @endfor
                                    <th></th>
                            </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                    <div class="panel-footer">
                        <a href="javascript:void(0)" id="add-row-btn" class="btn btn-info"><i class="fa fa-plus" aria-hidden="true">&nbsp;Add
                                Row</i></a>
                        <i>&nbsp;</i>
                        <a href="javascript:void(0)" class="btn btn-primary">Submit</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="row-template" style="display:none;">
        <tr>
            <th scope="row"></th>
            <td><input class='form-control input-sm' type='text' size="4"/></td>
            <td><input class='form-control input-sm' type='text' size="4"/></td>
            <td><input class='form-control input-sm' type='text' size="4"/></td>
            <td><input class='form-control input-sm' type='text' size="4"/></td>
            <td><input class='form-control input-sm' type='text' size="4"/></td>
            <td><input class='form-control input-sm' type='text' size="4"/></td>
            <td><input class='form-control input-sm' type='text' size="4"/></td>
            <td><a href="javascript:void(0)"><i class="fa fa-times" aria-hidden="true"></i></a></td>
        </tr>
    </div>
    <div class="modal fade" id="engtaskModal" tabindex="-1" role="dialog" aria-labelledby="engtaskModalLabel" aria-hidden="true">
        <div class="modal-dialog  modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title" id="engtaskModalLabel">Select engagement and task</h3>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="input-group form-group-sm">
                        <span class="input-group-addon"><i class="fa fa-users"></i>&nbsp;Engagement:</span>
                        <select id="client-engagement-addrow" class="selectpicker show-tick form-control form-control-sm" data-width="100%" data-dropup-auto="false"
                                data-live-search="true" name="eid" title="Select the engagements" required>
                            @if(isset($clientIds))
                                @foreach($clientIds as $cid=>$engagements)
                                    <optgroup label="{{newlifecfo\Models\Client::find($cid)->name }}">
                                        @foreach($engagements as $eng)
                                            <option value="{{$eng[0]}}">{{$eng[1]}}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            @endif
                        </select>
                        <span class="input-group-addon"><i class="fa fa-cogs" aria-hidden="true"></i>&nbsp;Position:</span>
                        <select class="selectpicker form-control form-control-sm" id="position-addrow" name="pid" data-width="100%"
                                required></select>
                    </div>
                    <br>
                    <div class="input-group form-group-sm">
                        <span class="input-group-addon"><i class="fa fa-tasks"></i>&nbsp;Task:</span>
                        <select id="task-id-addrow" class="selectpicker show-sub-text form-control form-control-sm" data-live-search="true"
                                data-width="100%" name="task_id" data-dropup-auto="false"
                                title="Select your task" required>
                            @foreach(\newlifecfo\Models\Templates\Taskgroup::all() as $tgroup)
                                <?php $gname = $tgroup->name?>
                                @foreach($tgroup->tasks as $task)
                                    <option value="{{$task->id}}"
                                            data-content="{{$gname.' <strong>'.$task->description.'</strong>'}}"></option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Add</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('my-js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.19.3/moment.min.js"></script>
    <script>
        $(function () {
            toastr.options = {
                "positionClass": "toast-bottom-full-width",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "4000",
                "extendedTimeOut": "900"
            };
            $('#client-engagement,#client-engagement-addrow').on('change', function () {
                var select = $(this);
                $.ajax({
                    type: "get",
                    url: "/hour/create",
                    data: {eid: select.selectpicker('val'), fetch: 'position'},
                    success: function (data) {
                        var pos = $('#position,#position-addrow').empty();
                        $(data).each(function (i, arr) {
                            pos.append("<option value=" + arr.position.id + " data-br=" + arr.br + " data-fs=" + arr.fs + ">" + arr.position.name + "</option>");
                        });
                        pos.selectpicker('refresh');
                    }
                });
            });
            $('#position').on('change', function () {
                $('#billable-hours').trigger("change");
            });
            $('#billable-hours').on('change', function () {
                var opt = $('#position').find(':selected');
                var br = opt.attr('data-br');
                var fs = opt.attr('data-fs');
                var bh = $(this).val();
                $('#income-estimate').val(bh + 'h  x  $' + br + '/hr  x  ' + (1 - fs) * 100 + '% = $' + bh * br * (1 - fs));
            });
            $('#hour-form').on('submit', function (e) {
                var eid = $('#client-engagement').selectpicker('val');
                var token = "{{ csrf_token() }}";
                $.ajax({
                    type: "POST",
                    url: "/hour",
                    data: {
                        _token: token,
                        eid: eid ? eid : '',
                        pid: $('#position').selectpicker('val'),
                        report_date: $('#report-date').val(),
                        task_id: $('#task-id').selectpicker('val'),
                        billable_hours: $('#billable-hours').val(),
                        non_billable_hours: $('#non-billable-hours').val(),
                        description: $('#description').val()
                    },
                    dataType: 'json',
                    success: function (feedback) {
                        if (feedback.code == 7) {
                            toastr.success('Success! Report has been saved!');
                            $('#billable-hours').val('');
                            $('#non-billable-hours').val('');
                            $('<li><div class="pull-left avatar"><a href="javascript:void(0);"><strong>'
                                + feedback.data.billable_hours + '</strong></a></div><p>billable hours reported for the work of <strong>'
                                + feedback.data.ename + '</strong>(' + feedback.data.cname + ')<span class="timestamp">'
                                + feedback.data.created_at + '<a href="javascript:deleteTodaysReport('
                                + feedback.data.hid + ');"><i class="fa fa-times pull-right"></i></a></span></p></li>')
                                .prependTo('#today-board').hide().fadeIn(1500);
                        } else {
                            toastr.error('Error! Saving record failed, code: ' + feedback.code +
                                ', message: ' + feedback.message);
                        }
                    },
                    error: function (feedback) {
                        toastr.error('Oh Noooooooo..' + feedback.message);
                    },
                    beforeSend: function () {
                        $("#report-button").button('loading');
                    },
                    complete: function () {
                        $("#report-button").button('reset');
                    }
                });
                e.preventDefault();
            });
            $('#report-date').datepicker({
                format: 'mm/dd/yyyy',
                todayHighlight: true,
                autoclose: true,
                orientation: 'bottom'
            }).datepicker('setDate', new Date());

            $('#day-week').on('click', function () {
                $('.daily-weekly-view').slideToggle();
            });
            $('#hours-roll').slimScroll({
                height: '220px'
            });
            $('#week-picker').datepicker({
                todayHighlight: true,
                autoclose: true,
                calendarWeeks: true
            }).datepicker('setDate', new Date()).on('show', function () {
                $('.datepicker tr td.cw').parent().hover(function (e) {
                    $(this).css("background-color", e.type === "mouseenter" ? "#47cef7" : "transparent");
                });
            }).on('changeDate', function (e) {
                var weekinfo = $('#week-info');
                var md = moment(e.date);
                var span = $('#hours-roll').find('tr span');
                var firstDate = md.day(0).format("MM/DD/YYYY");
                var lastDate = md.day(6).format("MM/DD/YYYY");
                weekinfo.find('strong').empty().text(firstDate + " - " + lastDate);
                weekinfo.find('span').empty().text(md.week());
                for (var i = 0; i < 7; i++) {
                    span.eq(i).empty().text(md.day(i).format("MMM DD"));
                }
            });
            $('#add-row-btn').on('click',function () {
                $('#engtaskModal').modal('toggle');
            });
        });

        function deleteTodaysReport(hid) {
            var li = $('a[href*="deleteTodaysReport(' + hid + ')"]').parent().parent().parent();
            swal({
                    title: "Are you sure?",
                    text: "This record shall be deleted, please make sure!",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Yes, delete it!",
                    closeOnConfirm: false
                },
                function () {
                    $.post({
                        url: "/hour/" + hid,
                        data: {_token: "{{csrf_token()}}", _method: 'delete'},
                        success: function (data) {
                            if (data.message == 'succeed') {
                                li.fadeOut(1000, function () {
                                    $(this).remove();
                                });
                                swal("Deleted!", "The record has been deleted.", "success");
                            } else {
                                toastr.warning('Failed! Fail to delete the record!' + data.message);
                            }
                        },
                        dataType: 'json'
                    });
                });
        }
    </script>
@endsection

@section('special-css')
    <style>
        #hours-roll th span{
            color: #4bb3ff;
            font-weight: lighter;
            margin-left: .7em;
        }
    </style>
@endsection
