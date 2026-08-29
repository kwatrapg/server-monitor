<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title"><?php _e('Edit Website Alert'); ?></h4>
</div>

<div class="modal-body">
    <div class="row">

        <div class="col-md-6">
            <div class="form-group">
                <label for="type"><?php _e('Alert Type'); ?> *</label>
                <select class="form-control select2 select2-hidden-accessible" id="type" name="type" style="width: 100%;" tabindex="-1" aria-hidden="true" required>
                    <option></option>
                    <option value='responsecode' <?php if($alert['type'] == "responsecode") echo "selected"; ?> ><?php _e('HTTP Response Code'); ?></option>
                    <option value='loadtime' <?php if($alert['type'] == "loadtime") echo "selected"; ?> ><?php _e('Load Time'); ?></option>
                    <option value='searchstringmissing' <?php if($alert['type'] == "searchstringmissing") echo "selected"; ?> ><?php _e('Search String Missing'); ?></option>
                </select>
            </div>
        </div>

        <div class="col-md-2" >
            <div class="form-group" id="comparison-div">
                <label for="comparison"><?php _e('Comparison'); ?></label>
                <select class="form-control select2 select2-hidden-accessible" id="comparison" name="comparison" style="width: 100%;" tabindex="-1" aria-hidden="true">
                    <option value='==' <?php if($alert['comparison'] == "==") echo "selected"; ?> >==</option>
                    <option value='>=' <?php if($alert['comparison'] == ">=") echo "selected"; ?> >>=</option>
                    <option value='<=' <?php if($alert['comparison'] == "<=") echo "selected"; ?> ><=</option>
                    <option value='>' <?php if($alert['comparison'] == ">") echo "selected"; ?> >></option>
                    <option value='<' <?php if($alert['comparison'] == "<") echo "selected"; ?> ><</option>
                    <option value='!=' <?php if($alert['comparison'] == "!=") echo "selected"; ?> >!=</option>
                </select>
            </div>
        </div>

        <div class="col-md-2">
            <div class="form-group" id="comparison_limit-div">
                <label for="comparison_limit"><?php _e('Limit/Value'); ?></label>
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
                <label for="occurrences"><?php _e('Occurrences'); ?> <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('If the problem occurs for more than this value, in a row, an incident will be opened and the selected contacts will be notified.'); ?>"></i></label>
                <input type="text" class="form-control" id="occurrences" name="occurrences" required value="2" value="<?php echo $alert['occurrences']; ?>">
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="contacts"><?php _e('Contacts'); ?> <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Contacts selected here will receive notifications for this alert.'); ?>"></i></label>
                <select class="form-control select2tags select2-hidden-accessible" id="contacts" name="contacts[]" style="width: 100%;" multiple>
                    <?php foreach ($contacts as $contact) { ?>
                        <option value='<?php echo $contact['id']; ?>' <?php if(in_array($contact['id'], $selected_contacts)) echo "selected"; ?> ><?php echo $contact['name']; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label for="repeats"><?php _e('Repeat'); ?> <i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Resend the notification if the incident is not resolved.'); ?>"></i></label>
                <select class="form-control select2 select2-hidden-accessible" id="repeats" name="repeats" style="width: 100%;" tabindex="-1" aria-hidden="true">
                    <option value='0' <?php if($alert['repeats'] == "0") echo "selected"; ?> ><?php _e('Once'); ?></option>
                    <option value='15' <?php if($alert['repeats'] == "15") echo "selected"; ?> ><?php _e('Every 15 mins'); ?></option>
                    <option value='30' <?php if($alert['repeats'] == "30") echo "selected"; ?> ><?php _e('Every 30 mins'); ?></option>
                    <option value='45' <?php if($alert['repeats'] == "45") echo "selected"; ?> ><?php _e('Every 45 mins'); ?></option>
                    <option value='60' <?php if($alert['repeats'] == "60") echo "selected"; ?> ><?php _e('Every 60 mins'); ?></option>
                    <option value='90' <?php if($alert['repeats'] == "90") echo "selected"; ?> ><?php _e('Every 90 mins'); ?></option>
                    <option value='120' <?php if($alert['repeats'] == "120") echo "selected"; ?> ><?php _e('Every 120 mins'); ?></option>
                    <option value='180' <?php if($alert['repeats'] == "180") echo "selected"; ?>><?php _e('Every 180 mins'); ?></option>
                </select>
            </div>
        </div>

    </div>


    <input type="hidden" name="id" value="<?php echo $alert['id']; ?>">
    <input type="hidden" name="websiteid" value="<?php echo $_GET['routeid']; ?>">

    <input type="hidden" name="action" value="editWebsiteAlert">
    <input type="hidden" name="route" value="<?php echo $_GET['reroute']; ?>">
    <input type="hidden" name="routeid" value="<?php echo $_GET['routeid']; ?>">
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


    $("#type").on("change", function (e) {
        var value = $("#type").val();

        if(value == "searchstringmissing") { $("#comparison-div").fadeOut(); $("#comparison_limit-div").fadeOut(); }
        if(value != "searchstringmissing") { $("#comparison-div").fadeIn(); $("#comparison_limit-div").fadeIn(); }
        //alert(value);
    });


    <?php if($alert['type'] == "searchstringmissing") { ?>
        $("#comparison-div").fadeOut();
        $("#comparison_limit-div").fadeOut();
    <?php } ?>

</script>
