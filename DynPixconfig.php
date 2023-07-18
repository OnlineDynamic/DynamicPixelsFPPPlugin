
<?
function returnIfExists($json, $setting) {
    if ($json == null) {
        return "";
    }
    if (array_key_exists($setting, $json)) {
        return $json[$setting];
    }
    return "";
}

function convertAndGetSettings() {
    global $settings;
        
    $cfgFile = $settings['configDirectory'] . "/plugin.dynamicpixels.json";
    if (file_exists($cfgFile)) {
        $j = file_get_contents($cfgFile);
        $json = json_decode($j, true);
        return $json;
    }
    $j = "[]";
    return json_decode($j, true);
}

$pluginJson = convertAndGetSettings();
?>


<div id="global" class="settings">
<fieldset>
<legend>Dynamic Pixels Controller(Cape) Config</legend>

<script>

var dynamicpixelsConfig = <? echo json_encode($pluginJson, JSON_PRETTY_PRINT); ?>;


var uniqueId = 1;
var modelOptions = "";
function AdddynamicpixelsItem(type) {
    var id = $("#dynamicpixelsTableBody > tr").length + 1;
    var html = "<tr class='fppTableRow";
    if (id % 2 != 0) {
        html += " oddRow'";
    }
    html += "'><td class='colNumber rowNumber'>" + id + ".</td><td><span style='display: none;' class='uniqueId'>" + uniqueId + "</span></td>";
    html += "<td><input type='text' minlength='7' maxlength='15' size='15' pattern='^((\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$' class='ipaddress' /></td>";
    html += "<td><input type='number' value='1' min='1' max='10000000' class='startchan' />";
    html += "<td><select class='devicetype'>";
    html += "<option value='PSU Control'";
    if(type == 'PSU Control') {html += " selected ";}
    //html += ">Light</option><option value='switch'";
    //if(type == 'switch') {html += " selected ";}
    html += ">Switch/Plug</option></select>";
    html += "<td><input type='number' value='0' min='0' max='10' class='plugnum' />";
    html += "</tr>";
    //selected
    $("#dynamicpixelsTableBody").append(html);

    newRow = $('#dynamicpixelsTableBody > tr').last();
    $('#dynamicpixelsTableBody > tr').removeClass('selectedEntry');
    DisableButtonClass('deleteEventButton');
    uniqueId++;

    return newRow;
}

function SavedynamicpixelsItem(row) {
    var ip = $(row).find('.ipaddress').val();
    var startchan = parseInt($(row).find('.startchan').val(),10);

    var plugnum = parseInt($(row).find('.plugnum').val(),10);
	var devicetype = $(row).find('.devicetype').val();

    var json = {
        "ip": ip,
        "startchannel": startchan,
        "devicetype": devicetype,
        "plugnumber": plugnum
    };
    return json;
}

function SavedynamicpixelsItems() {
    var dynamicpixelsConfig = [];
    var i = 0;
    $("#dynamicpixelsTableBody > tr").each(function() {
        dynamicpixelsConfig[i++] = SavedynamicpixelsItem(this);
    });
    
    var data = JSON.stringify(dynamicpixelsConfig);
    $.ajax({
        type: "POST",
        url: 'fppjson.php?command=setPluginJSON&plugin=dynamicpixels',
        dataType: 'json',
        async: false,
        data: data,
        processData: false,
        contentType: 'application/json',
        success: function (data) {
           SetRestartFlag(2);
        }
    });
}


function RenumberRows() {
    var id = 1;
    $('#dynamicpixelsTableBody > tr').each(function() {
        $(this).find('.rowNumber').html('' + id++ + '.');
        $(this).removeClass('oddRow');

        if (id % 2 != 0) {
            $(this).addClass('oddRow');
        }
    });
}
function RemovedynamicpixelsItem() {
    if ($('#dynamicpixelsTableBody').find('.selectedEntry').length) {
        $('#dynamicpixelsTableBody').find('.selectedEntry').remove();
        RenumberRows();
    }
    DisableButtonClass('deleteEventButton');
}


$(document).ready(function() {
                  
    $('#dynamicpixelsTableBody').sortable({
        update: function(event, ui) {
            RenumberRows();
        },
        item: '> tr',
        scroll: true
    }).disableSelection();

    $('#dynamicpixelsTableBody').on('mousedown', 'tr', function(event,ui){
        $('#dynamicpixelsTableBody tr').removeClass('selectedEntry');
        $(this).addClass('selectedEntry');
        EnableButtonClass('deleteEventButton');
    });
});

</script>
<div>
<table border=0>
<tr><td colspan='2'>
        <input type="button" value="Save" class="buttons genericButton" onclick="SavedynamicpixelsItems();">
        <input type="button" value="Add" class="buttons genericButton" onclick="AdddynamicpixelsItem('light');">
        <input id="delButton" type="button" value="Delete" class="deleteEventButton disableButtons genericButton" onclick="RemovedynamicpixelsItem();">
    </td>
</tr>
</table>

<div class='fppTableWrapper fppTableWrapperAsTable'>
<div class='fppTableContents'>
<table class="fppTable" id="dynamicpixelsTable"  width='100%'>
<thead><tr class="fppTableHeader"><th>#</th><th></th><th>Item Type</th><th>Item Name</th><th>Max Current</th></tr></thead>
<tbody id='dynamicpixelsTableBody'>
</tbody>
</table>
</div>

</div>
<div>
<p>
<p>
</div>
</div>
<script>

$.each(dynamicpixelsConfig, function( key, val ) {
    var row = AdddynamicpixelsItem(val["devicetype"]);
    $(row).find('.ipaddress').val(val["ip"]);
    $(row).find('.startchan').val(val["startchannel"]);
    $(row).find('.plugnum').val(val["plugnumber"]);
});
</script>

</fieldset>
</div>
