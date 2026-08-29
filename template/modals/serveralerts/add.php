<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title"><?php _e('Add Server Alert'); ?></h4>
</div>

<div class="modal-body">

    <div class="row">



        <div class="col-md-6">
            <div class="form-group">
                <label for="type"><?php _e('Alert Type'); ?> *</label>
                <select class="form-control select2 select2-hidden-accessible" id="type" name="type" style="width: 100%;" tabindex="-1" aria-hidden="true" required>
                    <option></option>
                    <optgroup label="<?php _e('System'); ?>">
                        <option value='nodata'><?php _e('No Data'); ?></option>
                        <option value='cpu'><?php _e('CPU Usage %'); ?></option>
                        <?php if($server['type'] == 'linux') { ?>
                            <option value='cpuio'><?php _e('CPU IO Wait %'); ?></option>
                            <option value='load1min'><?php _e('System Load 1 Min'); ?></option>
                            <option value='load5min'><?php _e('System Load 5 Min'); ?></option>
                            <option value='load15min'><?php _e('System Load 15 Min'); ?></option>
                        <?php } ?>
                        <option value='service'><?php _e('Service/Process Not Running'); ?></option>
                    </optgroup>

                    <optgroup label="<?php _e('RAM & Disk'); ?>">
                        <option value='ram'><?php _e('RAM Usage %'); ?></option>
                        <option value='ramMB'><?php _e('RAM Usage MB'); ?></option>
                        <option value='swap'><?php _e('Swap Usage %'); ?></option>
                        <option value='swapMB'><?php _e('Swap Usage MB'); ?></option>
                        <option value='disk'><?php _e('Disk Usage % (Aggregated)'); ?></option>
                        <option value='diskGB'><?php _e('Disk Usage GB (Aggregated)'); ?></option>

                        <?php if($server['type'] == 'linux') { ?>
                            <?php foreach ($disks as $disk) { $cells = explode(",", $disk); ?>
                                <option value='disk:<?php echo $cells[6]; ?>'><?php _e('Disk Usage %:'); ?> <?php echo $cells[6]; ?> </option>
                                <option value='diskGB:<?php echo $cells[6]; ?>'><?php _e('Disk Usage GB:'); ?> <?php echo $cells[6]; ?> </option>
                            <?php } ?>
                            <option value='mdadmDegraded'><?php _e('Mdadm Degraded'); ?></option>
                        <?php } ?>

                        <?php if($server['type'] == 'windows') { ?>
                            <?php foreach ($disks as $disk) { if(isset($disk['size'])) { ?>
                                <option value='disk:<?php echo $disk['fs']; ?>'><?php _e('Disk Usage %:'); ?> <?php echo $disk['fs']; ?> </option>
                                <option value='diskGB:<?php echo $disk['fs']; ?>'><?php _e('Disk Usage GB:'); ?> <?php echo $disk['fs']; ?> </option>
                            <?php } } ?>
                        <?php } ?>

                    </optgroup>

                    <optgroup label="<?php _e('Network'); ?>">
                        <?php if($server['type'] == 'linux') { ?>
                            <option value='connections'><?php _e('Connections'); ?></option>
                            <option value='ssh'><?php _e('SSH Sessions'); ?></option>
                        <?php } ?>

                        <option value='ping'><?php _e('Ping Latency'); ?></option>
                        <option value='netdl'><?php _e('Network Download Speed MB/s'); ?></option>
                        <option value='netup'><?php _e('Network Upload Speed MB/s'); ?></option>
                    </optgroup>

                </select>
            </div>
        </div>

        <div class="col-md-2" >
            <div class="form-group" id="comparison-div">
                <label for="comparison"><?php _e('Comparison'); ?></label>
                <select class="form-control select2 select2-hidden-accessible" id="comparison" name="comparison" style="width: 100%;" tabindex="-1" aria-hidden="true">
                    <option value='=='>==</option>
                    <option value='>='>>=</option>
                    <option value='<='><=</option>
                    <option value='>'>></option>
                    <option value='<'><</option>
                    <option value='!='>!=</option>
                </select>
            </div>
        </div>

        <div class="col-md-2">
            <div class="form-group" id="comparison_limit-div">
                <label for="comparison_limit" id="comparison_limit_label"><?php _e('Limit'); ?></label>
                <input type="text" class="form-control" id="comparison_limit" name="comparison_limit">
            </div>
        </div>

        <div class="col-md-2">
            <div class="form-group">
                <label for="status"><?php _e('Status'); ?></label>
                <select class="form-control select2 select2-hidden-accessible" id="status" name="status" style="width: 100%;" tabindex="-1" aria-hidden="true">
                    <option value='1'><?php _e('Active'); ?></option>
                    <option value='0'><?php _e('Inactive'); ?></option>
                </select>
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group" id="occurrences-div">
                <label for="occurrences"><?php _e('Occurrences'); ?> <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('If the problem occurs for more than this value an incident will be opened and the selected contacts will be notified.'); ?>"></i></label>
                <input type="text" class="form-control" id="occurrences" name="occurrences" required value="2">
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="contacts"><?php _e('Contacts'); ?> <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Contacts selected here will receive notifications for this alert.'); ?>"></i></label>
                <select class="form-control select2tags select2-hidden-accessible" id="contacts" name="contacts[]" style="width: 100%;" multiple>
                    <?php foreach ($contacts as $contact) { ?>
                        <option value='<?php echo $contact['id']; ?>'><?php echo $contact['name']; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label for="repeats"><?php _e('Repeat'); ?> <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Resend the notification if the incident is not resolved.'); ?>"></i></label>
                <select class="form-control select2 select2-hidden-accessible" id="repeats" name="repeats" style="width: 100%;" tabindex="-1" aria-hidden="true">
                    <option value='0'><?php _e('Once'); ?></option>
                    <option value='15'><?php _e('Every 15 mins'); ?></option>
                    <option value='30'><?php _e('Every 30 mins'); ?></option>
                    <option value='45'><?php _e('Every 45 mins'); ?></option>
                    <option value='60'><?php _e('Every 60 mins'); ?></option>
                    <option value='90'><?php _e('Every 90 mins'); ?></option>
                    <option value='120'><?php _e('Every 120 mins'); ?></option>
                    <option value='180'><?php _e('Every 180 mins'); ?></option>
                </select>
            </div>
        </div>



    </div>


    <input type="hidden" name="serverid" value="<?php echo $_GET['routeid']; ?>">

    <input type="hidden" name="action" value="addServerAlert">
    <input type="hidden" name="route" value="<?php echo $_GET['reroute']; ?>">
    <input type="hidden" name="routeid" value="<?php echo $_GET['routeid']; ?>">
    <input type="hidden" name="section" value="alerting">
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-flat btn-default" data-dismiss="modal"><i class="fa fa-times"></i> <?php _e('Cancel'); ?></button>
    <button type="submit" class="btn btn-flat btn-primary"><i class="fa fa-check"></i> <?php _e('Add Alert'); ?></button>
</div>


<script type="text/javascript">

	$(".select2").select2({
        placeholder: "<?php _e('Please select'); ?>"
    });

    $(function() { $(".select2tags").select2({
        tags: true
    }); });


    $("#type").on("change", function (e) {
        var value = $("#type").val();

        $("#comparison-div").fadeIn();
        $("#occurrences-div").fadeIn();
        $("#comparison_limit-div").fadeIn();
        $("#comparison_limit_label").text("<?php _e('Limit'); ?>");

        if(value == "nodata") { $("#comparison-div").fadeOut(); $("#comparison_limit-div").fadeOut(); }
        if(value == "service") { $("#comparison-div").fadeOut(); $("#occurrences-div").fadeOut(); $("#comparison_limit_label").text("<?php _e('Service'); ?>");   }

       
        if(value == "mdadmDegraded") { $("#comparison-div").fadeOut(); $("#occurrences-div").fadeOut(); $("#comparison_limit-div").fadeOut();   }


        //alert(value);
    });


</script>
