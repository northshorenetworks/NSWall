<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>Demo Javascript Modal Popups v0.1</title>
    <link href="SyntaxHighlighter.css" rel="stylesheet" type="text/css" />
    <script type="text/javascript" src="shCore.js" language="javascript"></script>
    <script type="text/javascript" src="shBrushJScript.js" language="javascript"></script>
    <script type="text/javascript" src="ModalPopups.js" language="javascript"></script>
</head>
<body>
    <div style="width: 750px; font-family: Verdana; font-size: 9pt;">
    <p><a href="http://www.modalpopups.com">Take me back to ModalPopups.com</a></p>

    <h1>Javascript Modal Popups demo page.</h1>
    <p>Javascript Modal Popups offers you a free javascript with commonly used modal popups. No CSS. No images. Auto size body. Very easy to use.</p>
    <p>This page show demo javascript code for functions: Alert, Confirm, YesNoCancel, Prompt, Indicator and Custom.</p>
    <p>It shows you the options to customize the look.<br />
    In order for all your modals to have the same behaviour you can set defaults with the <b>ModalPopups.SetDefaults</b> function.</p>
    <p>Defaults:<br />
    <code>
shadow: true,
shadowSize: 5,
shadowColor: "#333333",
backgroundColor: "#CCCCCC",
borderColor: "#999999",
titleBackColor: "#C1D2E7",
titleFontColor: "#15428B",
popupBackColor: "#C7D6E9",
popupFontColor: "black",
footerBackColor: "#C1D2E7",
footerFontColor: "#15428B",
okButtonText: "OK",
yesButtonText: "Yes",
noButtonText: "No",
cancelButtonText: "Cancel",
fontFamily: "Verdana,Arial",
fontSize: "9pt"
    </code>
    </p>
    <p>To set Dutch Language buttons throughout your application, just create a .js and include it in your page (below the include of ModalPopups.js)<br />
    <code>
ModalPopups.SetDefaults( {
    yesButtonText: "Ja",
    noButtonText: "Nee",
    okButtonText: "OK",
    cancelButtonText: "Annuleren"
    }
);
    </code>
    </p>
    <p><em>This page uses syntax highlighter.</em></p>
    <hr />

    <a href="javascript:ModalPopupsAlert1();">Alert, example 1</a><br />
    <pre id='code' class='javascript'>
function ModalPopupsAlert1() {
    ModalPopups.Alert("jsAlert1",
        "Save address information",
        "&lt;div style='padding:25px;'&gt;The address information has been saved succesfully&lt;br/&gt;" +
        "to the customer database...&lt;br/&gt;&lt;/div&gt;", 
        {
            okButtonText: "Close"
        }
    );
}</pre>
    <hr />
    
<a href="javascript:ModalPopupsAlert2();">Alert, example 2</a><br />
    <pre id='code' class='javascript'>
function ModalPopupsAlert2() {
    ModalPopups.Alert("jsAlert2",
        "Save address information",
        "&lt;div style='padding:25px;'&gt;The address information has been saved succesfully&lt;br/&gt;" +
        "to the customer database...&lt;br/&gt;&lt;/div&gt;",
        {
            okButtonText: "Close",
            backgroundColor: "Yellow",
            shadowSize: 15,
            fontFamily: "Courier New",
            fontSize: "9pt"
        }
    );
}</pre>
    <hr />    
    
<a href="javascript:ModalPopupsAlert3();">Alert, example 3</a><br />
    <pre id='code' class='javascript'>
function ModalPopupsAlert3() {
    ModalPopups.Alert("jsAlert3",
        "Save address information",
        "&lt;div style='padding:25px;'&gt;The address information has been saved succesfully&lt;br/&gt;" +
        "to the customer database...&lt;br/&gt;&lt;/div&gt;",
        {
            titleBackColor: "#A1B376",
            titleFontColor: "white",
            popupBackColor: "#E9E8CF",
            popupFontColor: "black",
            footerBackColor: "#A1B376",
            footerFontColor: "white"
        }
    );
}</pre>
    <hr />
    
    
    
    <a href="javascript:ModalPopupsConfirm();">Confirm</a><br />
    <pre id='code' class='javascript'>
function ModalPopupsConfirm() {
    ModalPopups.Confirm("idConfirm1",
        "Confirm delete address information",
        "&lt;div style='padding: 25px;'&gt;You are about to delete this address information.&lt;br/&gt;&lt;br/&gt;&lt;b&gt;Are you sure?&lt;/b&gt;&lt;/div&gt;", 
        {
            yesButtonText: "Yes",
            noButtonText: "No",
            onYes: "ModalPopupsConfirmYes()",
            onNo: "ModalPopupsConfirmNo()"
        }
    );
}
function ModalPopupsConfirmYes() {
    alert('You pressed Yes');
    ModalPopups.Close("idConfirm1");
}
function ModalPopupsConfirmNo() {
    alert('You pressed No');
    ModalPopups.Cancel("idConfirm1");
}</pre>
    <hr />
    
    <a href="javascript:ModalPopupsYesNoCancel();">YesNoCancel</a><br />
    <pre id='code' class='javascript'>
