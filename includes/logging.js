// BLP 2025-03-26 - 
// Log interactions. From ChatGPT
// This is a companion file with logging.php. This file uses beacon to
// send information to ../logging.php (default in
// bartonlp.com/otherpages.
// I have created a new table.
/*
CREATE TABLE `interaction` (
`index` int NOT NULL AUTO_INCREMENT,
`id` int DEFAULT NULL,
`ip` varchar(20) DEFAULT NULL,
`site` varchar(100) DEFAULT NULL,
`page` varchar(100) DEFAULT NULL,
`event` varchar(100) DEFAULT NULL,
`time` varchar(100) DEFAULT NULL,
`created` timestamp NULL DEFAULT NULL,
`lasttime` timestamp NULL DEFAULT NULL,
`count` int DEFAULT '1',
PRIMARY KEY (`index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
*/

var loggingphp;

(function interactionTracker() {
  const endpoint = loggingphp; // BLP 2025-03-26 - 
  const meta = {
    id: lastId,
    ip: theip,
    site: thesite,
    page: thepage,
    agent: theagent
  };

  // Only log once per event type
  const fired = new Set();

  function logInteraction(eventType) {
    if (fired.has(eventType)) return;
    fired.add(eventType);

    const params = new URLSearchParams({
      event: eventType,
      ...meta,
      ts: Date.now()
    });

    navigator.sendBeacon(endpoint, params);

    // Optional: mark session as likely human
    sessionStorage.setItem('isProbablyHuman', 'true');
  }

  const events = ['scroll', 'click', 'mousemove', 'keydown', 'touchstart', 'resize'];

  events.forEach(evt => window.addEventListener(evt, () =>
    logInteraction(evt),
                                                { once: true }));
})();
