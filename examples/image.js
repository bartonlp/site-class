'use strict';

const TRACKERJS_VERSION = "3.1.2trackerjs-pdo"; // BLP 2024-11-16 - remove mytime logic!

let visits = 0;
var isMeFalse;
var doState; // for debugging. It can be set by the caller.

// The very first thing we do is get the lastId from the script tag.

const lastId = $("script[data-lastid]").attr("data-lastid");
console.log("navigator.userAgentData: ", navigator.userAgentData);

jQuery(document).ready(function($) {
  if(noCssLastId !== '1') {
    $("script[data-lastid]").before('<link rel="stylesheet" href="csstest-' + lastId + '.css" title="blp test">');
  }

  let picture = '';

  if(!phoneImg) {
    picture += "<img id='logo' src=" + desktopImg + " alt='desktopImage'>";
  } else if(!desktopImg) {
    picture += "<img id='logo' src=" + phoneImg + " alt='phoneImage'>";
  } else { // We have a phone and desktop image.
    picture = "<picture id='logo'>";
    picture += "<source srcset=" + phoneImg + " media='((hover: none) and (pointer: coarse))' alt='phoneImage'>";
    picture += "<source srcset=" + desktopImg + " media='((hover: hover) and (pointer: fine))' alt='desktopImage'>";
    picture += "<img src=" + phoneImg + " alt='phoneImage'>";
    picture += "</picture>";
  }

  if(phoneImg || desktopImg) {
    $("header a:first-of-type").first().html(picture);
  }

  $("#headerImage2").remove();

  picture = '';

  if(!phoneImg2) {
    picture += "<img id='headerImage2' src=" + desktopImg2 + " alt='desktopImage2'>";
  } else if(!desktopImg2) {
    picture += "<img id='headerImage2' src=" + phoneImg2 + " alt='phoneImage2'>";
  } else {
    picture = "<picture id='headerImage2'>";
    picture += "<source srcset=" + phoneImg2 + " media='((hover: none) and (pointer: coarse))' alt='phoneImage2'>";
    picture += "<source srcset=" + desktopImg2 + " media='((hover: hover) and (pointer: fine))' alt='desktopImage2'>";
    picture += "<img src=" + phoneImg2 + " alt='phoneImage'>";
    picture += "</picture>";
  } 

  if(phoneImg2 || desktopImg2) {
    $("header a:first-of-type").after(picture);
  }

  console.log("VARIABLES -- thesite: " + thesite + ", theip: " + theip + ", thepage: " + thepage + 
              ", isMeFalse: " + isMeFalse + ", phoneImg: " + phoneImg + ", desktopImg: " + desktopImg +
              ", phoneImg2: " + phoneImg2 + ", desktopImg2: " + desktopImg2);
});

