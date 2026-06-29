{include file="sections/header.tpl"}
<div class="row">
    {if $stats.total > 0}
        {assign var="pctSuccess" value=($stats.completed / $stats.total * 100)|round}
    {else}
        {assign var="pctSuccess" value=0}
    {/if}
    <div class="col-lg-2 col-sm-2 col-xs-4" style="margin-bottom:10px">
        <div class="small-box bg-aqua" style="margin-bottom:0">
            <div class="inner" style="text-align:center;padding:8px 4px"><h5 style="margin:0;font-weight:700">{$stats.total}</h5></div>
            <div class="small-box-footer" style="font-size:10px">{Lang::T('Total')}</div>
        </div>
    </div>
    <div class="col-lg-2 col-sm-2 col-xs-4" style="margin-bottom:10px">
        <div class="small-box bg-yellow" style="margin-bottom:0">
            <div class="inner" style="text-align:center;padding:8px 4px"><h5 style="margin:0;font-weight:700">{$stats.pending}</h5></div>
            <div class="small-box-footer" style="font-size:10px">{Lang::T('Pending')}</div>
        </div>
    </div>
    <div class="col-lg-2 col-sm-2 col-xs-4" style="margin-bottom:10px">
        <div class="small-box bg-orange" style="margin-bottom:0">
            <div class="inner" style="text-align:center;padding:8px 4px"><h5 style="margin:0;font-weight:700">{$stats.in_progress}</h5></div>
            <div class="small-box-footer" style="font-size:10px">{Lang::T('In Progress')}</div>
        </div>
    </div>
    <div class="col-lg-2 col-sm-2 col-xs-4" style="margin-bottom:10px">
        <div class="small-box bg-green" style="margin-bottom:0">
            <div class="inner" style="text-align:center;padding:8px 4px"><h5 style="margin:0;font-weight:700">{$stats.completed}</h5></div>
            <div class="small-box-footer" style="font-size:10px">{Lang::T('Completed')}</div>
        </div>
    </div>
    <div class="col-lg-2 col-sm-2 col-xs-4" style="margin-bottom:10px">
        <div class="small-box bg-green" style="margin-bottom:0">
            <div class="inner" style="text-align:center;padding:8px 4px"><h5 style="margin:0;font-weight:700">{$pctSuccess}%</h5></div>
            <div class="small-box-footer" style="font-size:10px">{Lang::T('Success Rate')}</div>
        </div>
    </div>
    <div class="col-lg-2 col-sm-2 col-xs-4" style="margin-bottom:10px">
        <div class="small-box bg-purple" style="margin-bottom:0">
            <div class="inner" style="text-align:center;padding:8px 4px"><h5 style="margin:0;font-size:13px;font-weight:700">{$topStaff|default:'-'}</h5></div>
            <div class="small-box-footer" style="font-size:10px">{Lang::T('Top Staff')} ({$topStaffCount})</div>
        </div>
    </div>
</div>
{if $staffStats}
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>{Lang::T('Staff')}</th><th>{Lang::T('Completed')}</th></tr></thead>
                    <tbody>
                    {foreach $staffStats as $s}
                        <tr><td>{$s.fullname|escape:'html'}</td><td>{$s.cnt}</td></tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
{/if}
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="btn-group" style="margin-bottom:12px">
                    <a href="{$_url}plugin/support_ticket" class="btn btn-sm {if !$statusFilter and $view neq 'mine'}btn-primary{else}btn-default{/if}">{Lang::T('All')}</a>
                    <a href="{$_url}plugin/support_ticket&view=mine" class="btn btn-sm {if $view eq 'mine'}btn-primary{else}btn-default{/if}">{Lang::T('My Tickets')}</a>
                    <a href="{$_url}plugin/support_ticket&status=pending" class="btn btn-sm {if $statusFilter eq 'pending'}btn-primary{else}btn-default{/if}">{Lang::T('Pending')}</a>
                    <a href="{$_url}plugin/support_ticket&status=in_progress" class="btn btn-sm {if $statusFilter eq 'in_progress'}btn-primary{else}btn-default{/if}">{Lang::T('In Progress')}</a>
                    <a href="{$_url}plugin/support_ticket&status=completed" class="btn btn-sm {if $statusFilter eq 'completed'}btn-primary{else}btn-default{/if}">{Lang::T('Completed')}</a>
                </div>
                <div style="margin-bottom:12px;text-align:right">
                    <a href="{$_url}plugin/support_ticket&action=add" class="btn btn-sm btn-success">{Lang::T('New Ticket')}</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{Lang::T('Customer')}</th>
                                <th>{Lang::T('Phone')}</th>
                                <th>{Lang::T('Type')}</th>
                                <th>{Lang::T('Description')}</th>
                                <th>{Lang::T('Status')}</th>
                                <th>{Lang::T('Staff')}</th>
                                <th>{Lang::T('Resolution')}</th>
                                <th>{Lang::T('Date')}</th>
                                <th>{Lang::T('Action')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $tickets as $t}
                            <tr>
                                <td>{$t.id}</td>
                                <td>{$t.customer_name|escape:'html'}<br><small>{$t.customer_username|escape:'html'}</small></td>
                                <td>{$t.customer_phone|escape:'html'}</td>
                                <td><span class="label label-info">{$typeLabels[$t.ticket_type]|default:$t.ticket_type}</span></td>
                                <td>{$t.description|escape:'html'|truncate:80}</td>
                                <td>
                                    {if $t.status eq 'completed'}<span class="label label-success">{$statusLabels[$t.status]}</span>
                                    {elseif $t.status eq 'in_progress'}<span class="label label-warning">{$statusLabels[$t.status]}</span>
                                    {elseif $t.status eq 'cancelled'}<span class="label label-default">{$statusLabels[$t.status]}</span>
                                    {else}<span class="label label-danger">{$statusLabels[$t.status]}</span>
                                    {/if}
                                </td>
                                <td>{$t.staff_name|escape:'html'}</td>
                                <td>
                                    {if $t.resolution_type}
                                        {$resolutionLabels[$t.resolution_type]|default:$t.resolution_type}
                                        {if $t.resolution_notes}<br><small>{$t.resolution_notes|escape:'html'|truncate:40}</small>{/if}
                                    {else}
                                        -
                                    {/if}
                                </td>
                                <td>{$t.created_at|date_format:"%d/%m/%Y %H:%M"}</td>
                                <td nowrap>
                                    {if $t.status eq 'pending'}
                                        <button class="btn btn-info btn-xs" onclick="showAssign({$t.id})">{Lang::T('Assign')}</button>
                                    {/if}
                                    {if $t.status eq 'in_progress'}
                                        <button class="btn btn-success btn-xs" onclick="showResolve({$t.id})">{Lang::T('Resolve')}</button>
                                        <button class="btn btn-warning btn-xs" onclick="showForward({$t.id})">{Lang::T('Forward')}</button>
                                    {/if}
                                    {if $t.status eq 'pending' || $t.status eq 'in_progress'}
                                        <a href="{$_url}plugin/support_ticket&action=cancel&ticket_id={$t.id}" class="btn btn-danger btn-xs" onclick="return confirm('{Lang::T('Cancel this ticket?')}')">{Lang::T('Cancel')}</a>
                                    {/if}
                                </td>
                            </tr>
                            {foreachelse}
                            <tr><td colspan="10" class="text-center">{Lang::T('No tickets found')}</td></tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assign Modal -->
