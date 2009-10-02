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
text = text.replace(/\/32/g, "");
value = value.replace(/\/32/g, "");
text = text.replace(/:$/, "");
value = value.replace(/:$/, "");
if(value.match(/^host/)) {
if(verifyIP(text) == 0) {
document.getElementById(selectbox).options.add(optn);
optn.text = text;
optn.value = value;
}
}
else {
document.getElementById(selectbox).options.add(optn);
optn.text = text;
optn.value = value;
}
if (document.getElementById(selectbox).name=="MEMBERS") {
document.iform.srchost.value="";
document.iform.srcnet.value="";
document.iform.srcalias.value="";
document.iform.srcuser.value="";
}
if (document.getElementById(selectbox).name=="SRCADDR") {
document.iform.srchost.value="";
document.iform.srcnet.value="";
document.iform.srcalias.value="";
document.iform.srcuser.value="";
if (document.getElementById(selectbox).options[0].text == "any") {
document.getElementById(selectbox).remove(0);
}
}
if (document.getElementById(selectbox).name=="DSTADDR") {
document.iform.dsthost.value="";
document.iform.dstnet.value="";
document.iform.snatint.value="";
document.iform.snatext.value="";
document.iform.dstalias.value="";
if (optn.text != "any") {
if (document.getElementById(selectbox).options[0].text == "any") {
document.getElementById(selectbox).remove(0);
}
}
}
}

function removeOptions(selectbox)
{
var i;
for(i=selectbox.options.length-1;i>=0;i--)
{
if(selectbox.options[i].selected)
selectbox.remove(i);
if(selectbox.options.length == 0) {
if(selectbox.name == 'DSTADDR' || selectbox.name == 'SRCADDR') {
var optn = document.createElement("OPTION");
document.getElementById(selectbox.name).options.add(optn);
optn.text = 'any';
optn.value = 'any';
}
}
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
for(i=selectbox.options.length-1;i>=0;i--)
{
if(selectbox.options[i].selected)
{
prop += selectbox.options[i].value + ', ';
}
}
prop = prop.replace(/, $/,"");
prop = prop.replace(/snat:/g,"");
prop = prop.replace(/host:/g,"");
prop = prop.replace(/net:/g,"");
prop = prop.replace(/alias:/g,'$');
if (selectbox.name=="SRCADDR") {
document.iform.srclist.value=prop
}
if (selectbox.name=="DSTADDR") {
document.iform.dstlist.value=prop
}
if (selectbox.name=="MEMBERS") {
document.iform.memberslist.value=prop
}

}
 
function createProtoProps(selectbox)
{
var i;
var tcp = '';
var udp = '';
var ip = '';
for(i=selectbox.options.length-1;i>=0;i--)
{
if(selectbox.options[i].selected)
{
if(selectbox.options[i].value.match( /^tcp/ )) {
tcp += selectbox.options[i].value + ', ';
}
if(selectbox.options[i].value.match( /^udp/ )) {
udp += selectbox.options[i].value + ', ';
}
if(selectbox.options[i].value.match( /^ip/ )) {
ip += selectbox.options[i].value + ', ';
}
}
}
tcp = tcp.replace(/, $/,"");
udp = udp.replace(/, $/,"");
ip = ip.replace(/, $/,"");
tcp = tcp.replace(/tcp\//g, "");
udp = udp.replace(/udp\//g, "");
ip = ip.replace(/ip\//g, "");
document.iform.tcpports.value=tcp;
document.iform.udpports.value=udp;
document.iform.ipprotos.value=ip;
}

function prepareSubmit(id)
{
selectAllOptions(id);
createProp(id);
}

function prepareRuleSubmit(id1,id2,id3)
{
selectAllOptions(id1);
createProp(id1);
selectAllOptions(id2);
createProp(id2);
selectAllOptions(id3);
createProtoProps(id3);
}
 
var ids=new Array('srchost','srcnet','srcalias', 'srcuser', 'dsthost', 'dstnet', 'dstalias', 'dstsnat', 'dstredir', 'dstrelay');
var tabs=new Array('tabAddress', 'tabProtocol', 'tabOptions');
var ifacetabs=new Array('tabWAN', 'tabLAN', 'tabDMZ', 'tabWireless');
var wifiencrypts= new Array('wpa','wep','open');
var wifibridges= new Array('nobridge','lanbridge','dmzbridge');

function switchwifiencrypt(id){
  hideallwifiencrypts();
  showwifibridge(id);
} 

function switchwifibridge(id){
  hideallwifibridges();
  if(id.match( /^lan/ ) || id.match( /^dmz/)) {
        return 0;
  }
  if(id.match( /^nobridge/)) { 
   	showwifibridge('wifiipdisp');
   	showwifibridge('wifiipaliasdisp');
  }
}

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

function switchifacetab(tab){
hideallifacetabs();
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

function hideallifacetabs(){
//loop through the array and hide each element by id
for (var i=0;i<ifacetabs.length;i++){
if(ifacetabs[i].match( /^tab/ )) {
hidediv(ifacetabs[i]);
}
}
}

function hideallwifiencrypts(){
//loop through the array and hide each element by id
for (var i=0;i<wifiencrypts.length;i++){
hidediv(wifiencrypts[i]);
}
}

function hideallwifibridges(){
hidediv('wifiipdisp');
hidediv('wifiipaliasdisp');
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

function showwifidiv(id) {
//safe function to show an element with a specified id
if (document.getElementById) { // DOM3 = IE5, NS6
document.getElementById(id).style.display = '';
}
else {
if (document.layers) { // Netscape 4
document.id.display = '';
}
else { // IE 4
document.all.id.style.display = '';
}
}
}

function showwifibridge(id) {
//safe function to show an element with a specified id
if (document.getElementById) { // DOM3 = IE5, NS6
document.getElementById(id).style.display = '';
}
else {
if (document.layers) { // Netscape 4
document.id.display = '';
}
else { // IE 4
document.all.id.style.display = '';
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
