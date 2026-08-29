<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title"><?php _e('Add Service'); ?></h4>
</div>

<div class="modal-body">

    <div class="row">
        <div class="col-md-8">
            <div class="form-group">
                <label for="name"><?php _e('Name'); ?> *</label>
                <input type="text" class="form-control" id="name" name="name" required placeholder="<?php _e('Name, hostname or other for easy identification'); ?>">
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label for="groupid"><?php _e('Group'); ?></label>
                <select class="form-control select2 select2-hidden-accessible" id="groupid" name="groupid" style="width: 100%;" tabindex="-1" aria-hidden="true" required>
                    <?php foreach ($groups as $group) { if(!checkGroup($group['id'])) continue; ?>
                        <option value='<?php echo $group['id']; ?>'><?php echo $group['name']; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>


        <div class="col-md-5">
            <div class="form-group">
                <label for="common"><?php _e('Common Services'); ?></label>
                <select class="form-control select2 select2-hidden-accessible" id="common" name="common" style="width: 100%;" tabindex="-1" aria-hidden="true">
                    <option></option>
                    <optgroup label="<?php _e('Services'); ?>">
                        <option value='ftp'><?php _e('FTP (Monitor a FTP server)'); ?></option>
                        <option value='ssh'><?php _e('SSH (Monitor a SSH server)'); ?></option>
                        <option value='rdp'><?php _e('RDP (Monitor a RDP server)'); ?></option>
                        <option value='http'><?php _e('HTTP (Monitor a HTTP server)'); ?></option>
                        <option value='https'><?php _e('HTTPS (Monitor a HTTPS server)'); ?></option>
                        <option value='dns'><?php _e('DNS (Monitor a DNS server)'); ?></option>
                    </optgroup>

                    <optgroup label="<?php _e('Email'); ?>">
                        <option value='smtp'><?php _e('SMTP (Monitor a SMTP server)'); ?></option>
                        <option value='pop3'><?php _e('POP3 (Monitor a POP3 server)'); ?></option>
                        <option value='imap'><?php _e('IMAP (Monitor a IMAP server)'); ?></option>
                    </optgroup>

                    <optgroup label="<?php _e('Others'); ?>">
                        <option value='tcp'><?php _e('TCP (Monitor a TCP port)'); ?></option>
                        <option value='udp'><?php _e('UDP (Monitor a UDP port, deprecated)'); ?></option>
                        <option value='icmp'><?php _e('ICMP (Ping)'); ?></option>
                        <option value='dnslookup'><?php _e('DNS Lookup'); ?></option>
                        <option value='blacklist'><?php _e('IP Blacklist (Check IP Blacklists for a particular IP)'); ?></option>
                        <option value='callback'><?php _e('Callback'); ?></option>
                    </optgroup>


                </select>
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label for="type"><?php _e('Type'); ?> *</label>
                <select class="form-control select2 select2-hidden-accessible" id="type" name="type" style="width: 100%;" tabindex="-1" aria-hidden="true" required>
                    <option value='tcp'><?php _e('TCP'); ?></option>
                    <option value='udp'><?php _e('UDP (Deprecated)'); ?></option>
                    <option value='icmp'><?php _e('ICMP (Ping)'); ?></option>
                    <option value='dns'><?php _e('DNS'); ?></option>
                    <option value='blacklist'><?php _e('IP Blacklist'); ?></option>
                    <option value='callback'><?php _e('Callback'); ?></option>
                </select>
            </div>
        </div>

        <div class="col-md-2" id="port-div">
            <div class="form-group">
                <label for="port"><?php _e('Port'); ?></label>
                <input type="text" class="form-control" id="port" name="port">
            </div>
        </div>

        <div class="col-md-2" id="timeout-div">
            <div class="form-group">
                <label for="timeout"><?php _e('Timeout (s)'); ?> *</label>
                <input type="text" class="form-control" id="timeout" name="timeout" required value="<?php echo getConfigValue('check_timeout'); ?>">
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="host" id="host-label"><?php _e('Host'); ?> *</label>
                <input type="text" class="form-control" id="host" name="host" required>
            </div>
        </div>

        <div class="col-md-3" id="send-div">
            <div class="form-group">
                <label for="send" id="send-label"><?php _e('Send String'); ?></label>
                <input type="text" class="form-control" id="send" name="send" placeholder="">
            </div>
        </div>

        <div class="col-md-3" id="expect-div">
            <div class="form-group">
                <label for="expect" id="expect-label"><?php _e('Expected Response'); ?> *</label>
                <input type="text" class="form-control" id="expect" name="expect" placeholder="">
            </div>
        </div>

    </div>

    <input type="hidden" name="on_map" value="0">


    <div class="row" id="more-form" style="display:none">

        <?php if($isGoogleMaps) { ?>
        <div class="col-md-12">
            <div class="form-group">
                <label for="lat"><?php _e('Search Address'); ?></label>
                <input type="text" class="form-control" id="autocomplete" placeholder="<?php _e('Enter address to autofill coordinates'); ?>">
            </div>
        </div>
        <?php } ?>

        <div class="col-md-6">
            <div class="form-group">
                <label for="lat"><?php _e('Latitude'); ?></label>
                <input type="text" class="form-control" id="lat" name="lat" placeholder="<?php _e('Latitude'); ?>" data-validation="number" data-validation-optional="true" data-validation-allowing="range[-90.0;90.0],float,negative" data-validation-error-msg="<?php _e('Invalid Latitude Value'); ?>">
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="lng"><?php _e('Longitude'); ?></label>
                <input type="text" class="form-control" id="lng" name="lng" placeholder="<?php _e('Longitude'); ?>" data-validation="number" data-validation-optional="true" data-validation-allowing="range[-180.0;180.0],float,negative" data-validation-error-msg="<?php _e('Invalid Longitude Value'); ?>">
            </div>
        </div>

    </div>

    <?php if($isGoogleMaps) { ?>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="on_map" value="1" id="on_map"> <?php _e('Show on map'); ?>
                    </label>
                </div>

            </div>
        </div>
    </div>
    <?php } ?>



    <input type="hidden" name="action" value="addCheck">
    <input type="hidden" name="route" value="<?php echo $_GET['reroute']; ?>">
    <input type="hidden" name="routeid" value="">
    <input type="hidden" name="section" value="">
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-flat btn-default" data-dismiss="modal"><i class="fa fa-times"></i> <?php _e('Cancel'); ?></button>
    <button type="submit" class="btn btn-flat btn-primary"><i class="fa fa-check"></i> <?php _e('Add Service'); ?></button>
</div>


<script type="text/javascript">

	$(".select2").select2({
        placeholder: "<?php _e('Please select'); ?>"
    });


    $("#common").on("change", function (e) {
        var value = $("#common").val();

        if(value == "ftp") { $("#type").val("tcp").trigger("change"); $("#port").val("21"); }
        if(value == "ssh") { $("#type").val("tcp").trigger("change"); $("#port").val("22"); }
        if(value == "rdp") { $("#type").val("tcp").trigger("change"); $("#port").val("3389"); }
        if(value == "http") { $("#type").val("tcp").trigger("change"); $("#port").val("80"); }
        if(value == "https") { $("#type").val("tcp").trigger("change"); $("#port").val("443"); }
        if(value == "dns") { $("#type").val("tcp").trigger("change"); $("#port").val("53"); }
        if(value == "smtp") { $("#type").val("tcp").trigger("change"); $("#port").val("25"); }
        if(value == "pop3") { $("#type").val("tcp").trigger("change"); $("#port").val("110"); }
        if(value == "imap") { $("#type").val("tcp").trigger("change"); $("#port").val("143"); }
        if(value == "tcp") { $("#type").val("tcp").trigger("change"); }
        if(value == "udp") { $("#type").val("udp").trigger("change"); }
        if(value == "icmp") { $("#type").val("icmp").trigger("change"); }
        if(value == "dnslookup") { $("#type").val("dns").trigger("change"); }
        if(value == "blacklist") { $("#type").val("blacklist").trigger("change"); }
        if(value == "callback") { $("#type").val("callback").trigger("change"); }
        //alert(value);
    });

    $("#type").on("change", function (e) {
        var value = $("#type").val();

        if(value == "tcp") {
            $("#port-div").fadeIn();
            $("#timeout-div").fadeIn();
            $("#expect-div").fadeOut();
            $("#send-div").fadeOut();
            $("#host").val("");
            $("#host-label").text("<?php _e('Hostname or IP Address *'); ?>");
        }
        if(value == "udp") {
            $("#port-div").fadeIn();
            $("#timeout-div").fadeIn();
            $("#expect-div").fadeOut();
            $("#send-div").fadeOut();
            $("#host").val("");
            $("#host-label").text("<?php _e('Hostname or IP Address *'); ?>");
        }

        if(value == "icmp") {
            $("#port").val("");
            $("#timeout-div").fadeIn();
            $("#port-div").fadeOut();
            $("#send-div").fadeOut();
            $("#expect-div").fadeOut();
            $("#host").val("");
            $("#host-label").text("<?php _e('Hostname or IP Address *'); ?>");
        }

        if(value == "dns") {
            $("#port").val("");
            $("#send-div").fadeIn();
            $("#expect-div").fadeIn();
            $("#timeout-div").fadeIn();
            $("#port-div").fadeOut();
            $("#host").val("");
            $("#send-label").text("<?php _e('DNS Server *'); ?>");
            $("#host-label").text("<?php _e('Query *'); ?>");
        }

        if(value == "blacklist") {
            $("#port").val("");
            $("#port-div").fadeOut();
            $("#send-div").fadeOut();
            $("#expect-div").fadeOut();
            $("#timeout-div").fadeOut();
            $("#host").val("");
            $("#host-label").text("<?php _e('IP Address *'); ?>");
        }

        if(value == "callback") {
            $("#port").val("");
            $("#port-div").fadeOut();
            $("#send-div").fadeOut();
            $("#expect-div").fadeOut();
            $("#timeout-div").fadeOut();
            $("#host").val(makekey());
            $("#host-label").text("<?php _e('Secret key *'); ?>");

        }

        //alert(value);
    });

    $("#expect-div").fadeOut();
    $("#send-div").fadeOut();



    function makekey() {
      var text = "";
      var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

      for (var i = 0; i < 15; i++)
        text += possible.charAt(Math.floor(Math.random() * possible.length));

      return text;
    }


</script>



<?php if($isGoogleMaps) { ?>
    <script>

    $.validate({
      decimalSeparator : '.'
    });

    $(document).ready(function(){
        $("#on_map").change(function(){
            if(this.checked) { $("#more-form").slideDown(); }
            else { $("#more-form").slideUp(); }
        });
    });

        var autocomplete;

        function initAutocomplete() {
            // Create the autocomplete object, restricting the search to geographical
            // location types.
            autocomplete = new google.maps.places.Autocomplete(
            /** @type {!HTMLInputElement} */(document.getElementById('autocomplete')),
            {types: ['geocode']});

            // When the user selects an address from the dropdown, populate the address
            // fields in the form.
            autocomplete.addListener('place_changed', fillInAddress);
        }

        function fillInAddress() {
            // Get the place details from the autocomplete object.
            var place = autocomplete.getPlace();

            // Populate values
            $( "#lat" ).val( place.geometry.location.lat() );
            $( "#lng" ).val( place.geometry.location.lng() );
        }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo getConfigValue("google_maps_api_key"); ?>&libraries=places&callback=initAutocomplete" async defer></script>
<?php } ?>
