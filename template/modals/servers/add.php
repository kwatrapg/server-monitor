<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title"><?php _e('Add Server'); ?></h4>
</div>

<div class="modal-body">

    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="name"><?php _e('Name'); ?> *</label>
                <input type="text" class="form-control" id="name" name="name" required>
                <p class="help-block"><?php _e('Hostname, IP Address or other for easy identification.'); ?></p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="groupid"><?php _e('Group'); ?></label>
                <select class="form-control select2 select2-hidden-accessible" id="groupid" name="groupid" style="width: 100%;" tabindex="-1" aria-hidden="true" required>
                    <?php foreach ($groups as $group) { if(!checkGroup($group['id'])) continue; ?>
                        <option value='<?php echo $group['id']; ?>'><?php echo $group['name']; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="type"><?php _e('Platform'); ?></label>
                <select class="form-control select2 select2-hidden-accessible" id="type" name="type" style="width: 100%;" tabindex="-1" aria-hidden="true">
                    <option value='linux'><?php _e('Linux'); ?></option>
                    <option value='windows'><?php _e('Windows'); ?> <?php _e('(BETA)'); ?></option>
                </select>
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
                        <input type="checkbox" name="on_map" id="on_map" value="1"> <?php _e('Show on map'); ?>
                    </label>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>



    <input type="hidden" name="action" value="addServer">
    <input type="hidden" name="route" value="<?php echo $_GET['reroute']; ?>">
    <input type="hidden" name="routeid" value="">
    <input type="hidden" name="section" value="">
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-flat btn-default" data-dismiss="modal"><i class="fa fa-times"></i> <?php _e('Cancel'); ?></button>
    <button type="submit" class="btn btn-flat btn-primary"><i class="fa fa-check"></i> <?php _e('Add Server'); ?></button>
</div>


<script type="text/javascript">
		$(".select2").select2();
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
