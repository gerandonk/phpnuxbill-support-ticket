{include file="sections/header.tpl"}
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="post" action="{$_url}plugin/support_ticket&action=create">
                    <div class="form-group">
                        <label>{Lang::T('Customer')}</label>
                        <select name="customer_id" class="form-control" required>
                            <option value="">{Lang::T('Select Customer')}</option>
                            {foreach $customers as $c}
                            <option value="{$c.id}">{$c.fullname|escape:'html'} ({$c.username|escape:'html'}) - {$c.phonenumber|escape:'html'}</option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{Lang::T('Type')}</label>
                        <select name="ticket_type" class="form-control" required>
                            <option value="">{Lang::T('Select Type')}</option>
                            {foreach $typeLabels as $k => $v}
                            <option value="{$k}">{$v}</option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{Lang::T('Description')}</label>
                        <textarea name="description" class="form-control" rows="5" required placeholder="{Lang::T('Describe the issue or request...')}"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">{Lang::T('Create Ticket')}</button>
                    <a href="{$_url}plugin/support_ticket" class="btn btn-default">{Lang::T('Cancel')}</a>
                </form>
            </div>
        </div>
    </div>
</div>
{include file="sections/footer.tpl"}