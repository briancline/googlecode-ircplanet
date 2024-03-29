<?php
/*
 * ircPlanet Services for ircu
 * Copyright (c) 2005 Brian Cline.
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without 
 * modification, are permitted provided that the following conditions are met:

 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 * 3. Neither the name of ircPlanet nor the names of its contributors may be
 *    used to endorse or promote products derived from this software without 
 *    specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

	require('globals.php');
	require('../Core/service.php');
	require(SERVICE_DIR .'/db_gline.php');
	require(SERVICE_DIR .'/db_badchan.php');
	require(SERVICE_DIR .'/db_jupe.php');
	
	
	class OperatorService extends Service
	{
		var $pending_events = array();
		var $db_glines = array();
		var $db_jupes = array();
		var $db_badchans = array();

		function serviceConstruct()
		{
		}
		
		
		function serviceDestruct()
		{
		}
		

		function serviceLoad()
		{
			$this->loadGlines();
			$this->loadBadchans();

			if (defined('TOR_GLINE') && TOR_GLINE == true) {
				if (!defined('TOR_DURATION'))
					die('tor_gline is enabled, but tor_duration was not defined!');
				if (convertDuration(TOR_DURATION) == false)
					die('The duration specified in tor_duration is invalid!');
				if (!defined('TOR_REASON') || TOR_REASON == '')
					die('tor_gline is enabled, but tor_reason was not defined!');
			}
			
			if (defined('COMP_GLINE') && COMP_GLINE == true) {
				if (!defined('COMP_DURATION'))
					die('comp_gline is enabled, but comp_duration was not defined!');
				if (convertDuration(COMP_DURATION) == false)
					die('The duration specified in comp_duration is invalid!');
				if (!defined('COMP_REASON') || COMP_REASON == '')
					die('comp_gline is enabled, but comp_reason was not defined!');
			}
			
			if (defined('CLONE_GLINE') && CLONE_GLINE == true) {
				if (!defined('CLONE_MAX'))
					die('clone_gline is enabled, but clone_max was not defined!');
				if (!is_numeric(CLONE_MAX) || CLONE_MAX == 0)
					die('Invalid value specified for clone_max!');
				if (!defined('CLONE_DURATION'))
					die('clone_gline is enabled, but clone_duration was not defined!');
				if (convertDuration(CLONE_DURATION) == false)
					die('The duration specified in clone_duration is invalid!');
				if (!defined('CLONE_REASON') || CLONE_REASON == '')
					die('clone_gline is enabled, but clone_reason was not defined!');
			}
		}
		
		
		function servicePreburst()
		{
		}
		
		
		function servicePostburst()
		{
			foreach ($this->db_glines as $key => $db_gline) {
				$this->addGline($db_gline->getMask(), $db_gline->getRemainingSecs(), 
						$db_gline->getSetTs(), $db_gline->getReason());
				$this->enforceGline($db_gline->getMask());
			}
			
			$bot_num = $this->default_bot->getNumeric();
			foreach ($this->default_bot->channels as $chan_name) {
				$chan = $this->getChannel($chan_name);
				
				if (!$chan->isOp($bot_num))
					$this->op($chan->getName(), $bot_num);
			}

			foreach ($this->pending_events as $event) {
				extract($event);
				$this->default_bot->messagef($chan_name, '[%'. (NICK_LEN + $margin) .'s] %s %s',
					$source, $event_name, $misc);

				$this->pending_events = array();
			}
		}
		
		
		function servicePreread()
		{
		}
		

		function serviceClose($reason = 'So long, and thanks for all the fish!')
		{
			foreach ($this->users as $numeric => $user) {
				if ($user->isBot()) {
					$this->sendf(FMT_QUIT, $numeric, $reason);
					$this->removeUser($numeric);
				}
			}
		}

		
		function serviceMain()
		{
		}
		
		
		function loadGlines()
		{
			$res = db_query('select * from os_glines order by gline_id asc');
			while ($row = mysql_fetch_assoc($res)) {
				$gline = new DB_Gline($row);
				
				if ($gline->isExpired()) {
					$gline->delete();
					continue;
				}

				$gline_key = strtolower($gline->getMask());
				$this->db_glines[$gline_key] = $gline;
			}

			debugf('Loaded %d g-lines.', count($this->db_glines));
		}


		function loadJupes()
		{
			$res = db_query('select * from os_jupes order by jupe_id asc');
			while ($row = mysql_fetch_assoc($res)) {
				$jupe = new DB_Jupe($row);
				
				if ($jupe->isExpired()) {
					$jupe->delete();
					continue;
				}

				$jupe_key = strtolower($jupe->getServer());
				$this->db_jupes[$jupe_key] = $jupe;
			}

			debugf('Loaded %d jupes.', count($this->db_jupes));
		}


		function loadBadchans()
		{
			$res = db_query('select * from os_badchans order by badchan_id asc');
			while ($row = mysql_fetch_assoc($res)) {
				$badchan = new DB_BadChan($row);
				
				$badchan_key = strtolower($badchan->getMask());
				$this->db_badchans[$badchan_key] = $badchan;
			}

			debugf('Loaded %d badchans.', count($this->db_badchans));
		}


		function getDbGline($host)
		{
			$gline_key = strtolower($host);
			if (array_key_exists($gline_key, $this->db_glines))
				return $this->db_glines[$gline_key];

			return false;
		}


		function getDbJupe($server)
		{
			$jupe_key = strtolower($server);
			if (array_key_exists($jupe_key, $this->db_jupes))
				return $this->db_jupes[$jupe_key];

			return false;
		}




		function serviceAddGline($host, $duration, $lastmod, $reason)
		{
			if ($this->getDbGline($host))
				return false;

			$gline = new DB_Gline();
			$gline->setTs($lastmod);
			$gline->setMask($host);
			$gline->setDuration($duration);
			$gline->setReason($reason);
			$gline->save();

			$gline_key = strtolower($host);
			$this->db_glines[$gline_key] = $gline;
		}


		function serviceRemoveGline($host)
		{
			$gline = $this->getDbGline($host);

			if (!$gline)
				return false;
			
			$gline->delete();
			$gline_key = strtolower($host);
			unset($this->db_glines[$gline_key]);
		}


		function serviceAddJupe($server, $duration, $last_mod, $reason)
		{
			$jupe = $this->getDbJupe($server);
			
			if (!$jupe)
				return false;

			$db_jupe = new DB_Jupe();
			$db_jupe->setServer($jupe->getServer());
			$db_jupe->setDuration($jupe->getExpireTs() - time());
			$db_jupe->setLastMod($jupe->getLastMod());
			$db_jupe->setTs(time());
			$db_jupe->setReason($jupe->getReason());
			$db_jupe->setActive($jupe->isActive());
			$db_jupe->save();

			$jupe_key = strtolower($server);
			$this->db_jupes[$jupe_key] = $jupe;
		}


		function serviceRemoveJupe($server)
		{
			$jupe = $this->getDbJupe($server);

			if (!$jupe)
				return false;
			
			$jupe->delete();
			$jupe_key = strtolower($server);
			unset($this->db_jupes[$jupe_key]);
		}

		
		/**
		 * isBlacklistedDns is a generic function to provide extensibility
		 * for easily checking DNS based blacklists. It has three arguments:
		 * 	host:    The IP address of the host you wish to check.
		 * 	suffix:    The DNS suffix for the DNSBL service.
		 *    pos_resp:  An array containing responses that should be considered
		 * 	           a positive match. If not provided, will assume that ANY
		 * 	           successful DNS resolution against the DNSBL should be
		 * 	           considered a positive match.
		 * 
		 * For example:
		 * 	isBlacklistedDns('1.2.3.4', 'dnsbl.com')
		 * 		Returns true if 4.3.2.1.dnsbl.com returns any DNS resolution.
		 * 	isBlacklistedDns('1.2.3.4', 'dnsbl.com', 2)
		 * 		Returns true if 4.3.2.1.dnsbl.com contains '127.0.0.2' in its 
		 * 		response.
		 * 	isBlacklistedDns('1.2.3.4', 'dnsbl.com', array(2, 3))
		 * 		Returns true if 4.3.2.1.dnsbl.com contains either 127.0.0.2 or 
		 * 		127.0.0.3 in its response.
		 */
		function isBlacklistedDns($host, $dns_suffix, $pos_responses = -1)
		{
			// Don't waste time checking private class IPs.
			if (isPrivateIp($host))
				return false;
			
			$start_ts = microtime(true);
			
			/**
			 * DNS blacklists work by storing records for ipaddr.dnsbl.com,
			 * but with DNS all octets are reversed. So to check if 1.2.3.4
			 * is blacklisted in a DNSBL, we need to query for the hostname
			 * 4.3.2.1.dnsbl.com.
			 */
			$octets = explode('.', $host);
			$reverse_octets = implode('.', array_reverse($octets));
			$lookup_addr = $reverse_octets .'.'. $dns_suffix .'.';

			debugf('DNSBL checking %s', $lookup_addr);
			$dns_result = @dns_get_record($lookup_addr, DNS_A);

			if (count($dns_result) > 0) {
				$dns_result = $dns_result[0]['ip'];
				$resolved = true;
			}
			else {
				$dns_result = $lookup_addr;
				$resolved = false;
			}
			
			$end_ts = microtime(true);
			debugf('DNSBL check time elapsed: %0.4f seconds (%s = %s)', 
					$end_ts - $start_ts, $lookup_addr, $dns_result);
			
			// If it didn't resolve, don't check anything
			if (!$resolved)
				return false;
			
			// Check for any successful resolution
			if ($resolved && $pos_responses == -1 || empty($pos_responses))
				return true;
			
			// Check for a match against the provided string
			if (is_string($pos_responses) && !empty($pos_responses)
			 		&& $dns_result == ('127.0.0.'. $pos_responses))
				return true;
			
			// Check for a match within the provided array
			if (is_array($pos_responses)) {
				foreach ($pos_responses as $tmp_match) {
					$tmp_match = '127.0.0.'. $tmp_match;
					if ($tmp_match == $dns_result)
						return true;
				}
			}
			
			// All checks failed; host tested negative.
			return false;
		}
		
		
		function isTorHost($host)
		{
			/**
			 * The TOR DNSBL will return 127.0.0.1 as the address for a host
			 * if it is a Tor server or exit node, and 127.0.0.2 if the host
			 * is neither but one exists on the same class C subnet. We don't
			 * care if there's one on the subnet, only if the host we query
			 * for is actually a Tor server or exit node.
			 * 
			 * For more information on the TOR DNSBL, please see
			 * http://www.sectoor.de/tor.php.
			 */
			
			/**
			 * We use multiple Tor DNSBLs because sometimes you'll get a
			 * false negative if one DNSBL isn't 100% up-to-date. Rare,
			 * but not impossible.
			 */
			$blacklists = array(
				'tor.dnsbl.sectoor.de' => array(1),
				'tor.dan.me.uk'        => array(100),
				'tor.ahbl.org'         => array(2)
			);

			foreach ($blacklists as $dns_suffix => $responses) {
				if ($this->isBlacklistedDns($host, $dns_suffix, $responses))
					return true;
			}
			
			return false;
		}
		
		
		function isCompromisedHost($host)
		{
			/**
			 * To determine if a host is compromised, check a myriad of public
			 * DNSBL services (some are IRC-centric) to see if they are listed.
			 */
			$blacklists = array(
				'ircbl.ahbl.org'      => array(2),
				'dnsbl.dronebl.org'   => array(),
				'dnsbl.proxybl.org'   => array(2),
				'rbl.efnetrbl.org'    => array(1, 2, 3, 4),
				'dnsbl.swiftbl.net'   => array(2, 3, 4, 5),
				'cbl.abuseat.org'     => array(2),
				'xbl.spamhaus.org'    => array(),
				'drone.abuse.ch'      => array(2, 3, 4, 5),
				'httpbl.abuse.ch'     => array(2, 3, 4),
				'spam.abuse.ch'       => array(2)
			);
			
			foreach ($blacklists as $dns_suffix => $responses) {
				if ($this->isBlacklistedDns($host, $dns_suffix, $responses))
					return true;
			}
			
			return false;
		}
		
		
		function getBadchan($mask)
		{
			$mask = strtolower($mask);
			if (array_key_exists($mask, $this->db_badchans))
				return $this->db_badchans[$mask];

			return false;
		}


		function isBadchan($chan_name)
		{
			if (isChannel($chan_name))
				$chan_name = $chan_name->getName();

			foreach ($this->db_badchans as $b_key => $badchan) {
				if ($badchan->matches($chan_name))
					return true;
			}

			return false;
		}


		function addBadchan($mask)
		{
			if ($this->getBadchan($mask) != false)
				return false;

			$badchan = new DB_BadChan();
			$badchan->setMask($mask);
			$badchan->save();

			$key = strtolower($mask);
			$this->db_badchans[$key] = $badchan;

			return $this->db_badchans[$key];
		}


		function removeBadchan($mask)
		{
			$badchan = $this->getBadchan($mask);
			if ($badchan == false)
				return false;

			$key = strtolower($mask);
			unset($this->db_badchans[$key]);
			$badchan->delete();

			return true;
		}


		function getUserLevel($user_obj)
		{
			$acct_id = $user_obj;
			
			if (is_object($user_obj) && isUser($user_obj)) {
				if (!$user_obj->isLoggedIn())
					return 0;
				
				$acct_id = $user_obj->getAccountId();
			}
			
			$res = db_query("select `level` from `os_admins` where user_id = ". $acct_id);
			if ($res && mysql_num_rows($res) > 0) {
				$level = mysql_result($res, 0);
				mysql_free_result($res);
				return $level;
			}
			
			return 0;
		}
		
		
		function reportCommand($command_name, $source, $arg1 = "", $arg2 = "", $arg3 = "", $arg4 = "", $arg5 = "")
		{
			$command_name = BOLD_START . $command_name . BOLD_END;
			return $this->reportEvent($command_name, $source, $arg1, $arg2, $arg3, $arg4, $arg5, true);
		}
		
		
		function reportEvent($event_name, $source, $arg1 = "", $arg2 = "", $arg3 = "", $arg4 = "", $arg5 = "", $is_command = false)
		{
			if ((!$is_command && !REPORT_EVENTS) || ($is_command && !REPORT_COMMANDS))
				return;

			if ($is_command)
				$channel = COMMAND_CHANNEL;
			else
				$channel = EVENT_CHANNEL;
			
			$bot = $this->default_bot;
			
			if (isServer($source))
				$source = BOLD_START . $source->getNameAbbrev(NICK_LEN) . BOLD_END;
			elseif (isUser($source))
				$source = $source->getNick();
			
			for ($i = 1; $i <= 5; $i++) {
				eval('$arg = $arg'. $i .';');

				if (!is_object($arg)) {
					continue;
				}
				
				if (isServer($arg) || isChannel($arg))
					$arg = $arg->getName();
				elseif (isUser($arg))
					$arg = $arg->getNick();
				
				eval('$arg'. $i .' = $arg;');
			}
			
			if (strlen($source) > NICK_LEN)
				$source = substr($source, 0, NICK_LEN);
			
			$margin = substr_count($source, BOLD_START);
			$misc = $arg1 .' '. $arg2 .' '. $arg3 .' '. $arg4 .' '. $arg5;
			$misc = trim($misc);
			
			if (!$this->finished_burst) {
				$this->pending_events[] = array(
					'chan_name'   => $channel,
					'margin'      => $margin,
					'source'      => $source,
					'event_name'  => $event_name,
					'misc'        => $misc);
			}

			$bot->messagef($channel, '[%'. (NICK_LEN + $margin) .'s] %s %s',
				$source, $event_name, $misc);

/*
			if ($this->finished_burst)
				$bot->messagef($channel, "[%". (NICK_LEN + $margin) ."s] %s %s", $source, $event_name, $misc);
*/
			
			return true;
		}
	}
	
	$os = new OperatorService();


