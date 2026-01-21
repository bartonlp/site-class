<?php
// site-class/includes/traits/UserAgentTools.php
// BLP 2025-04-19 - moved setSiteCookie() here from Database. Also getIp()
// BLP 2025-04-19 - Change botAs to botAsBits

namespace bartonlp\SiteClass;

/*
CREATE TABLE `tracker` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `botAsBits` int DEFAULT '0',
  `site` varchar(25) DEFAULT NULL,
  `page` varchar(255) NOT NULL DEFAULT '',
  `finger` varchar(50) DEFAULT NULL,
  `nogeo` tinyint(1) DEFAULT NULL,
  `browser` varchar(50) DEFAULT NULL,
  `ip` varchar(40) DEFAULT NULL,
  `count` int DEFAULT '1',
  `agent` text,
  `referer` varchar(255) DEFAULT '',
  `starttime` datetime DEFAULT NULL,
  `endtime` datetime DEFAULT NULL,
  `difftime` varchar(20) DEFAULT NULL,
  `isJavaScript` int DEFAULT '0',
  `error` varchar(256) DEFAULT NULL,
  `lasttime` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `site` (`site`),
  KEY `ip` (`ip`),
  KEY `lasttime` (`lasttime`),
  KEY `starttime` (`starttime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

CREATE TABLE `bots3` (
  `ip` varchar(50) NOT NULL COMMENT 'big enough to handle IP6',
  `agent` text NOT NULL COMMENT 'big enough to handle anything',
  `count` int DEFAULT '1' COMMENT 'the number of time this has been updated',
  `robots` int DEFAULT '0' COMMENT 'bit mapped values',
  `site` int DEFAULT '0' COMMENT 'bit mapped values',
  `page` varchar(255) DEFAULT NULL COMMENT 'the page on my site',
  `created` datetime DEFAULT NULL COMMENT 'when record created',
  `lasttime` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'auto, the lasttime this was updated',
  UNIQUE KEY `ip_agent_page` (`ip`,`agent`(255),`page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
*/

define("USER_AGENT_TOOLS_VERSION", "1.0.0uat-pdo");

trait UserAgentTools {
  /*
   * isMyIp. Verify if an arbitrary IP address belongs to Me.
   * @param: string $ip.
   * @return: bool.
   * if $this->isMeFalse is true returns false always.
   * if $ip is not in the $this->myIp array returns false.
   * else true.
   * @side-effect: can set $this->botAsBits.
   */
  
  public function isMyIp(string $ip): bool {
    if ($this->isMeFalse === true) {
      $this->botAsBits |= BOTS_ISMEFALSE;
      return false;
    }
    return in_array($ip, $this->myIp);
  }

  /*
   * isMe. Verify if $this->ip belongs to ME.
   * @param: none.
   * @return: bool.
   * calls $this->isMyIp($this->ip or blank.
   */
  
  public function isMe(): bool {
    return $this->isMyIp($this->ip ?? '');
  }

  /*
   * isBot. Check the User Agent String and the bots3 table to determin if this is a BOT.
   * @param: string $agent. Default to null.
   * @return: bool.
   * @site-effects: sets the $this->botAsBits and $this->trackerinfo properties.
   * This method is used in several places. tracker.php, beacon.php,
   * robots-sitemap.php, NotARobot.php, register1.php, analysis.php.
   * NOTE: Database::tracker() is run from the constructor before anything else is done.
   * This method is NOT called from Database::tracker()!
   */
  