function ModalPopupsYesNoCancel() {
    ModalPopups.YesNoCancel("idYesNoCancel1",
        "Confirm close of document",
        "&lt;div style='padding: 25px;'&gt;&lt;p&gt;You are about to close this document.&lt;br/&gt;" + 
        "If you don't save this document, all information will be lost.&lt;/p&gt;" + 
        "&lt;p&gt;&lt;b&gt;Close document?&lt;/b&gt;&lt;/p&gt;&lt;/div&gt;", 
        {
            onYes: "ModalPopupsYesNoCancelYes()",
            onNo: "ModalPopupsYesNoCancelNo()",
            onCancel: "ModalPopupsYesNoCancelCancel()"
        }
    );
}
function ModalPopupsYesNoCancelYes() {
    alert('You pressed Yes');
    ModalPopups.Close("idYesNoCancel1");
}
function ModalPopupsYesNoCancelNo() {
    alert('You pressed No');
    ModalPopups.Cancel("idYesNoCancel1");
}
function ModalPopupsYesNoCancelCancel() {
    alert('You pressed Cancel');
    ModalPopups.Cancel("idYesNoCancel1");
}
</pre>
    <hr />
    
    <a href="javascript:ModalPopupsPrompt();">Prompt</a><br />
    <pre id='code' class='javascript'>
function ModalPopupsPrompt() {
    ModalPopups.Prompt("idPrompt1",
        "Prompt",
        "Please enter your ID number",  
        {
            width: 300,
            height: 100,
            onOk: "ModalPopupsPromptOk()",
            onCancel: "ModalPopupsPromptCancel()"
        }
    );
}
function ModalPopupsPromptOk()
{
    if(ModalPopups.GetPromptInput("idPrompt1").value == "") {
        ModalPopups.GetPromptInput("idPrompt1").focus();
        return;
    }
    alert("You pressed OK\nValue: " + ModalPopups.GetPromptInput("idPrompt1").value);
    ModalPopups.Close("idPrompt1");
}
function ModalPopupsPromptCancel() {
    alert("You pressed Cancel");
    ModalPopups.Cancel("idPrompt1");
}
</pre>
    <hr />

    <a href="javascript:ModalPopupsIndicator();">Indicator, default</a><br />
    <pre id='code' class='javascript'>
function ModalPopupsIndicator() {
    ModalPopups.Indicator("idIndicator1",
        "Please wait",
        "Saving address information... <br/>" + 
        "This may take 3 seconds.", 
        {
            width: 300,
            height: 100
        }
    );
            
    setTimeout('ModalPopups.Close(\"idIndicator1\");', 3000);
}
</pre>
    <hr />
    
    <a href="javascript:ModalPopupsIndicator2();">Indicator, with custom indicator image</a><br />
<pre id='code' class='javascript'>
function ModalPopupsIndicator2() {
    ModalPopups.Indicator("idIndicator2",
        "Please wait",
        "&lt;div style=''&gt;" + 
        "&lt;div style='float:left;'&gt;&lt;img src='spinner.gif'&gt;&lt;/div&gt;" + 
        "&lt;div style='float:left; padding-left:10px;'&gt;" + 
        "Saving address information... &lt;br/&gt;" + 
        "This may take 3 seconds." + 
        "&lt;/div&gt;", 
        {
            width: 300,
            height: 100
        }
    );
            
    setTimeout('ModalPopups.Close(\"idIndicator2\");', 3000);
}
</pre>
    <hr />
    
    <a href="javascript:ModalPopupsCustom1();">Custom (Customized)</a><br />
