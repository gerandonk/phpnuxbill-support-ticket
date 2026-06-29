<?php

/**
 * 
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *
 * Support Ticket Plugin for PHP Mikrotik Billing
 *
 * @author: Gerandonk Mods <noc@igrwifi.my.id>
 * Website: https://igrwifi.my.id/
 * GitHub: https://github.com/gerandonk/
 * Telegram: https://t.me/sklitinov/
 *
 **/

register_menu("Support Ticket", true, "support_ticket", 'AFTER_RADIUS', 'glyphicon glyphicon-headphones', '', '', ['SuperAdmin', 'Admin']);
register_menu("Support Ticket", false, "support_ticket_customer", 'AFTER_DASHBOARD', 'glyphicon glyphicon-headphones');
register_hook('view_dashboard', 'support_ticket_dashboard_hook');

function support_ticket_create_tables()
{
    $t = ORM::for_table('support_tickets')->raw_query("SHOW TABLES LIKE 'support_tickets'")->find_one();
    if (!$t) {
        ORM::raw_execute("
            CREATE TABLE IF NOT EXISTS `support_tickets` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `customer_id` int(11) NOT NULL,
                `ticket_type` enum('upgrade','downgrade','complaint','trouble') NOT NULL,
                `description` text NOT NULL,
                `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
                `assigned_to` int(11) DEFAULT NULL,
                `resolution_type` enum('replace_device','replace_cable','system','other') DEFAULT NULL,
                `resolution_notes` text DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT current_timestamp(),
                `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `customer_id` (`customer_id`),
                KEY `assigned_to` (`assigned_to`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }
}

function support_ticket_notify_customer($ticketId, $subject, $body)
{
    $t = ORM::for_table('support_tickets')->find_one($ticketId);
    if ($t) {
        Message::addToInbox($t->customer_id, $subject, $body, 'System');
    }
}

function support_ticket_phone_format($phone)
{
    global $config;
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (preg_match('/^0/', $phone)) {
        $cc = !empty($config['country_code_phone']) ? $config['country_code_phone'] : '62';
        $phone = preg_replace('/^0/', $cc, $phone);
    }
    return $phone;
}

function support_ticket_wa_notify_admins($ticketId, $message)
{
    $admins = ORM::for_table('tbl_users')->where('status', 'Active')
        ->where_raw("`user_type` IN ('SuperAdmin', 'Admin')")
        ->where_raw("`phone` IS NOT NULL AND `phone` != ''")
        ->find_array();
    foreach ($admins as $admin) {
        Message::sendWhatsapp(support_ticket_phone_format($admin['phone']), $message);
    }
}

function support_ticket_wa_notify_staff($staffId, $message)
{
    $staff = ORM::for_table('tbl_users')->find_one($staffId);
    if ($staff && !empty($staff['phone'])) {
        Message::sendWhatsapp(support_ticket_phone_format($staff['phone']), $message);
    }
}

function support_ticket_dashboard_hook($args = [])
{
    global $ui;
    $check = ORM::for_table('support_tickets')->raw_query("SHOW TABLES LIKE 'support_tickets'")->find_one();
    if (!$check) return;
    $admin = Admin::_info();
    $userId = $admin['id'];
    $total = ORM::for_table('support_tickets')->where('assigned_to', $userId)->count();
    $pending = ORM::for_table('support_tickets')->where('assigned_to', $userId)->where('status', 'pending')->count();
    $in_progress = ORM::for_table('support_tickets')->where('assigned_to', $userId)->where('status', 'in_progress')->count();
    $widgets = $ui->getTemplateVars('widgets') ?: [];
    $html = '
    <div class="panel panel-info panel-hovered mb20 activities">
        <div class="panel-heading">' . Lang::T('My Tickets') . '</div>
        <div class="panel-body">
            <div class="row text-center">
                <div class="col-md-4"><strong style="font-size:18px">' . $total . '</strong><br><small>' . Lang::T('Total') . '</small></div>
                <div class="col-md-4"><strong style="font-size:18px;color:#e74c3c">' . $pending . '</strong><br><small>' . Lang::T('Pending') . '</small></div>
                <div class="col-md-4"><strong style="font-size:18px;color:#f39c12">' . $in_progress . '</strong><br><small>' . Lang::T('In Progress') . '</small></div>
            </div>
            <div style="margin-top:8px;text-align:right">
                <a href="' . getUrl('plugin/support_ticket&view=mine') . '" class="btn btn-xs btn-primary">' . Lang::T('View My Tickets') . '</a>
                <a href="' . getUrl('plugin/support_ticket') . '" class="btn btn-xs btn-default">' . Lang::T('All Tickets') . '</a>
            </div>
        </div>
    </div>';
    $widgets[] = ['position' => 1, 'content' => $html];
    $ui->assign('widgets', $widgets);
}

function support_ticket()
{
    global $ui, $config, $admin;
    _admin();
    $admin = Admin::_info();
    support_ticket_create_tables();

    $action = _get('action', '');
    $ticket_id = (int)_req('ticket_id', 0);

    // Handle staff creating ticket for customer
    if ($action === 'create' && _post('customer_id') && _post('ticket_type')) {
        $allowedTypes = ['upgrade', 'downgrade', 'complaint', 'trouble'];
        $type = _post('ticket_type');
        if (!in_array($type, $allowedTypes)) $type = 'complaint';
        $t = ORM::for_table('support_tickets')->create();
        $t->customer_id = (int)_post('customer_id');
        $t->ticket_type = $type;
        $t->description = _post('description') ?: '';
        $t->assigned_to = $admin['id'];
        $t->status = 'in_progress';
        $t->save();
        $ticketId = $t->id();
        $c = ORM::for_table('tbl_customers')->find_one($t->customer_id);
        $cName = $c ? $c->fullname : '#' . $t->customer_id;
        Message::addToInbox($t->customer_id, Lang::T('Ticket') . ' #' . $ticketId . ' ' . Lang::T('Sent'), Lang::T('Ticket') . ' #' . $ticketId . ' ' . Lang::T('has been received. We will process it shortly.'), 'System');
        if ($c && $c->phonenumber) {
            $waMsg = Lang::T('Ticket') . " #$ticketId\n"
                . Lang::T('Your') . ' ' . Lang::T('Ticket') . ' ' . Lang::T('has been received. We will process it shortly.') . "\n"
                . Lang::T('Description') . ': ' . $t->description . "\n"
                . getUrl("plugin/support_ticket");
            Message::sendWhatsapp(support_ticket_phone_format($c->phonenumber), $waMsg);
        }
        r2(getUrl('plugin/support_ticket'), 's', Lang::T('Ticket created'));
    }

    // Show create ticket form
    if ($action === 'add') {
        $customers = ORM::for_table('tbl_customers')->order_by_asc('fullname')->find_array();
        $ui->assign('_title', Lang::T('New Ticket'));
        $ui->assign('_system_menu', 'plugin/support_ticket');
        $ui->assign('customers', $customers);
        $ui->assign('typeLabels', [
            'upgrade' => Lang::T('Upgrade'),
            'downgrade' => Lang::T('Downgrade'),
            'complaint' => Lang::T('Complaint'),
            'trouble' => Lang::T('Trouble'),
        ]);
        $ui->display('support_ticket_add.tpl');
        return;
    }

    $typeLabels = [
        'upgrade' => Lang::T('Upgrade'),
        'downgrade' => Lang::T('Downgrade'),
        'complaint' => Lang::T('Complaint'),
        'trouble' => Lang::T('Trouble'),
    ];
    $statusLabels = [
        'pending' => Lang::T('Pending'),
        'in_progress' => Lang::T('In Progress'),
        'completed' => Lang::T('Completed'),
        'cancelled' => Lang::T('Cancelled'),
    ];
    $resolutionLabels = [
        'replace_device' => Lang::T('Replace Device'),
        'replace_cable' => Lang::T('Replace Cable'),
        'system' => Lang::T('System'),
        'other' => Lang::T('Other'),
    ];

    // Handle assign / forward
    if (($action === 'assign' || $action === 'forward') && $ticket_id && _post('staff_id')) {
        $t = ORM::for_table('support_tickets')->find_one($ticket_id);
        if ($t) {
            $staff = ORM::for_table('tbl_users')->find_one((int)_post('staff_id'));
            $staffName = $staff ? $staff->fullname : ('#' . _post('staff_id'));
            $t->assigned_to = (int)_post('staff_id');
            if ($t->status === 'pending') $t->status = 'in_progress';
            $t->save();
            $isForward = $action === 'forward';
            $subject = $isForward ? Lang::T('Ticket Forwarded') : Lang::T('Ticket Processed');
            $actionLabel = $isForward ? Lang::T('forwarded to') : Lang::T('assigned to');
            support_ticket_notify_customer($ticket_id, Lang::T('Ticket') . ' #' . $ticket_id . ' ' . $subject, Lang::T('Ticket') . ' #' . $ticket_id . ' (' . $typeLabels[$t->ticket_type] . ') ' . $actionLabel . ' ' . $staffName . '.');
            // Notify assigned staff via WhatsApp
            $c = ORM::for_table('tbl_customers')->find_one($t->customer_id);
            $cName = $c ? $c->fullname : '#' . $t->customer_id;
            $cPhone = $c ? $c->phonenumber : '-';
            $waTitle = $isForward ? Lang::T('Forwarded') : Lang::T('Assigned');
            $waMsg = Lang::T('Ticket') . " #$ticket_id " . $waTitle . ' ' . Lang::T('to you') . "\n"
                . Lang::T('Customer') . ': ' . $cName . "\n"
                . Lang::T('Phone') . ': ' . $cPhone . "\n"
                . Lang::T('Type') . ': ' . $typeLabels[$t->ticket_type] . "\n"
                . Lang::T('Description') . ': ' . $t->description . "\n"
                . getUrl("plugin/support_ticket&view=mine");
            support_ticket_wa_notify_staff((int)_post('staff_id'), $waMsg);
        }
        r2(getUrl('plugin/support_ticket'), 's', $action === 'forward' ? Lang::T('Ticket forwarded') : Lang::T('Ticket assigned'));
    }

    // Handle resolution
    if ($action === 'resolve' && $ticket_id) {
        $t = ORM::for_table('support_tickets')->find_one($ticket_id);
        if ($t) {
            $resType = _post('resolution_type');
            $resNotes = _post('resolution_notes');
            $t->status = 'completed';
            $t->resolution_type = $resType;
            $t->resolution_notes = $resNotes;
            $t->save();
            $resLabel = isset($resolutionLabels[$resType]) ? $resolutionLabels[$resType] : $resType;
            $notes = $resNotes ? ' - ' . $resNotes : '';
            support_ticket_notify_customer($ticket_id, Lang::T('Ticket') . ' #' . $ticket_id . ' ' . Lang::T('Completed'), Lang::T('Ticket') . ' #' . $ticket_id . ' (' . $typeLabels[$t->ticket_type] . ') ' . Lang::T('has been completed.') . ' ' . Lang::T('Resolution') . ': ' . $resLabel . '.' . $notes);
            // Notify customer via WhatsApp
            $c = ORM::for_table('tbl_customers')->find_one($t->customer_id);
            if ($c && !empty($c->phonenumber)) {
                $waMsg = Lang::T('Ticket') . " #$ticket_id " . Lang::T('Completed') . "\n"
                    . Lang::T('Resolution') . ': ' . $resLabel . "\n";
                if ($resNotes) $waMsg .= Lang::T('Notes') . ': ' . $resNotes . "\n";
                Message::sendWhatsapp(support_ticket_phone_format($c->phonenumber), $waMsg);
            }
        }
        r2(getUrl('plugin/support_ticket'), 's', Lang::T('Ticket completed'));
    }

    // Handle cancel
    if ($action === 'cancel' && $ticket_id) {
        $t = ORM::for_table('support_tickets')->find_one($ticket_id);
        if ($t) {
            $t->status = 'cancelled';
            $t->save();
            support_ticket_notify_customer($ticket_id, Lang::T('Ticket') . ' #' . $ticket_id . ' ' . Lang::T('Cancelled'), Lang::T('Ticket') . ' #' . $ticket_id . ' (' . $typeLabels[$t->ticket_type] . ') ' . Lang::T('has been cancelled.'));
        }
        r2(getUrl('plugin/support_ticket'), 's', Lang::T('Ticket cancelled'));
    }

    // Filters
    $view = _get('view', '');
    $statusFilter = _get('status', '');
    $isSuperAdmin = $admin['user_type'] === 'SuperAdmin';
    $list = ORM::for_table('support_tickets')->order_by_desc('created_at');

    // Non-SuperAdmin defaults to mine; SuperAdmin can use &view=mine
    if (! $isSuperAdmin) {
        $view = 'mine';
    }
    if ($view === 'mine') {
        $list = $list->where('assigned_to', $admin['id']);
    }
    if ($statusFilter) {
        $list = $list->where('status', $statusFilter);
    }
    $tickets = $list->find_array();

    // Enrich tickets with customer & staff names
    foreach ($tickets as &$t) {
        $c = ORM::for_table('tbl_customers')->find_one($t['customer_id']);
        $t['customer_name'] = $c ? $c->fullname : ('#' . $t['customer_id']);
        $t['customer_username'] = $c ? $c->username : '';
        $t['customer_phone'] = $c ? $c->phonenumber : '';
        if ($t['assigned_to']) {
            $s = ORM::for_table('tbl_users')->find_one($t['assigned_to']);
            $t['staff_name'] = $s ? $s->fullname : ('#' . $t['assigned_to']);
        } else {
            $t['staff_name'] = '-';
        }
    }

    // Stats (all tickets for header, or filtered)
    if ($view === 'mine') {
        $base = ORM::for_table('support_tickets')->where('assigned_to', $admin['id']);
    } else {
        $base = ORM::for_table('support_tickets');
    }
    $stats = [];
    $stats['total'] = (clone $base)->count();
    $stats['pending'] = (clone $base)->where('status', 'pending')->count();
    $stats['in_progress'] = (clone $base)->where('status', 'in_progress')->count();
    $stats['completed'] = (clone $base)->where('status', 'completed')->count();

    // Staff list for assignment
    $staffList = ORM::for_table('tbl_users')->find_array();

    // Staff completion stats
    $staffStats = ORM::for_table('support_tickets')
        ->select('support_tickets.*')
        ->select('tbl_users.fullname')
        ->join('tbl_users', array('support_tickets.assigned_to', '=', 'tbl_users.id'))
        ->where('support_tickets.status', 'completed')
        ->group_by('support_tickets.assigned_to')
        ->select_expr('COUNT(*)', 'cnt')
        ->order_by_desc('cnt')
        ->find_array();
    $topStaff = null;
    $topStaffCount = 0;
    foreach ($staffStats as $s) {
        if ($s['cnt'] > $topStaffCount) {
            $topStaffCount = $s['cnt'];
            $topStaff = $s['fullname'];
        }
    }

    $ui->assign('_title', Lang::T('Support Tickets'));
    $ui->assign('_system_menu', 'plugin/support_ticket');
    $ui->assign('_admin', $admin);
    $ui->assign('tickets', $tickets);
    $ui->assign('stats', $stats);
    $ui->assign('staffList', $staffList);
    $ui->assign('staffStats', $staffStats);
    $ui->assign('topStaff', $topStaff);
    $ui->assign('topStaffCount', $topStaffCount);
    $ui->assign('typeLabels', $typeLabels);
    $ui->assign('statusLabels', $statusLabels);
    $ui->assign('resolutionLabels', $resolutionLabels);
    $ui->assign('statusFilter', $statusFilter);
    $ui->assign('view', $view);
    $ui->assign('isSuperAdmin', $isSuperAdmin);
    $ui->display('support_ticket.tpl');
}

function support_ticket_customer()
{
    global $ui;
    _auth();
    support_ticket_create_tables();

    $customer = User::_info();
    $customerId = $customer['id'];

    $typeLabels = [
        'upgrade' => Lang::T('Upgrade'),
        'downgrade' => Lang::T('Downgrade'),
        'complaint' => Lang::T('Complaint'),
        'trouble' => Lang::T('Trouble'),
    ];
    $statusLabels = [
        'pending' => Lang::T('Pending'),
        'in_progress' => Lang::T('In Progress'),
        'completed' => Lang::T('Completed'),
        'cancelled' => Lang::T('Cancelled'),
    ];

    // Handle new ticket submission
    if (isset($_POST['save_ticket'])) {
        $ticket_type = _post('ticket_type');
        $description = _post('description');
        if ($ticket_type && $description) {
            $t = ORM::for_table('support_tickets')->create();
            $t->customer_id = $customerId;
            $t->ticket_type = $ticket_type;
            $t->description = $description;
            $t->save();
            $ticketId = $t->id();
            Message::addToInbox($customerId, Lang::T('Ticket') . ' #' . $ticketId . ' ' . Lang::T('Sent'), Lang::T('Ticket') . ' ' . $typeLabels[$ticket_type] . ' #' . $ticketId . ' ' . Lang::T('has been received. We will process it shortly.'), 'System');
            // Notify admins via WhatsApp
            $phone = !empty($customer['phonenumber']) ? $customer['phonenumber'] : '-';
            $waMsg = Lang::T('New Ticket') . " #$ticketId\n"
                . Lang::T('Customer') . ': ' . $customer['fullname'] . "\n"
                . Lang::T('Phone') . ': ' . $phone . "\n"
                . Lang::T('Type') . ': ' . $typeLabels[$ticket_type] . "\n"
                . Lang::T('Description') . ': ' . $description . "\n"
                . getUrl("plugin/support_ticket");
            support_ticket_wa_notify_admins($ticketId, $waMsg);
            r2(getUrl('plugin/support_ticket_customer'), 's', Lang::T('Ticket submitted successfully'));
        } else {
            r2(getUrl('plugin/support_ticket_customer'), 'e', Lang::T('Please fill all required fields'));
        }
    }

    // Customer's tickets
    $tickets = ORM::for_table('support_tickets')
        ->where('customer_id', $customerId)
        ->order_by_desc('created_at')
        ->find_array();

    foreach ($tickets as &$t) {
        if ($t['assigned_to']) {
            $s = ORM::for_table('tbl_users')->find_one($t['assigned_to']);
            $t['staff_name'] = $s ? $s->fullname : '-';
        } else {
            $t['staff_name'] = '-';
        }
    }

    $ui->assign('_title', Lang::T('Support Ticket'));
    $ui->assign('customer', $customer);
    $ui->assign('tickets', $tickets);
    $ui->assign('typeLabels', $typeLabels);
    $ui->assign('statusLabels', $statusLabels);
    $ui->display('support_ticket_customer.tpl');
}