<div class="modal fade" id="assignModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <form method="post" action="{$_url}plugin/support_ticket&action=assign">
            <div class="modal-content">
                <div class="modal-header"><h4 class="modal-title">{Lang::T('Assign Ticket')}</h4></div>
                <div class="modal-body">
                    <input type="hidden" name="ticket_id" id="assign-ticket-id" value="">
                    <div class="form-group">
                        <label>{Lang::T('Staff')}</label>
                        <select name="staff_id" class="form-control" required>
                            <option value="">{Lang::T('Select Staff')}</option>
                            {foreach $staffList as $s}
                            <option value="{$s.id}">{$s.fullname|escape:'html'}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{Lang::T('Close')}</button>
                    <button type="submit" class="btn btn-primary">{Lang::T('Assign')}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Resolve Modal -->
<div class="modal fade" id="resolveModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <form method="post" action="{$_url}plugin/support_ticket&action=resolve">
            <div class="modal-content">
                <div class="modal-header"><h4 class="modal-title">{Lang::T('Complete Ticket')}</h4></div>
                <div class="modal-body">
                    <input type="hidden" name="ticket_id" id="resolve-ticket-id" value="">
                    <div class="form-group">
                        <label>{Lang::T('Resolution Type')}</label>
                        <select name="resolution_type" class="form-control" required>
                            <option value="">{Lang::T('Select')}</option>
                            {foreach $resolutionLabels as $k => $v}
                            <option value="{$k}">{$v}</option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{Lang::T('Resolution Notes')}</label>
                        <textarea name="resolution_notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{Lang::T('Close')}</button>
                    <button type="submit" class="btn btn-success">{Lang::T('Complete Ticket')}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Forward Modal -->
<div class="modal fade" id="forwardModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <form method="post" action="{$_url}plugin/support_ticket&action=forward">
            <div class="modal-content">
                <div class="modal-header"><h4 class="modal-title">{Lang::T('Forward Ticket')}</h4></div>
                <div class="modal-body">
                    <input type="hidden" name="ticket_id" id="forward-ticket-id" value="">
                    <div class="form-group">
                        <label>{Lang::T('Forward To')}</label>
                        <select name="staff_id" class="form-control" required>
                            <option value="">{Lang::T('Select Staff')}</option>
                            {foreach $staffList as $s}
                            <option value="{$s.id}">{$s.fullname|escape:'html'}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{Lang::T('Close')}</button>
                    <button type="submit" class="btn btn-warning">{Lang::T('Forward')}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function showAssign(id) {
    document.getElementById('assign-ticket-id').value = id;
    $('#assignModal').modal('show');
}
function showForward(id) {
    document.getElementById('forward-ticket-id').value = id;
    $('#forwardModal').modal('show');
}
function showResolve(id) {
    document.getElementById('resolve-ticket-id').value = id;
    $('#resolveModal').modal('show');
}
</script>
{include file="sections/footer.tpl"}