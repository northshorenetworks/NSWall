function verifyIP (IPvalue) {
errorString = "";
theName = "IPaddress";

var ipPattern = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/;
var ipArray = IPvalue.match(ipPattern);

if (IPvalue == "0.0.0.0")
errorString = errorString + theName + ': '+IPvalue+' is a special IP address and cannot be used here.';
else if (IPvalue == "255.255.255.255")
errorString = errorString + theName + ': '+IPvalue+' is a special IP address and cannot be used here.';
if (ipArray == null)
errorString = errorString + theName + ': '+IPvalue+' is not a valid IP address.';
else {
for (i = 0; i < 4; i++) {
thisSegment = ipArray[i];
if (thisSegment > 255) {
errorString = errorString + theName + ': '+IPvalue+' is not a valid IP address.';
i = 4;
}
if ((i == 0) && (thisSegment > 255)) {
errorString = errorString + theName + ': '+IPvalue+' is a special IP address and cannot be used here.';
i = 4;
      }
   }
}
extensionLength = 3;
if (errorString == "") {
return 0;
}
else {
alert (errorString);
return 1;
}
}

function addOption(selectbox,text,value)
{
var optn = document.createElement("OPTION");
document.getElementById(selectbox).options.add(optn);
text = text.replace(/\/32/g, "");
value = value.replace(/\/32/g, "");
text = text.replace(/:$/, "");
value = value.replace(/:$/, "");
optn.text = text;
optn.value = value;
document.iform.srchost.value="";
document.iform.srcnet.value="";
document.iform.srctable.value="";
 
if (document.getElementById(selectbox).name=="MEMBERS") {
document.iform.members.value="";
}
if (document.getElementById(selectbox).name=="SRCADDR") {
document.iform.members.value="";
}
if (document.getElementById(selectbox).name=="DSTADDR") {
document.iform.members.value="";
}
}
 
function removeOptions(selectbox)
{
var i;
for(i=selectbox.options.length-1;i>=0;i--)
{
if(selectbox.options[i].selected)
selectbox.remove(i);
}
}
 
function selectAllOptions(selectbox)
{
var i;
for(i=selectbox.options.length-1;i>=0;i--)
{
selectbox.options[i].selected = true;
}
}
 
function createProp(selectbox)
{
var i;
var prop = '';
var rdrprop ='';
for(i=selectbox.options.length-1;i>=0;i--)
{
if(selectbox.options[i].selected)
{
prop += selectbox.options[i].value + ', ';
}
}
prop = prop.replace(/, $/,"");
prop = prop.replace(/host:/g,"");
prop = prop.replace(/net:/g,"");
prop = prop.replace(/table:/g,'$');
rdrprop = rdrprop.replace(/snat:/g,"");
if (selectbox.name=="MEMBERS") {
document.iform.memberslist.value=prop
   }
if (selectbox.name=="SRCADDR") {
   document.iform.srclist.value=prop
   }
if (selectbox.name=="DSTADDR") {
   document.iform.dstlist.value=prop
   }
}
 
function prepareSubmit()
{
selectAllOptions(MEMBERS);
createProp(MEMBERS)
}

function prepareRuleSubmit()
{
selectAllOptions(SRCADDR);
createProp(SRCADDR);
selectAllOptions(DSTADDR);
createProp(DSTADDR);
selectAllOptions(PROTOLIST);
createProtoProps(PROTOLIST);
}
 
var ids=new Array('srchost','srcnet','srcalias', 'dsthost', 'dstnet', 'dstalias', 'dstsnat', 'dstredir', 'dstrelay');
var tabs=new Array('tabAddress', 'tabProtocol', 'tabOptions');
 
function switchsrcid(id){  
  hideallsrcids();
  showdiv(id);
}
 
function switchdstid(id){
hidealldstids();
showdiv(id);
}
 
function switchtab(tab){
hidealltabs();
showdiv(tab);
}

function hideallsrcids(){
//loop through the array and hide each element by id
for (var i=0;i<ids.length;i++){
if(ids[i].match( /^src/ )) {
hidediv(ids[i]);
}
}
}

function hidealldstids(){
//loop through the array and hide each element by id
for (var i=0;i<ids.length;i++){
if(ids[i].match( /^dst/ )) {
hidediv(ids[i]);
}
}
}
 
function hidealltabs(){
//loop through the array and hide each element by id
for (var i=0;i<tabs.length;i++){
if(tabs[i].match( /^tab/ )) {
hidediv(tabs[i]);
} 
}
}

function hidediv(id) {
//safe function to hide an element with a specified id
if (document.getElementById) { // DOM3 = IE5, NS6
document.getElementById(id).style.display = 'none';
}
else {
if (document.layers) { // Netscape 4
document.id.display = 'none';
}
else { // IE 4
document.all.id.style.display = 'none';
}
}
}
 
function showdiv(id) {
//safe function to show an element with a specified id
if (document.getElementById) { // DOM3 = IE5, NS6
document.getElementById(id).style.display = 'block';
}
else {
if (document.layers) { // Netscape 4
document.id.display = 'block';
}
else { // IE 4
document.all.id.style.display = 'block';
}
}
}

function activate(obj){
links = document.getElementById('navigator').getElementsByTagName('li');
for(i=0;i<links.length;i++){
links[i].className = 'tabinact';
if(links[i].id==obj){
links[i].className='tabact';
}
}
}