<pre id='code' class='javascript'>
function ModalPopupsCustom1() {
    ModalPopups.Custom("idCustom1",
        "Enter address information",
        "&lt;div style='padding: 25px;'&gt;" + 
        "&lt;table&gt;" + 
        "&lt;tr&gt;&lt;td&gt;Name&lt;/td&gt;&lt;td&gt;&lt;input type=text id='inputCustom1Name' style='width:250px;'&gt;&lt;/td&gt;&lt;/tr&gt;" + 
        "&lt;tr&gt;&lt;td&gt;Address&lt;/td&gt;&lt;td&gt;&lt;input type=text id='inputCustom1Address' style='width:250px;'&gt;&lt;/td&gt;&lt;/tr&gt;" + 
        "&lt;tr&gt;&lt;td&gt;ZipCode&lt;/td&gt;&lt;td&gt;&lt;input type=text id='inputCustom1ZipCode' style='width:100px;'&gt;&lt;/td&gt;&lt;/tr&gt;" + 
        "&lt;tr&gt;&lt;td&gt;City&lt;/td&gt;&lt;td&gt;&lt;input type=text id='inputCustom1City' style='width:250px;'&gt;&lt;/td&gt;&lt;/tr&gt;" + 
        "&lt;tr&gt;&lt;td&gt;Phone&lt;/td&gt;&lt;td&gt;&lt;input type=text id='inputCustom1Phone' style='width:250px;'&gt;&lt;/td&gt;&lt;/tr&gt;" + 
        "&lt;tr&gt;&lt;td&gt;E-Mail&lt;/td&gt;&lt;td&gt;&lt;input type=text id='inputCustom1EMail' style='width:250px;'&gt;&lt;/td&gt;&lt;/tr&gt;" + 
        "&lt;/table&gt;" + 
        "&lt;/div&gt;", 
        {
            width: 500,
            buttons: "ok,cancel",
            okButtonText: "Save",
            cancelButtonText: "Cancel",
            onOk: "ModalPopupsCustom1Save()",
            onCancel: "ModalPopupsCustom1Cancel()"
        }
    );
            
    ModalPopups.GetCustomControl("inputCustom1Name").focus(); 
}
function ModalPopupsCustom1Save() {
    var custom1Name = ModalPopups.GetCustomControl("inputCustom1Name"); 
    if(custom1Name.value == "") {
        alert("Please submit a name to this form");
        custom1Name.focus();
    }
    else {
        alert("Your name is: " + custom1Name.value);
        ModalPopups.Close("idCustom1");
    }
}

function ModalPopupsCustom1Cancel() {
    alert('You pressed Cancel');
    ModalPopups.Cancel("idCustom1");
}
</pre>
    <hr />

    <a href="javascript:ModalPopupsAlert99();">Alert (loadFile: "TextFile.txt")</a><br />
