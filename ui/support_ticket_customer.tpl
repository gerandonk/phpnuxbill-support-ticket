{include file="customer/header.tpl"}
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">{Lang::T('New Ticket')}</div>
            <div class="panel-body">
                <form method="post">
                    <div class="form-group">
                        <label>{Lang::T('Type')}</label>
                        <select name="ticket_type" class="form-control" required>
                            <option value="">{Lang::T('Select Type')}</option>
                            <option value="upgrade">{Lang::T('Upgrade')}</option>
                            <option value="downgrade">{Lang::T('Downgrade')}</option>
                            <option value="complaint">{Lang::T('Complaint')}</option>
                            <option value="trouble">{Lang::T('Trouble')}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{Lang::T('Description')}</label>
                        <textarea name="description" class="form-control" rows="4" required placeholder="{Lang::T('Describe your issue or request...')}"></textarea>
                    </div>
                    <button type="submit" name="save_ticket" value="1" class="btn btn-primary">{Lang::T('Submit Ticket')}</button>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">{Lang::T('My Tickets')}</div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{Lang::T('Type')}</th>
                                <th>{Lang::T('Description')}</th>
                                <th>{Lang::T('Status')}</th>
                                <th>{Lang::T('Staff')}</th>
                                <th>{Lang::T('Resolution')}</th>
                                <th>{Lang::T('Date')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $tickets as $t}
                            <tr>
                                <td>{$t.id}</td>
                                <td><span class="label label-info">{$typeLabels[$t.ticket_type]|default:$t.ticket_type}</span></td>
                                <td>{$t.description|escape:'html'|truncate:100}</td>
                                <td>
                                    {if $t.status eq 'completed'}<span class="label label-success">{$statusLabels[$t.status]}</span>
                                    {elseif $t.status eq 'in_progress'}<span class="label label-warning">{$statusLabels[$t.status]}</span>
                                    {elseif $t.status eq 'cancelled'}<span class="label label-default">{$statusLabels[$t.status]}</span>
                                    {else}<span class="label label-danger">{$statusLabels[$t.status]}</span>
                                    {/if}
                                </td>
                                <td>{$t.staff_name|escape:'html'}</td>
                                <td>
                                    {if $t.resolution_notes}
                                        {$t.resolution_notes|escape:'html'|truncate:60}
                                    {else}
                                        -
                                    {/if}
                                </td>
                                <td>{$t.created_at|date_format:"%d/%m/%Y %H:%M"}</td>
                            </tr>
                            {foreachelse}
                            <tr><td colspan="7" class="text-center">{Lang::T('No tickets yet')}</td></tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
{include file="customer/footer.tpl"}