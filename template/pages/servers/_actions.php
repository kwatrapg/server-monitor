<?php
// Actions tab of servers/manage-linux and servers/manage-windows. Expects
// $server, $alert_actions, $alert_action_runs, $perms and $manageRoute.
$run_labels = [
    "pending" => ["label-default", __('Waiting for agent')],
    "dispatched" => ["label-info", __('Running')],
    "done" => ["label-success", __('Success')],
    "failed" => ["label-danger", __('Failed')],
];
$action_names = [];
foreach ($alert_actions as $a) $action_names[$a['id']] = $a['name'];
?>
<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered">
        <thead>
            <tr>
                <th><?php _e('Name'); ?></th>
                <th><?php _e('When Alert Fires'); ?></th>
                <th><?php _e('Action'); ?></th>
                <th><?php _e('Status'); ?></th>
                <th class="text-right"></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($alert_actions)) { ?>
                <tr><td colspan="5" class="text-muted"><?php _e('No actions yet. Add one to call a webhook or run a command when an alert fires.'); ?></td></tr>
            <?php } ?>
            <?php foreach ($alert_actions as $alert_action) { ?>
                <tr>
                    <td><?php echo e($alert_action['name']); ?></td>
                    <td><?php echo e(ServerAction::alertLabel(getRowById("app_servers_alerts", $alert_action['alertid']))); ?></td>
                    <td>
                        <?php if ($alert_action['type'] == 'webhook') { ?>
                            <span class="label bg-purple"><?php _e('Webhook'); ?></span> <code><?php echo e($alert_action['webhook_url']); ?></code>
                        <?php } else { ?>
                            <span class="label bg-navy"><?php _e('Command'); ?></span> <code><?php echo e($alert_action['command']); ?></code>
                        <?php } ?>
                    </td>
                    <td>
                        <?php if ($alert_action['status'] == 1) { ?>
                            <span class="label label-success"><?php _e("Active"); ?></span>
                        <?php } else { ?>
                            <span class="label label-default"><?php _e("Inactive"); ?></span>
                        <?php } ?>
                    </td>
                    <td>
                        <div class='pull-right'>
                            <div class="btn-group">
                                <?php if (in_array("editServer", $perms)) { ?><a href="#" onClick='showM("?modal=serveractions/edit&reroute=<?php echo $manageRoute; ?>&routeid=<?php echo $server['id']; ?>&id=<?php echo $alert_action['id']; ?>");return false' class="btn btn-success btn-flat btn-sm"><i class="fa fa-edit"></i></a><?php } ?>
                                <?php if (in_array("editServer", $perms)) { ?><a href="#" onClick='showM("?modal=serveractions/delete&reroute=<?php echo $manageRoute; ?>&routeid=<?php echo $server['id']; ?>&id=<?php echo $alert_action['id']; ?>");return false' class="btn btn-danger btn-flat btn-sm"><i class="fa fa-trash-o"></i></a><?php } ?>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<h4><?php _e('Recent Runs'); ?></h4>
<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered">
        <thead>
            <tr>
                <th><?php _e('Time'); ?></th>
                <th><?php _e('Action'); ?></th>
                <th><?php _e('Result'); ?></th>
                <th><?php _e('Code'); ?></th>
                <th><?php _e('Output'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($alert_action_runs)) { ?>
                <tr><td colspan="5" class="text-muted"><?php _e('No actions have run yet.'); ?></td></tr>
            <?php } ?>
            <?php foreach ($alert_action_runs as $run) { $run_label = $run_labels[$run['status']] ?? ["label-default", $run['status']]; ?>
                <tr>
                    <td><?php echo dateTimeDisplay($run['created']); ?></td>
                    <td><?php echo e($action_names[$run['actionid']] ?? ''); ?> <small class="text-muted">(<?php echo e($run['type']); ?>)</small></td>
                    <td><span class="label <?php echo $run_label[0]; ?>"><?php echo e($run_label[1]); ?></span></td>
                    <td><?php echo e($run['result_code'] ?? ''); ?></td>
                    <td><pre style="margin:0;max-height:80px;overflow:auto;white-space:pre-wrap"><?php echo e($run['output']); ?></pre></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
