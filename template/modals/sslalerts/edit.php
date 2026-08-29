<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title"><?php _e('Edit SSL Alert'); ?></h4>
</div>

<div class="modal-body">
    <div class="row">

        <div class="col-md-6">
            <div class="form-group">
                <label for="type"><?php _e('Alert Type'); ?> *</label>
                <select class="form-control select2 select2-hidden-accessible" id="type" name="type" style="width: 100%;" tabindex="-1" aria-hidden="true" required>
                    <option></option>
                    <option value='expiringsoon' <?php if($alert['type'] == "expiringsoon") echo "selected"; ?> ><?php _e('Expiring Soon (days remaining)'); ?></option>
                    <option value='expired' <?php if($alert['type'] == "expired") echo "selected"; ?> ><?php _e('Already Expired (days remaining)'); ?></option>
                </select>
            </div>
        </div>

        <div class="col-md-2" >
            <div class="form-group" id="comparison-div">
                <label for="comparison"><?php _e('Comparison'); ?></label>
                <select class="form-control select2 select2-hidden-accessible" id="comparison" name="comparison" style="width: 100%;" tabindex="-1" aria-hidden="true">
                    <option value='<=' <?php if($alert['comparison'] == "<=") echo "selected"; ?> ><=</option>
                    <option value='==' <?php if($alert['comparison'] == "==") echo "selected"; ?> >==</option>
                    <option value='>=' <?php if($alert['comparison'] == ">=") echo "selected"; ?> >>=</option>
                    <option value='>' <?php if($alert['comparison'] == ">") echo "selected"; ?> >></option>
                    <option value='<' <?php if($alert['comparison'] == "<") echo "selected"; ?> ><</option>
                </select>
            </div>
        </div>

        <div class="col-md-2">
            <div class="form-group" id="comparison_limit-div">
                <label for="comparison_limit"><?php _e('Days'); ?></label>
                <input type="text" class="form-control" id="comparison_limit" name="comparison_limit" value="<?php echo $alert['comparison_limit']; ?>">
            </div>
        </div>

        <div class="col-md-2">
            <div class="form-group">
                <label for="status"><?php _e('Status'); ?></label>
                <select class="form-control select2 select2-hidden-accessible" id="status" name="status" style="width: 100%;" tabindex="-1" aria-hidden="true">
                    <option value='1' <?php if($alert['status'] == "1") echo "selected"; ?> ><?php _e('Active'); ?></option>
                    <option value='0' <?php if($alert['status'] == "0") echo "selected"; ?> ><?php _e('Inactive'); ?></option>
                </select>
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label for="occurrences"><?php _e('Occurrences'); ?> <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Number of consecutive checks the condition must hold before an incident opens.'); ?>"></i></label>
                <input type="text" class="form-control" id="occurrences" name="occurrences" required value="<?php echo $alert['occurrences']; ?>">
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="contacts"><?php _e('Contacts'); ?> <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Contacts selected here will receive notifications for this alert.'); ?>"></i></label>
                <select class="form-control select2tags select2-hidden-accessible" id="contacts" name="contacts[]" style="width: 100%;" multiple>
                    <?php foreach ($contacts as $contact) { ?>
                        <option value='<?php echo $contact['id']; ?>' <?php if(in_array($contact['id'], $selected_contacts)) echo "selected"; ?> ><?php echo e($contact['name']); ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label for="repeats"><?php _e('Repeat'); ?> <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Resend the notification if the incident is not resolved.'); ?>"></i></label>
                <select class="form-control select2 select2-hidden-accessible" id="repeats" name="repeats" style="width: 100%;" tabindex="-1" aria-hidden="true">
                    <option value='0' <?php if($alert['repeats'] == "0") echo "selected"; ?> ><?php _e('Once'); ?></option>
                    <option value='1440' <?php if($alert['repeats'] == "1440") echo "selected"; ?> ><?php _e('Every 24 hours'); ?></option>
                    <option value='10080' <?php if($alert['repeats'] == "10080") echo "selected"; ?> ><?php _e('Every 7 days'); ?></option>
                </select>
            </div>
        </div>

    </div>

    <input type="hidden" name="id" value="<?php echo $alert['id']; ?>">
    <input type="hidden" name="sslid" value="<?php echo e($_GET['routeid'] ?? ''); ?>">

    <input type="hidden" name="action" value="editSslAlert">
    <input type="hidden" name="route" value="<?php echo e($_GET['reroute'] ?? ''); ?>">
    <input type="hidden" name="routeid" value="<?php echo e($_GET['routeid'] ?? ''); ?>">
    <input type="hidden" name="section" value="alerting">
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-flat btn-default" data-dismiss="modal"><i class="fa fa-times"></i> <?php _e('Cancel'); ?></button>
    <button type="submit" class="btn btn-flat btn-success"><i class="fa fa-save"></i> <?php _e('Save'); ?></button>
</div>

<script type="text/javascript">

    $(".select2").select2({
        placeholder: "<?php _e('Please select'); ?>"
    });

    $(function() { $(".select2tags").select2({
        tags: true
    }); });

</script>
