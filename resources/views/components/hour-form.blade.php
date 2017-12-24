<div class="input-group">
    <span class="input-group-addon"><i class="fa fa-users"></i>&nbsp; Client and Engagement:</span>
    @component('components.engagement_selector',['dom_id'=>"client-engagement",'clientIds'=>$clientIds])
    @endcomponent
</div>
<br>
<div class="input-group">
    <span class="input-group-addon"><i class="fa fa-cogs" aria-hidden="true"></i>&nbsp;Job Position:</span>
    <select class="selectpicker" id="position" name="pid" data-width="auto"
            required></select>
    <span class="input-group-addon"><i class="fa fa-calendar"></i>&nbsp; Report Date</span>
    <input class="date-picker form-control" id="report-date" placeholder="mm/dd/yyyy"
           name="report_date" type="text" required/>

</div>
<br>
<div class="input-group">
    @component('components.task_selector',['dom_id'=>'task-id'])
    @endcomponent
</div>
<br>
<div class="input-group">
                                <span class="input-group-addon"><i
                                            class="fa fa-usd"></i>&nbsp;<strong>Billable Hours:</strong></span>
    <input class="form-control" id="billable-hours" name="billable_hours" type="number"
           placeholder="numbers only"
           step="0.1" min="0"
           max="24" required>

    <span class="input-group-addon"><i
                class="fa fa-hourglass-start"></i>&nbsp;Non-billable Hours:</span>
    <input class="form-control" id="non-billable-hours" name="non_billable_hours"
           type="number" step="0.1" min="0"
           placeholder="numbers only">

</div>
<br>
<div class="input-group">
                                <span class="input-group-addon"><i
                                            class="fa fa-money"></i>&nbsp;Estimated Income:</span>
    <input type="text" id="income-estimate" disabled value="" class="form-control" data-br="" data-fs="">
</div>
<br>
<textarea id="description" class="form-control notebook" name="description" placeholder="description"
          rows="3"></textarea>
<br>
@if(isset($admin))
    @if($admin)
        <div style=" border-style: dotted;color:#33c0ff; padding: .3em .3em .3em .3em;">
            <div class="row">
                <div class="col-md-6">
                    <label class="fancy-radio">
                        <input name="endorse-or-not" value="1" type="radio">
                        <span><i></i><strong>Endorse Report</strong></span>
                    </label>
                </div>
                <div class="col-md-6">
                    <label class="fancy-radio">
                        <input name="endorse-or-not" value="2"
                               type="radio">
                        <span><i></i><strong>Recommend Re-submit</strong></span>
                    </label>
                </div>
            </div>
            <input class="form-control" name="feedback" id="hour-feedback"
                   placeholder="feedback" type="text">
        </div>
    @else
        <div id="feedback-info" style="margin-bottom: -1em"></div>
    @endif
@endif