  public function isBot(?string $agent = null): bool {
    // If forceBot is true anything is a robot.
    
    if ($this->forceBot === true) {
      $this->isBot = true;
      $this->botAsBits = BOTS_FORCE | BOTS_SITECLASS;
      $this->trackerBotInfo = TRACKER_BOT;
    } else {
      // If not forceBot then initialize the three side effects.
      
      $this->isBot = false;
      $this->botAsBits = 0;
      $this->trackerBotInfo = 0;
    }

    // Try to get the class properties.
    
    $ip = $this->ip ?? '';
    $agent = $agent ?? $this->agent ?? '';
    $page = basename($this->self ?? ''); // strip of any prefex

    // Make sure it is not ME!

    if($this->isMe()) return false;

    if(empty($agent)) {
      $this->botAsBits |= BOTS_NOAGENT; // BLP 2025-04-05 - bitmap
      $this->isBot = true;
    }

    [$browser, $engine, $botbits, $trackerbits, $isBot] = getBrowserInfo($agent); // from helper-functions.php

    $this->browser = $browser; // BLP 2025-04-25 - new
    $this->engine = $engine;   // BLP 2025-04-25 - new
    $this->isBot |= $isBot;
    $this->botAsBits |= $botbits;  
    $this->trackerBotInfo |= $trackerbits;
    
    // New logic to take apart the robots field and 'or' the corresponding values
    // into $this->bottAs and $this->trackerBotInfo variables which are used later in Database
    // tracker().

    // I use this map to take apart the robots fields from ALL of the bots3 records.
    // Some of these have TRACKER_... or BEACON_... values but many have none.
    // See the logic in the section that loops through ALL of the bots3 records below.

    $robotMap = BOTS_ROBOTMAP; // BOTS_ROBOTMAP is an array in defines.php
    
    // Look at ALL of the bots3 records for this ip, agent, page (table unique keys).
    
    if($this->sql("select robots from $this->masterdb.bots3 where ip='$ip' and agent='$agent' and page='$page'")) { // Found some.
      // Get each record

      while([$robots] = $this->fetchrow('num')) {
        // Use $robotMap and take apart the information
        // Changed this to a looping function as the number of $robots items got too big.
        // The $robotMap array looks like: BOTS_... bit and an optional TRACKER_... or BEACON_...
        // bit.
        // We mask the $robots bitmap with the $bit from the array and then 'or' the bit into
        // $this->botAsBits.
        // If the optional flag bit is set it is 'ored' into $this->trackerBitInfo.

        foreach($robotMap as $bit => $flag) {
          if($robots & $bit) {
            $this->botAsBits |= $bit;
            if(isset($flag)) {
              $this->trackerBotInfo |= $flag;
            }
          }
        }
        
        // Now $this->trackerBotInfo and $this->botAsBits are set with the bits that were found in
        // $robots. This added all of the bits found in ALL of the bots3 records for the $ip,
        // $agent and $page (the tables keys) to the $this->botAsBits and the $this->trackerBotInfo
        // properties.
      }

      // If any of these are set then this is a bot, UNLESS the next if is true.
      
      if($this->botAsBits & (BOTS_MATCH | BOTS_GOODBOT | BOTS_NOAGENT)) {
        $this->botAsBits |= BOTS_SITECLASS;
        $this->trackerBotInfo |= TRACKER_BOT; 
      }

      // However, if BOTS_HAS_DIFFTIME is true then this is NOT a BOT so remove BOTS_SITECLASS and
      // TRACKER_BOT. I am going to leave any of the three from above.
      
      if($this->botAsBits & BOTS_HAS_DIFFTIME) { // true if difftime was present
        logInfo("UserAgentTools isBot, remove bot bit: ip=$ip, agent=$agent, page=$page, line=". __LINE__);
        $this->trackerBotInfo &= ~TRACKER_BOT; // BLP 2025-04-14 - If we have difftime remove the bots bit from trackerBotInfo.
        $this->botAsBits &= ~BOTS_SITECLASS; // BLP 2025-04-14 - remove bot from botAsBits.
      } 

      $hexTrackerBotInfo = dechex($this->trackerBotInfo);
      $hexBotAsBits = dechex($this->botAsBits);

      // $this->botAsBits and $this->trackerBotInfo are used in Database::tracker().
      // Now only if botAsBits has the BOTS_SITECLASS bit set is this a bot.

      if($this->botAsBits & BOTS_SITECLASS) {
        $this->isBot = true;
      }
    }

    // $this->isBot may be true because 1) BOTS_MATCH, 2) BOTS_NOAGENT 3) BOTS_GOODBOT or 4) found in bots
    // table. If the agent mastched the list in the preg_match at the top, then BOTS_MATCH.
    // If the agent had an http address for information then BOTS_GOODBOT is set.
    // If there was 'no agent' then BOTS_NOAGENT is set.
    // If any of the above happened then isBot is true.
    // If we did not find anything then $this->botAsBits=0 and $this->isBot=false.

    return $this->isBot;
  }

  /**
   * setSiteCookie()
   *
   * @param: string $cookie
   * @param: string $value
   * @param: int $expire
   * @param: string $path Defaults to '/'.
   * @param: string $thedomain Defaults to null.
   * @return bool true if OK else false.
   */

  public function setSiteCookie(string $cookie, string $value, int $expire, string $path="/", ?string $thedomain=null,
                                bool $secure=true, bool $httponly=false, string $samesite='Lax'):bool
  {
    $ref = $thedomain ?? "." . $this->siteDomain; // BLP 2021-10-16 -- added dot back to ref.
    
    $options =  array(
                      'expires' => $expire,
                      'path' => $path,
                      'domain' => $ref, // (defaults to $this->siteDomain with leading period.
                      'secure' => $secure,
                      'httponly' => $httponly,    // If true javascript can't be used (defaults to false.
                      'samesite' => $samesite    // None || Lax  || Strict (defaults to Lax)
                     );

    if(!setcookie($cookie, $value, $options)) {
      error_log("UserAgentTools setSiteCookie: failed, site=$this->siteName, page=$this->self, line=". __LINE__);
      return false;
    }

    return true;
  }

  /**
   * getIp()
   * Get the ip address
   * @return int ip address
   */

  public function getIp():string {
    return $this->ip;
  }
}
