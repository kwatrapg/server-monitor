<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title"><?php _e('Install Monitoring Agent'); ?></h4>
</div>

<div class="modal-body">


    <p><?php _e('Please run the following command as root to install or reinstall the monitoring agent on server'); ?> <?php echo e($server['name']); ?></p>
    <pre>wget -N --no-check-certificate <?php echo baseURL(); ?>assets/install.sh && bash install.sh <?php echo e($server['serverkey']); ?> <?php echo rtrim(baseURL(), '/') ?></pre>



</div>

<div class="modal-footer">
    <button type="button" class="btn btn-flat btn-primary clipboard" data-clipboard-text="wget -N --no-check-certificate <?php echo baseURL(); ?>assets/install.sh && bash install.sh <?php echo e($server['serverkey']); ?> <?php echo rtrim(baseURL(), '/') ?>"><i class="fa fa-copy"></i> <?php _e('Copy'); ?></button>
    <button type="button" class="btn btn-flat btn-default" data-dismiss="modal"><i class="fa fa-times"></i> <?php _e('Close'); ?></button>
</div>


<script type="text/javascript">
	new Clipboard('.clipboard');
</script>
