// This file is loaded on every page that resides inseide the #content div


// Any time a user clicks on link (<a href=) , load the content div with the response from the link content

$('#iform a').click(function(){
     var toLoad = $(this).attr('href');
     window.location.hash = $(this).attr('href').substr(0,$(this).attr('href').length-4);
     clearInterval(refreshId);
     $("#content").load(toLoad);
     return false;
});