<pre id='code' class='javascript'>
function ModalPopupsAlert99() {
    ModalPopups.Alert("jsAlert99",
        "Save address information",
        "", {
            okButtonText: "Close",
            loadTextFile: "TextFile.txt",
            width: 500,
            height: 300});
}   
</pre>
    <hr />    
        
    <p><a href="http://www.modalpopups.com">Take me back to ModalPopups.com</a></p>
    
    </div>
    

    <script type="text/javascript" language="javascript">
        function ModalPopupsAlert1() {
            ModalPopups.Alert("jsAlert1",
                "Save address information",
                "<div style='padding:25px;'>The address information has been saved succesfully<br/>" + 
                "to the customer database...<br/></div>", 
                {
                    okButtonText: "Close"
                }
            );
        }    
        
        function ModalPopupsAlert99() {
            ModalPopups.Alert("jsAlert99",
                "Save address information",
                "", 
                {
                    okButtonText: "Close",
                    loadTextFile: "TextFile.txt",
                    width: 500,
                    height: 300
                }
            );
        }   
        
        function ModalPopupsAlert3() {
            ModalPopups.Alert("jsAlert3",
                "Save address information",
                "<div style='padding:25px;'>The address information has been saved succesfully<br/>" + 
                "to the customer database...<br/></div>", 
                {
                    titleBackColor: "#A1B376",
                    titleFontColor: "white",
                    popupBackColor: "#E9E8CF",
                    popupFontColor: "black",
                    footerBackColor: "#A1B376",
                    footerFontColor: "white"                 
                }
            );
        }    
         
        function ModalPopupsAlert2() {
            ModalPopups.Alert("jsAlert2",
                "Save address information",
                "<div style='padding:25px;'>The address information has been saved succesfully<br/>" + 
                "to the customer database...<br/></div>", 
                {
                    shadowSize: 15,
                    okButtonText: "Close",
                    backgroundColor: "Yellow",
                    fontFamily: "Courier New",
                    fontSize: "9pt"
                }
            );
        }
        
        function ModalPopupsConfirm() {
            ModalPopups.Confirm("idConfirm1",
                "Confirm delete address information",
                "<div style='padding: 25px;'>You are about to delete this address information.<br/><br/><b>Are you sure?</b></div>", 
                {
                    yesButtonText: "Yes",
                    noButtonText: "No",
                    onYes: "ModalPopupsConfirmYes()",
                    onNo: "ModalPopupsConfirmNo()"
                }
            );
        }
        
        function ModalPopupsConfirmYes() {
            alert('You pressed Yes');
            ModalPopups.Close("idConfirm1");
        }
        
        function ModalPopupsConfirmNo() {
            alert('You pressed No');
            ModalPopups.Cancel("idConfirm1");
        }

        function ModalPopupsYesNoCancel() {
            ModalPopups.YesNoCancel("idYesNoCancel1",
                "Confirm close of document",
                "<div style='padding: 25px;'><p>You are about to close this document.<br/>" + 
                "If you don't save this document, all information will be lost.</p>" + 
                "<p><b>Close document?</b></p></div>", 
                {
                    onYes: "ModalPopupsYesNoCancelYes()",
                    onNo: "ModalPopupsYesNoCancelNo()",
                    onCancel: "ModalPopupsYesNoCancelCancel()"
                }
            );
        }

        function ModalPopupsYesNoCancelYes() {
            alert('You pressed Yes');
            ModalPopups.Close("idYesNoCancel1");
        }

        function ModalPopupsYesNoCancelNo() {
            alert('You pressed No');
            ModalPopups.Cancel("idYesNoCancel1");
        }

        function ModalPopupsYesNoCancelCancel() {
            alert('You pressed Cancel');
            ModalPopups.Cancel("idYesNoCancel1");
        }

        function ModalPopupsPrompt() {
            ModalPopups.Prompt("idPrompt1",
                "Prompt",
                "Please enter your ID number",  
                {
                    width: 300,
                    height: 100,
                    onOk: "ModalPopupsPromptOk()",
                    onCancel: "ModalPopupsPromptCancel()"
                }
            );
        }

        function ModalPopupsPromptOk()
        {
            if(ModalPopups.GetPromptInput("idPrompt1").value == "") {
                ModalPopups.GetPromptInput("idPrompt1").focus();
                return;
            }
            alert("You pressed OK\nValue: " + ModalPopups.GetPromptInput("idPrompt1").value);
            ModalPopups.Close("idPrompt1");
        }

        function ModalPopupsPromptCancel() {
            alert("You pressed Cancel");
            ModalPopups.Cancel("idPrompt1");
        }
        
        function ModalPopupsIndicator() {
            ModalPopups.Indicator("idIndicator1",
                "Please wait",
                "Saving address information... <br/>" + 
                "This may take 3 seconds.", {
                    width: 300,
                    height: 100});
                    
            setTimeout('ModalPopups.Close(\"idIndicator1\");', 3000);
        }

        function ModalPopupsIndicator2() {
            ModalPopups.Indicator("idIndicator2",
                "Please wait",
                "<div style=''>" + 
                "<div style='float:left;'><img src='spinner.gif'></div>" + 
                "<div style='float:left; padding-left:10px;'>" + 
                "Saving address information... <br/>" + 
                "This may take 3 seconds." + 
                "</div>", 
                {
                    width: 300,
                    height: 100
                }
            );
                    
            setTimeout('ModalPopups.Close(\"idIndicator2\");', 3000);
        }
        
        function ModalPopupsCustom1() {
            ModalPopups.Custom("idCustom1",
                "Enter address information",
                "<div style='padding: 25px;'>" + 
                "<table>" + 
                "<tr><td>Name</td><td><input type=text id='inputCustom1Name' style='width:250px;'></td></tr>" + 
                "<tr><td>Address</td><td><input type=text id='inputCustom1Address' style='width:250px;'></td></tr>" + 
                "<tr><td>ZipCode</td><td><input type=text id='inputCustom1ZipCode' style='width:100px;'></td></tr>" + 
                "<tr><td>City</td><td><input type=text id='inputCustom1City' style='width:250px;'></td></tr>" + 
                "<tr><td>Phone</td><td><input type=text id='inputCustom1Phone' style='width:250px;'></td></tr>" + 
                "<tr><td>E-Mail</td><td><input type=text id='inputCustom1EMail' style='width:250px;'></td></tr>" + 
                "</table>" + 
                "</div>", 
                {
                    width: 500,
                    buttons: "ok,cancel",
                    okButtonText: "Save",
                    cancelButtonText: "Cancel",
                    onOk: "ModalPopupsCustom1Save()",
                    onCancel: "ModalPopupsCustom1Cancel()"
                }
            );
                    
            ModalPopups.GetCustomControl("inputCustom1Name").focus(); 
        }
        
        function ModalPopupsCustom1Save() {
            var custom1Name = ModalPopups.GetCustomControl("inputCustom1Name"); 
            if(custom1Name.value == "")
            {
                alert("Please submit a name to this form");
                custom1Name.focus();
            }
            else
            {
                alert("Your name is: " + custom1Name.value);
                ModalPopups.Close("idCustom1");
            }
        }
        
        function ModalPopupsCustom1Cancel() {
            alert('You pressed Cancel');
            ModalPopups.Cancel("idCustom1");
        }
        
    </script>

    <script type="text/javascript" src="shInit.js" language="javascript"></script>

</body>
</html>
