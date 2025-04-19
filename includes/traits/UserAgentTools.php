<?php
// site-class/includes/traits/UserAgentTools.php
// BLP 2025-04-19 - moved setSiteCookie() here from Database. Also getIp()
// BLP 2025-04-19 - Change botAs to botAsBits

trait UserAgentTools {
  public function isMyIp(string $ip): bool {
    if ($this->isMeFalse === true) {
      $this->botAsBits |= BOTS_ISMEFALSE;
      return false;
    }
    return in_array($ip, $this->myIp ?? []);
  }

  public function isMe(): bool {
    return $this->isMyIp($this->ip ?? '');
  }

  public function isBot(?string $agent = null): bool {
    if ($this->forceBot === true) {
      $this->isBot = true;
      $this->botAsBits = BOTS_FORCE | BOTS_SITECLASS;
    } else {
      $this->isBot = false;
      $this->botAsBits = 0;
    }

    $ip = $this->ip ?? '';
    $agent = $agent ?? $this->agent ?? '';
    $page = basename($this->self ?? '');

    // I use this map to take apart the robots fields from ALL of the bots3 records.
    // Some of these have TRACKER_... or BEACON_... values but many have none.
    // See the logic in the section that loops through ALL of the bots3 records below.

    $robotMap = BOTS_ROBOTMAP; // BLP 2025-04-11 - BOTS_ROBOTMAP is an array in defines.php
    
    $this->trackerBotInfo = null; // Set to null at start.
    
    // BLP 2025-01-12 - Make sure it is not ME!

    if($this->isMe()) return false;
    
    if(!empty($agent)) {
      if(($x = preg_match("~@|bot|spider|scan|HeadlessChrome|python|java|wget|nutch|perl|libwww|lwp-trivial|curl|PHP/|urllib|".
                          "crawler|GT::WWW|Snoopy|MFC_Tear_Sample|HTTP::Lite|PHPCrawl|URI::Fetch|Zend_Http_Client|".
                          "http client|PECL::HTTP|Go-|python~i", $agent)) === 1) { // 1 means a match

        $this->isBot = true;
        $this->botAsBits |= BOTS_MATCH;
      } elseif($x === false) { // false is error
        // This is an unexplained ERROR
        throw new PdoException(__CLASS__ . " " . __LINE__ . ": preg_match() returned false", -300);
      }

      if(($x = preg_match("~\+?https?://~", $agent)) === 1) {
        $this->isBot = true;
        $this->botAsBits |= BOTS_GOODBOT; // BLP 2025-04-03 - bitmap
      } elseif($x === false) {
        throw new PdoException(__CLASS__ . " " . __LINE__ . ": preg_match() for +https? false", -301);
      }
    } else {
      $this->botAsBits |= BOTS_NOAGENT; // BLP 2025-04-05 - bitmap
      $this->isBotBits = true;
    }

    // New logic to take apart the robots field and 'or' the corresponding values
    // into $this->bottAs and $this->trackerBotInfo variables which are used later in Database
    // tracker().

    // Look at ALL of the bots3 records for this ip, agent, page.
    
    if($this->sql("select robots from $this->masterdb.bots3 where ip='$ip' and agent='$agent' and page='$page'")) { // Found some.
      // Get each record

      while([$robots] = $this->fetchrow('num')) {
        $type = null;

        // BLP 2025-04-04 - Use $robotMap and take apart the information
        // Changed this to a looping function as the number of $robots items got too big.

        foreach($robotMap as $bit => $flag) {
          if($robots & $bit) {
            $this->botAsBits |= $bit;
            if(isset($flag)) {
              $this->trackerBotInfo |= $flag;
            }
          }
        }
        
        // Now $this->trackerBotInfo and $this->botAsBits are set.
      }

      if($this->botAsBits & BOTS_HAS_DIFFTIME) { // true if difftime was present
        error_log("***dbPdo remove bot bit: ip=$ip, agent=$agent, page=$page, line=". __LINE__);
        $this->trackerBotInfo &= ~TRACKER_BOT; // BLP 2025-04-14 - If we have difftime remove the bots bit from trackerBotInfo.
        $this->botAsBits &= ~BOTS_SITECLASS; // BLP 2025-04-14 - remove bot from botAsBits.
      }

      $hexTrackerBotInfo = dechex($this->trackerBotInfo);
      $hexBotAsBits = dechex($this->botAsBits);
      //error_log("***dbPdo: ip=$ip, agent=$agent, page=$page, trackerBotInfo=$hexTrackerBotInfo,
      //botAsBits=$hexBotAsBits");
      
      // BLP 2025-03-09 - $this->botAsBits and $this->trackerBotInfo used in tracker in Database.
      // Now only if botAsBits has the BOTS_SITECLASS bit set is this a bot.

      if($this->botAsBits & BOTS_SITECLASS) { //if not zero
        $this->isBot = true;
      }
    }

    // isBot may be true because 1) BOTS_MATCH, 2) BOTS_NOAGENT 3) BOTS_GOODBOT or 4) found in bots
    // table. If the agent mastched the list in the preg_match at the top, then BOTS_MATCH.
    // If the agent had an http address for information then BOTS_GOODBOT is set.
    // If there was 'no agent' then BOTS_NOAGENT is set.
    // If any of the above happened then isBot is true.
    // If we did not find anything then $this->botAsBits=0 and isBot=false.

    return $this->isBot;
  }

  /**
   * setSiteCookie()
   * @return bool true if OK else false
   * BLP 2021-12-20 -- add $secure, $httponly and $samesite as default
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
      error_log("Database setSiteCookie: failed, site=$this->siteName, page=$this->self, line=". __LINE__);
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
