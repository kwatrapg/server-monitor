<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title"><?php _e('Install Monitoring Agent'); ?></h4>
</div>

<div class="modal-body">

    <h4><?php _e('Follow the steps below to install the Windows monitoring agent on this server:'); ?></h4>

    <p><b>1.</b> <?php _e('Download agent installer from '); ?> <a href="./assets/sentruo-agent-windows.exe"><?php _e('here'); ?></a> <?php _e('and install on your server.'); ?>.</p>

    <p><b>2.</b> <?php _e('Open the monitoring agent after installation finishes.'); ?>.<br></p>

    <p><b>3. </b> <?php _e('Apply the following configuration and close the window'); ?>:</p>

    <p>
        <b>Server Key</b> <?php echo e($server['serverkey']); ?><br>
        <b>Gateway Address</b> <?php echo rtrim(baseURL(), '/') ?>/agent.php<br>
    </p>

    <p><?php _e('Agent will begin reporting data in a few minutes.'); ?></p>

</div>

<div class="modal-footer">
    <button type="button" class="btn btn-flat btn-primary clipboard" data-clipboard-text="<?php echo e($server['serverkey']); ?>"><i class="fa fa-copy"></i> <?php _e('Copy Server Key'); ?></button>
    <button type="button" class="btn btn-flat btn-primary clipboard" data-clipboard-text="<?php echo rtrim(baseURL(), '/') ?>/agent.php"><i class="fa fa-copy"></i> <?php _e('Copy Gateway Address'); ?></button>
    <button type="button" class="btn btn-flat btn-default" data-dismiss="modal"><i class="fa fa-times"></i> <?php _e('Close'); ?></button>
</div>


<script type="text/javascript">
	new Clipboard('.clipboard');
</script>
