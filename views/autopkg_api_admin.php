<?php $this->view('partials/head'); ?>

<div class="container">
    <div class="col-lg-9">
        <h3>AutoPKG API&nbsp;&nbsp;<button id="ForceRebuildButton" class="btn btn-default btn-xs"></button></h3>
        <div id="autopkg_api-status"></div>
        <div id="autopkg_api-msg" data-i18n="listing.loading" class="col-lg-9"></div>
    </div>
</div>

<script>
$(document).on('appReady', function(){

    // Give button its text
    $('#ForceRebuildButton')
        .append(i18n.t("autopkg_api.manual_rebuild"))

    autopkg_api_json()

    // Force rebuild button click function
    $('#ForceRebuildButton').click(function (e) {
        $.getJSON(appUrl + '/module/autopkg_api/force_autopkg_rebuild/', function (processdata) {
            $('#autopkg_api-status')
                    .empty()
            autopkg_api_json()
        });
    });
});


// Load the autopkg_api JSON
function autopkg_api_json(){
       $.getJSON( appUrl + '/module/autopkg_api/get_autopkg_data_view', function(d) {

        // Check if we have data
        if( ! d || d.length == 0){
            $('#autopkg_api-msg').text(i18n.t('autopkg_api.no_data'));
        } else {

            // Hide
            $('#autopkg_api-msg').text('');
            $('#autopkg_api-msg').removeClass('hide');

            var skipThese = ['script_hash','rebuild_pkg'];

            // Generate rows from data
            var rows = ''

            for (var prop in d){
                // Skip skipThese
                if(skipThese.indexOf(prop) == -1){
                    // Do nothing for empty values to blank them
                    if ((d[prop] == '' || d[prop] == null) && d[prop] !== 0){
                        rows = rows

                    // Format date 
                    } else if ((prop == "last_rebuilt" || prop == "last_polled") && d[prop] > 10){
                        var date = new Date(d[prop] * 1000);
                        rows = rows + '<tr><th style="width:300px;">'+i18n.t('autopkg_api.'+prop)+'</th><td>'+moment(date).format('llll')+'  -  '+moment(date).fromNow()+'</td></tr>';

                    } else if ((prop == 'rebuild_reason')){
                        rows = rows + '<tr><th>'+i18n.t('autopkg_api.'+prop)+'</th><td><span class="label label-info">'+d[prop]+'</span></td></tr>';

                    } else if ((prop == 'rebuild_pkg') && d[prop] == "TRUE"){
                        rows = rows + '<tr><th>'+i18n.t('autopkg_api.'+prop)+'</th><td>'+i18n.t('yes')+'</td></tr>';

                    } else if ((prop == 'rebuild_pkg') && d[prop] == "FALSE"){
                        rows = rows + '<tr><th>'+i18n.t('autopkg_api.'+prop)+'</th><td>'+i18n.t('no')+'</td></tr>';

                    } else if (prop == "modules_env" && d[prop]){
                        rows = rows + '<tr><th>'+i18n.t('autopkg_api.'+prop)+'</th><td>'+d[prop].replaceAll(",", ", ")+'</td></tr>';

                    // Else, build out rows from entries
                    } else {
                        rows = rows + '<tr><th>'+i18n.t('autopkg_api.'+prop)+'</th><td>'+d[prop]+'</td></tr>';
                    }
                }
            }

            if (rows != ''){
                $('#autopkg_api-status')
                    .append($('<div style="max-width:1200px;">')
                        .append($('<table>')
                            .addClass('table table-striped table-condensed')
                            .append($('<tbody>')
                                .append(rows))))
            }
        }
    });
}

</script>

<?php $this->view('partials/foot'); ?>
