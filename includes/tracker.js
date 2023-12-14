// Track user activity
// Goes hand in hand with tracker.php
// BLP 2023-08-08 - Changed to use the new images for desktop and phone.
// BLP 2023-08-10 - Do 'headerImage2'

'use strict';

const TRACKERJS_VERSION = "3.1.0trackerjs"; // BLP 2023-08-08 - 

let visits = 0;

var isMeFalse;
//isMeFalse = true; // For Debugging

var doState; // for debugging. It can be set by the caller.

function makeTime() {
  let x = new Date;
  return x.getHours()+":"+String(x.getMinutes()).padStart(2, '0')+":"+String(x.getSeconds()).padStart(2, '0')+":"+ String(x.getMilliseconds()).padStart(3, '0');
}

// Post a AjaxMsg. For debugging

function postAjaxMsg(msg, arg1='', arg2='') {
  $.ajax({
    url: trackerUrl,
    data: {page: 'ajaxmsg', msg: msg, site: thesite, ip: theip, arg1: arg1, arg2: arg2, isMeFalse: isMeFalse},
    type: 'post',
    success: function(data) {
      console.log(data);
    },
    error: function(err) {
      console.log(err);
    }
  });
}

// The very first thing we do is get the lastId from the script tag.

const lastId = $("script[data-lastid]").attr("data-lastid");
console.log("navigator.userAgentData: ", navigator.userAgentData);

// Now we wait until all of the DOM is loaded and ready.
// NOTE: these Javascript variables have been set in
// SiteClass.class.php -- SiteClass::getPageHead() and are available
// everywhere because they are declaired as 'var' right after
// tracker.js is declaired:
// thesite, thepage, trackerUrl, beaconUrl, noCssLastId, desktopImg,
// phoneImg.

