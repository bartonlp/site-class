// Track user activity
// Goes hand in hand with tracker.php

'use strict';

//const TRACKERJS_VERSION = "3.1.11trackerjs-pdo"; // BLP 2025-04-26 - fix csstest
                                                 
// SiteClass places a <script> tag in the head.i.php file via
// $h->tracjerStr. That variable has a full list of JavaScript
// variables like this for bartonphillips.com. The values come from the
// mysitemap.json file.
/* 
var thesite = "bartonphillips.com",
theip = "195.252.232.86",
thepage = "/index.php",
trackerUrl = "https://bartonlp.com/otherpages/tracker.php",
beaconUrl = "https://bartonlp.com/otherpages/beacon.php",
noCssLastId = "",
desktopImg = "https://bartonphillips.net/images/blp-image.png", 
phoneImg = ""; 
desktopImg2 = "https://bartonphillips.net/images/146624.png";
phoneImg2 = "", 
mysitemap = "/var/www/bartonphillips.com/mysitemap.json",
lastId = "7611498", // BLP 2025-03-25 -
loggingphp = "https://bartonlp.com/otherpages/logging.php" // BLP 2025-03-26 - 
*/

// To use these debug variables you need a JavaScript section in you
// main file that sets these variable. Usually this would be placed in
/*
$S->b_inlineScript = <<<
var isMeFalse = "$S->isFalse", doState = "$S->doState", forceBot = "$S->forceBot";
EOF;
*/
// *************************
// We need 'var' as it may be added in a php.
var isMeFalse;
var doState; 
var forceBot; 
// These are here in case you want to edit these here rather than via a
// $S->b_inlineScript.//isMeFalse = true; // For Debugging
//doState = true; // For Debugging
//forceBot = true; // For Debugging
// *************************

function makeTime() {
  const x = new Date;
  return x.getHours()+":"+String(x.getMinutes()).padStart(2, '0')+":"+
      String(x.getSeconds()).padStart(2, '0')+":"+ String(x.getMilliseconds()).padStart(3, '0');
}

// **************************************************
// This can be used to do fetch() form or json calls.
// await postFormData({page: 'ajaxmsg', msg: 'Hello',site:
// 'bartonphillips.com'});
// or
// await postFormData('json', {...});

async function postFormData(data, type='form') {
  const body, headers;

  if (type === 'json') {
    headers = { 'Content-Type': 'application/json' };
    body = JSON.stringify(data);
  } else {
    // Default to x-www-form-urlencoded
    headers = { 'Content-Type': 'application/x-www-form-urlencoded' };
    body = new URLSearchParams(data);
  }

  const response = await fetch(trackerUrl, {
    method: 'POST',
    headers,
    body
  });

  if (!response.ok) {
    throw new Error(`HTTP error ${response.status}`);
  }

  const ret = response.text(); 
  return ret;
}

// **************************************************
// Post a AjaxMsg. For debugging
// While this really does not need to return the 'result' data it is an
// exercise in an async function with await.

async function postAjaxMsg(msg, mysitemap, arg1='', arg2='') {
  //console.log(`postAjaxMsg: id=${lastId}`);
  
  try {
    const result = await postFormData({
      page: 'ajaxmsg',
      id: lastId,
      ip: theip,
      agent: theagent,
      site: thesite,
      msg: msg,
      mysitemap: mysitemap,
      arg1: arg1,
      arg2: arg2
    });
                                 
    return result;
  } catch (err) {
    console.log('Fetch error:', err);
  }
}
// **************************************************

// **************************************************
// Start of regular logic.

console.log("tracker.js: at " , document.currentScript.src);
console.log("navigator.userAgentData: ", navigator.userAgentData);

// Now we wait until all of the DOM is loaded and ready.
// NOTE: these Javascript variables:
// thesite, thepage, trackerUrl, beaconUrl, noCssLastId, desktopImg,
// phoneImg,
// have been set in SiteClass.class.php -- SiteClass::getPageHead() and are available
// everywhere because they are declaired as 'var' right after
// tracker.js is declaired.

