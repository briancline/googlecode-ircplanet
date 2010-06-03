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
	
	db_queryf("delete from stats_servers");
	db_queryf("delete from stats_users");
	db_queryf("delete from stats_channels");
	db_queryf("delete from stats_channel_users");
	
	foreach ($this->servers as $num => $server) {
		db_queryf("insert into stats_servers (server_name, `desc`, start_date, max_users, isService) ".
			"values ('%s', '%s', '%s', '%s', '%s')",
			$server->getName(),
			$server->getDesc(),
			db_date($server->getStartTs()),
			$server->getMaxUsers(),
			$server->isService()
		);
	}
	
	foreach ($this->users as $numeric => $user) {
		$server = $this->getServer($user->getServerNumeric());
		
		db_queryf("insert into stats_users 
			(nick, ident, host, name, server, modes, account, signon_date)
			values
			('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
			$user->getNick(),
			$user->getIdent(),
			$user->getHost(),
			$user->getName(),
			$server->getName(),
			$user->getModes(),
			$user->getAccountName(),
			db_date($user->getSignonTs())
		);
	}
	
	foreach ($this->channels as $chan_key => $chan) {
		db_queryf("insert into stats_channels (channel_name, topic, modes) values ('%s', '%s', '%s')",
			$chan->getName(),
			$chan->getTopic(),
			$chan->getModes()
		);
		
		foreach ($chan->getUserList() as $numeric) {
			$user = $this->getUser($numeric);
			
			db_queryf("insert into stats_channel_users (channel_name, nick, isOp, isVoice) values 
				('%s', '%s', '%s', '%s')",
				$chan->getName(),
				$user->getNick(),
				$chan->isOp($numeric),
				$chan->isVoice($numeric)
			);
		}
	}
	