jQuery(document).ready(function($) {
  if(noCssLastId !== "1") {
    $("script[data-lastid]").before('<link rel="stylesheet" href="csstest-' + lastId + '.css" title="blp test">');
  }
  
  // BLP 2023-08-08 - desktopImg and phoneImg are supplied by SiteClass::getPageHead();
  // BLP 2023-08-10 - Same logic as Img2

  let picture;

  if(phoneImg || desktopImg) {
    picture = "<picture id='logo'>";
    
    if(phoneImg) {
      picture += "<source srcset=" + phoneImg + " media='((hover: none) and (pointer: coarse))' alt='phoneImage'>";
    }

    if(desktopImg) {
      picture += "<img src=" + desktopImg + " alt='desktopImage'>"
    } else {
      picture += "<img src=" + phoneImg + " alt='phoneImage'>";
    }
    
    picture += "</picture>";

    // BLP 2023-08-10 - This will remove the <img> tag and replace it
    // with the <picture> tag.
    
    $("header a:first-of-type").first().html(picture);
  }

  // BLP 2023-08-10 - Here we need to remove the <img
  // id='headerImage2'> before we replave it with a posible <picture>
  // tag.
  
  $("#headerImage2").remove();

  // BLP 2023-08-10 - look to see if we have any Img2 items.

  if(phoneImg2 || desktopImg2) {
    picture = "<picture id='headerImage2'>";

    // BLP 2023-08-10 - Do we have a phoneImg2?
    
    if(phoneImg2) {
      picture += "<source srcset=" + phoneImg2 + " media='((hover: none) and (pointer: coarse))' alt='phoneImage'>";
    } 

    // BLP 2023-08-10 - Do we have a desktopImg2?
    
    if(desktopImg2) {
      picture += "<img src=" + desktopImg2 + " alt='desktopImage'>"
    } else {
      picture += "<img src=" + phoneImg2 + " alt='phoneImage'>";
    }
      
    // BLP 2023-08-10 - If we have either finish off the picture tag.
    
    picture += "</picture>";
    
    $("header a:first-of-type").after(picture);
  } 

  // BLP 2023-08-10 - At this point we may or may not have a second
  // item in header.
  
  console.log("VARIABLES -- thesite: " + thesite + ", theip: " + theip + ", thepage: " + thepage + ", lastId: " + lastId +
              ", isMeFalse: " + isMeFalse + ", phoneImg: " + phoneImg + ", desktopImg: " + desktopImg +
              ", phoneImg2: " + phoneImg2 + ", desktopImg2: " + desktopImg2 + ", mysitemap: " + mysitemap);
  
  // Get the cookie. If it has 'mytime' we set 'visits' to zero.
  // Always reset cookie for 10 min.

  visits = (document.cookie.match(/(mytime)=/)) ? 0 : 1; // visits is now set for the rest of the visit.
  
  console.log(`cookie mytime: visits=${visits}`);
  
  let date = new Date();
  let value = date.toGMTString();
  date.setTime(date.getTime() + (60 * 10 * 1000)); // 10 minutes
  value += "|" + date.toGMTString(); // the current time | time + 10 min.

  console.log("mytime cookie value="+value);
  document.cookie = "mytime=" + value + "; expires=" + date.toGMTString() + ";path=/"; // expires in 10 min.

  // Usually the image stuff (normal and noscript) will
  // happen before 'start' or 'load'.
  // 'start' is done weather or not 'load' happens. As long as
  // javascript works. Otherwise we should get information from the
  // image in the <noscript> section of includes/banner.i.php
  // BLP 2023-08-08 - 'start' now implies 'script'!
  
  let ref = document.referrer; // Get the referer which we pass to 'start'
  
  $.ajax({
    url: trackerUrl,
    data: {page: 'start', id: lastId, site: thesite, ip: theip, thepage: thepage, isMeFalse: isMeFalse, referer: ref, mysitemap: mysitemap},
    type: 'post',
    success: function(data) {
      console.log(data +", "+ makeTime());
    },
    error: function(err) {
      console.log(err);
    }
  });

  /* Lifestyle Events */
  
  const getState = () => {
    if (document.visibilityState === 'hidden') {
      return 'hidden';
    }
    if (document.hasFocus()) {
      return 'active';
    }
    return 'passive';
  };

  let state = getState();

  // Accepts a next state and, if there's been a state change, logs the
  // change to the console. It also updates the `state` value defined above.

  const logStateChange = (nextState, type) => {
    const prevState = state;
    if(nextState !== prevState) {
      if(doState) {
        console.log(`${type} State change: ${prevState} >>> ${nextState}, ${thepage}`);
        postAjaxMsg(`${type} State change: ${prevState} >>> ${nextState}, ${thepage}`);
      }
      state = nextState;
    }
  };

  // These lifecycle events can all use the same listener to observe state
  // changes (they call the `getState()` function to determine the next state).

  ['pageshow', 'focus', 'blur', 'visibilitychange', 'resume'].forEach(type => {
    window.addEventListener(type, () => logStateChange(getState(), type), {capture: true});
  });

  // The next two listeners, on the other hand, can determine the next
  // state from the event itself.

  window.addEventListener('freeze', () => {
    // In the freeze event, the next state is always frozen.
    logStateChange('frozen', 'freeze');
  }, {capture: true});

  window.addEventListener('pagehide', event => {
    if(event.persisted) {
      // If the event's persisted property is `true` the page is about
      // to enter the Back-Forward Cache, which is also in the frozen state.
      logStateChange('frozen', 'pagehide');
    } else {
      // If the event's persisted property is not `true` the page is
      // about to be unloaded.
      logStateChange('terminated', 'pagehide');
    }
  }, {capture: true});

  /* End of lifestyle events. */
  
  // On the load event
  
  $(window).on("load", function(e) {

    $.ajax({
      url: trackerUrl,
      data: {page: e.type, 'id': lastId, site: thesite, ip: theip, thepage: thepage, isMeFalse: isMeFalse, mysitemap: mysitemap},
      type: 'post',
      success: function(data) {
        console.log(data +", "+ makeTime());
      },
      error: function(err) {
        console.log(err);
      }
    });
  });

  // Check for pagehide unload beforeunload nd visibilitychange
  // These are the exit codes as the page disapears.

  $(window).on("visibilitychange pagehide unload beforeunload", function(e) {
    // Can we use beacon?

    if(navigator.sendBeacon) { // If beacon is supported by this client we will always do beacon.
      navigator.sendBeacon(beaconUrl, JSON.stringify({'id':lastId, 'type': e.type, 'site': thesite, 'ip': theip, 'visits': visits, 'thepage': thepage, 'isMeFalse': isMeFalse, 'state': state}));
      console.log("beacon " + e.type + ", "+thesite+", "+thepage+", state="+state+", "+makeTime());
    } else { // This is only if beacon is not supported by the client (which is infrequently. This can happen with MS-Ie, tor and old versions of others).
      console.log("Beacon NOT SUPPORTED");
      
      // BLP 2023-08-08 - tracker.php will send an error_log if this
      // happens. It does happen if you use 'tor' for example.
      
      $.ajax({
        url: trackerUrl,
        data: {page: 'onexit', type: e.type, 'id': lastId, site: thesite, ip: theip, visits: visits, thepage: thepage, isMeFalse: isMeFalse, state: state, mysitemap: mysitemap},
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

  // Now lets do timer to update the endtime
  // This triggers tracker.php 'timer'.
  
  let cnt = 0;
  let time = 0;
  let difftime = 10000; // We miss the first ajax so the next time will be 10sec + 10sec.
  let tflag = true;
  
  function runtimer() {
    if(cnt++ < 50) {
      // Time should increase to about 8 plus minutes
      time += 10000;
    }

    // Check for first time.
    
    if(tflag) {
      // Don't do the first time. Wait until finger is set.
      // Wait the then next time which will be in 10 seconds.

      //console.log("Don't do first time. Set time for 10sec.");
      
      setTimeout(runtimer, time);
      tflag = false;
      return;
    }

    // After 10 seconds we should probably have a 'finger'.

    console.log(`tracker timer: ${trackerUrl}, ${theip}, ${thesite}, ${thepage}`);
    $.ajax({
      url: trackerUrl,
      data: {page: 'timer', id: lastId, site: thesite, ip: theip, visits: visits, thepage: thepage, isMeFalse: isMeFalse, mysitemap: mysitemap},
      type: 'post',
      success: function(data) {
        difftime += 10000;
        console.log(data +", " +makeTime() + ", next timer=" + (difftime/1000) + " sec");

        // TrackerCount is only in bartonphillips.com/index.php
        $("#TrackerCount").html("Tracker every " + time/1000 + " sec.<br>");

        setTimeout(runtimer, time);
      },
      error: function(err) {
        console.log(err);
      }
    });
  }

  runtimer();
});
