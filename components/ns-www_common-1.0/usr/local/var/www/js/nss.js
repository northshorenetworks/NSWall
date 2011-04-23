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

function substr_count (haystack, needle, offset, length)
{
    var pos = 0, cnt = 0;

    haystack += '';
    needle += '';
    if (isNaN(offset)) {offset = 0;}
    if (isNaN(length)) {length = 0;}
    offset--;

    while ((offset = haystack.indexOf(needle, offset+1)) != -1){
        if (length > 0 && (offset+needle.length) > length){
            return false;
        } else{
            cnt++;
        }
    }

    return cnt;
}

// test_ipv6
//
// Test if the input is a valid ipv6 address. Javascript version of an original PHP function.
//
// Ported from: http://crisp.tweakblogs.net/blog/2031

function test_ipv6(ip)
{
  // Test for empty address
  if (ip.length<3)
  {
	return ip == "::";
  }

  // Check if part is in IPv4 format
  if (ip.indexOf('.')>0)
  {
        lastcolon = ip.lastIndexOf(':');

        if (!(lastcolon && test_ipv4(ip.substr(lastcolon + 1))))
            return false;

        // replace IPv4 part with dummy
        ip = ip.substr(0, lastcolon) + ':0:0';
  } 

  // Check uncompressed
  if (ip.indexOf('::')<0)
  {
    var match = ip.match(/^(?:[a-f0-9]{1,4}:){7}[a-f0-9]{1,4}$/i);
    return match != null;
  }

  // Check colon-count for compressed format
  if (substr_count(ip, ':'))
  {
    var match = ip.match(/^(?::|(?:[a-f0-9]{1,4}:)+):(?:(?:[a-f0-9]{1,4}:)*[a-f0-9]{1,4})?$/i);
    return match != null;
  } 

  // Not a valid IPv6 address
  return false;
}



function displayProcessingDiv () {
	$("#save_config").html('<center>Saving Configuration File<br><br><img src="images/ajax-loader.gif" height="25" width="25" name="spinner">');
    $(".ui-dialog-titlebar").css('display','block');
    $('#save_config').dialog('open');
}