jQuery(document).ready(function($) {
  // Now lets do timer to update the endtime
  // This triggers tracker.php 'timer'.

  let cnt = 0;
  let time = 0;
  let difftime = 10000; // We miss the first ajax so the next time will be 10sec + 10sec.
  let tflag = true;

  runtimer(); // First thing, start the timer.

  if(noCssLastId !== '1') {
    // BLP 2025-04-26 - New logic. This replaces [data-lastid] with the
    // new id.
    $("#jQuery").before('<link rel="stylesheet" href="csstest-' + lastId + '.css" title="blp test">');
  }
  
  // BLP 2023-08-08 - desktopImg and phoneImg are supplied by
  // SiteClass::getPageHead(); They are trackerImg1 and trackerImgPhone
  // BLP 2023-08-10 - Same logic as Img2

  // If I do not have a phoneImg then I can just do a normal <img>

  let picture = '';

  //console.log("phoneImg="+phoneImg+", desktopImg="+desktopImg+", phoneImg2="+phoneImg2+", desktopImg2="+desktopImg2);
  
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

  // BLP 2024-12-17 - add <!-- commet.

  if(phoneImg || desktopImg) {
    $("header a:first-of-type").first().html(picture);
    $("header a:first-of-type").first().prepend("<!-- JavaScript enabled. tracker.js providing images -->\n");
  }
  
  // BLP 2023-08-10 - Here we need to remove the <img
  // id='headerImage2'> before we replave it with a posible <picture>
  // tag.
  
  $("#headerImage2").remove();

  // BLP 2023-08-10 - look to see if we have any Img2 items.

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
  
  // BLP 2023-08-10 - At this point we may or may not have a second
  // item in header.
  
  console.log("VARIABLES -- thesite: " + thesite + ", theip: " + theip + ", thepage: " + thepage + ", lastId: " + lastId +
              ", isMeFalse: " + isMeFalse + ", phoneImg: " + phoneImg + ", desktopImg: " + desktopImg +
              ", phoneImg2: " + phoneImg2 + ", desktopImg2: " + desktopImg2 + ", mysitemap: " + mysitemap);
  
  // Usually the image stuff (normal and noscript) will
  // happen before 'start' or 'load'.
  // 'start' is done weather or not 'load' happens. As long as
  // javascript works. Otherwise we should get information from the
  // image in the <noscript> section of includes/banner.i.php
   
  let ref = document.referrer; // Get the referer which we pass to 'start'

  // Do START.
  
  $.ajax({
    url: trackerUrl,
    data: {page: 'start', id: lastId, site: thesite, ip: theip, agent: theagent, thepage: thepage,
           isMeFalse: isMeFalse, referer: ref, mysitemap: mysitemap},
    type: 'post',
    success: function(data) {
      console.log(data +", "+ makeTime());
    },
    error: function(err) {
      console.log(err);
    }
  });

  /********************/
  /* Lifestyle Events */

  const getState = () => {
    if(document.visibilityState === 'hidden') {
      return 'hidden';
    }
    if(document.hasFocus()) {
      return 'active';
    }
    return 'passive';
  };

  let state = getState(); // get the first state.
  let prevState;
  
  // Accepts a next state and, if there has been a state change, logs the
  // change to the console. It also updates the 'state'

  const logStateChange = (nextState, type) => {
    prevState = state;
    state = nextState;
    
    if(nextState !== prevState) {
      console.log(`${type} State change: ${prevState} >>> ${nextState}, ${thepage}`);
      if(doState) { // ONLY for debugging.
        postAjaxMsg(`tracker.js: type=${type} State change: prev=${prevState} >>> next=${nextState}, page=${thepage}`, mysitemap);
      }
    }
  };

  // These lifecycle events can all use thier listener to observe state
  // changes (they call the 'getState()' function to determine the next state).

  ['pageshow', 'focus', 'blur', 'visibilitychange', 'resume'].forEach(type => {
    window.addEventListener(type, () => logStateChange(getState(), type));
  });

  // The next two listeners, on the other hand, can determine the next
  // state from the event itself.

  window.addEventListener('freeze', () => {
    // In the freeze event, the next state is always frozen.
    logStateChange('frozen', 'freeze');
  });

  window.addEventListener('pagehide', event => {
    if(event.persisted) {
      // If the event's persisted property is 'true' the page is about
      // to enter the Back-Forward Cache, which is also in the frozen state.
      logStateChange('frozen', 'pagehide');
    } else {
      // If the event's persisted property is not 'true' the page is
      // about to be unloaded.
      logStateChange('terminated', 'pagehide');
    }
  });
  /* End of lifestyle events. */
  
  // On the load event
  
  $(window).on("load", function(e) {
    $.ajax({
      url: trackerUrl,
      data: {page: e.type, 'id': lastId, agent: theagent, site: thesite, ip: theip, thepage: thepage, isMeFalse: isMeFalse, mysitemap: mysitemap},
      type: 'post',
      success: function(data) {
        console.log(data +", "+ makeTime());
      },
      error: function(err) {
        console.log(err);
      }
    });
  });

  // Check for pagehide unload beforeunload and visibilitychange
  // These are the exit codes as the page disapears.

  $(window).on("visibilitychange pagehide unload beforeunload", function(e) {
    // Can we use beacon?

    if(navigator.sendBeacon) { // If beacon is supported by this client we will always do beacon.
      navigator.sendBeacon(beaconUrl, JSON.stringify({'id':lastId, 'type': e.type, 'site': thesite, 'ip': theip, 'agent': theagent,
        'thepage': thepage, 'isMeFalse': isMeFalse, 'state': state, 'prevState': prevState}));
      
      console.log("beacon " + e.type + ", "+thesite+", "+thepage+", state="+state+", prevState="+prevState+ ", " + makeTime());
    } else { // This is only if beacon is not supported by the client (which is infrequently. This can happen with MS-Ie, tor and old versions of others).
      console.log("Beacon NOT SUPPORTED");
      
      // BLP 2023-08-08 - tracker.php will send an error_log if this
      // happens. It does happen if you use 'tor' for example.
      
      $.ajax({
        url: trackerUrl,
        data: {page: 'onexit', type: e.type, 'id': lastId, agent: theagent,
               site: thesite, ip: theip, thepage: thepage, isMeFalse: isMeFalse, state: state, mysitemap: mysitemap},
        type: 'post',
        success: function(data) {
          console.log("tracker ", data);
        },
        error: function(err) {
          console.log(err);
        }
      });
    }
  });

  function runtimer() {
    if(cnt++ < 50) {
      // Time should increase to about 8 plus minutes
      time += 10000;
    }

    // Check for first time.
    
    if(tflag) {
      // Don't do the first time. Wait until finger is set.
      // Wait for the next time which will be in 10 seconds.

      setTimeout(runtimer, time);
      tflag = false;
      return;
    }

    // After 10 seconds we should probably have a 'finger'.
    
    $.ajax({
      url: trackerUrl,
      data: {page: 'timer', id: lastId, site: thesite, agent: theagent, ip: theip, thepage: thepage,
             isMeFalse: isMeFalse, mysitemap: mysitemap, difftime: (difftime/1000)},
      type: 'post',
      success: function(data) {
        difftime += 10000;
        console.log(`${data}, ${makeTime()}, next timer=${difftime/1000} sec`);

        // TrackerCount is only in bartonphillips.com/index.php
        $("#TrackerCount").html("Tracker every " + time/1000 + " sec.<br>");
        
        const msg = `Timer: next difftime=${difftime/1000}`;
        
        // BLP 2025-03-24 - FOR DEBUG. Looking to see why I am not seeing timer entries.?
        //if(!difftime) {
        //  postAjaxMsg(msg, mysitemap, thepage, lastId).then(ajaxData => {
        //    console.log(`This is ajaxData (${ajaxData}) from trackerUrl(${trackerUrl})`);
        //  });
        //}
        
        setTimeout(runtimer, time);        
      }
    });
  }
});
