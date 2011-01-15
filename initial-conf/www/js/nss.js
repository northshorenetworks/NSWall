var num_regex=/^\d+$/;

function verifyIP (IPvalue) {
    errorString = "";
    theName = "IPaddress";
    var ipPattern = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/;
    var ipArray = IPvalue.split('.');

    if (IPvalue.match(ipPattern) == null)
        errorString = errorString + theName + ': '+IPvalue+' is not a valid IP address.';
    if (ipArray.length != 4)
        errorString = errorString + theName + ': '+IPvalue+' is not a valid IP address.';
    if (IPvalue == "0.0.0.0")
        errorString = errorString + theName + ': '+IPvalue+' is not a valid IP address.';
    if (IPvalue == "255.255.255.255")
        errorString = errorString + theName + ': '+IPvalue+' is not a valid IP address.';

    for (i = 0; i < 4; i++) {
        var thisSegment = ipArray[i];
        if (thisSegment > 255) {
            errorString = errorString + theName + ': '+IPvalue+' is not a valid IP address.';
            break;
        }       
    }
    
    extensionLength = 3;
    if (errorString == "") {
        return 0;
    }
    else {
        return 1;
    }
}

function validateRange (Value, Low, High) {
	if (Value < Low || Value > High) {
		alert('Value of: '+Value+' is not in the range: '+Low+'-'+High);
                return 1;
	} else {
                return 0;
        }
}

function displayProcessingDiv () {
	$("#save_config").html('<center>Saving Configuration File<br><br><img src="images/ajax-loader.gif" height="25" width="25" name="spinner">');
    $(".ui-dialog-titlebar").css('display','block');
    $('#save_config').dialog('open');
}
