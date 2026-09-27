<?php
// Shared by serveractions/add and serveractions/edit. $action is only set on edit.
// The Windows agent doesn't run commands, so only webhooks are offered there.
$current = $action ?? [ "alertid" => "", "name" => "", "type" => "webhook", "webhook_url" => "", "command" => "", "timeout_seconds" => 30, "status" => 1 ];
$commandsSupported = ($server['type'] == 'linux');
?>
<div class="col-md-6">
    <div class="form-group">
        <label for="alertid"><?php _e('When this alert fires'); ?> *</label>
        <select class="form-control select2 select2-hidden-accessible" id="alertid" name="alertid" style="width: 100%;" tabindex="-1" aria-hidden="true" required>
            <?php foreach ($alerts as $alert) { ?>
                <option value='<?php echo $alert['id']; ?>' <?php if ($alert['id'] == $current['alertid']) echo 'selected'; ?>><?php echo e(ServerAction::alertLabel($alert)); ?></option>
            <?php } ?>
        </select>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="name"><?php _e('Name'); ?> *</label>
        <input type="text" class="form-control" id="name" name="name" required value="<?php echo e($current['name']); ?>" placeholder="<?php _e('e.g. restart nginx'); ?>">
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="type"><?php _e('Action'); ?> *</label>
        <select class="form-control select2 select2-hidden-accessible" id="actiontype" name="type" style="width: 100%;" tabindex="-1" aria-hidden="true">
            <option value='webhook' <?php if ($current['type'] == 'webhook') echo 'selected'; ?>><?php _e('Call Webhook'); ?></option>
            <?php if ($commandsSupported) { ?>
                <option value='command' <?php if ($current['type'] == 'command') echo 'selected'; ?>><?php _e('Run Command on this server'); ?></option>
            <?php } ?>
        </select>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="status"><?php _e('Status'); ?></label>
        <select class="form-control select2 select2-hidden-accessible" id="status" name="status" style="width: 100%;" tabindex="-1" aria-hidden="true">
            <option value='1' <?php if ($current['status'] == 1) echo 'selected'; ?>><?php _e('Active'); ?></option>
            <option value='0' <?php if ($current['status'] == 0) echo 'selected'; ?>><?php _e('Inactive'); ?></option>
        </select>
    </div>
</div>

<div class="col-md-12" id="webhook-div">
    <div class="form-group">
        <label for="webhook_url"><?php _e('Webhook URL'); ?> * <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Receives a JSON POST with the server, alert and incident details when the alert fires.'); ?>"></i></label>
        <input type="url" class="form-control" id="webhook_url" name="webhook_url" value="<?php echo e($current['webhook_url']); ?>" placeholder="https://example.com/hooks/alert">
    </div>
</div>

<?php if ($commandsSupported) { ?>
<div class="col-md-8" id="command-div">
    <div class="form-group">
        <label for="command"><?php _e('Command'); ?> * <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Runs once on this server via its agent (as the agent user), on the agent\'s next check-in after the alert fires.'); ?>"></i></label>
        <input type="text" class="form-control" id="command" name="command" value="<?php echo e($current['command']); ?>" placeholder="systemctl restart nginx">
    </div>
</div>

<div class="col-md-4" id="timeout-div">
    <div class="form-group">
        <label for="timeout_seconds"><?php _e('Timeout (seconds)'); ?></label>
        <input type="number" min="1" class="form-control" id="timeout_seconds" name="timeout_seconds" value="<?php echo e($current['timeout_seconds']); ?>">
    </div>
</div>
<?php } ?>

<script type="text/javascript">
    $(".select2").select2({
        placeholder: "<?php _e('Please select'); ?>"
    });

    <?php // Not wrapped in $(function(){}) - this modal is injected via AJAX, see commands/add.php. ?>
    function toggleActionType() {
        var isCommand = $("#actiontype").val() == "command";
        $("#webhook-div").toggle(!isCommand);
        $("#webhook_url").prop("required", !isCommand);
        $("#command-div, #timeout-div").toggle(isCommand);
        $("#command").prop("required", isCommand);
    }
    $("#actiontype").on("change", toggleActionType);
    toggleActionType();
</script>
