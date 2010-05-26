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
	
	foreach($this->servers as $num => $server)
	{
		db_queryf("insert into stats_servers (server_name, `desc`, start_date, max_users, is_service) ".
			"values ('%s', '%s', '%s', '%s', '%s')",
			$server->get_name(),
			$server->get_desc(),
			db_date($server->get_start_ts()),
			$server->get_max_users(),
			$server->is_service()
		);
	}
	
	foreach($this->users as $numeric => $user)
	{
		$server = $this->get_server($user->get_server_numeric());
		
		db_queryf("insert into stats_users 
			(nick, ident, host, name, server, modes, account, signon_date)
			values
			('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
			$user->get_nick(),
			$user->get_ident(),
			$user->get_host(),
			$user->get_name(),
			$server->get_name(),
			$user->get_modes(),
			$user->get_account_name(),
			db_date($user->get_signon_ts())
		);
	}
	
	foreach($this->channels as $chan_key => $chan)
	{
		db_queryf("insert into stats_channels (channel_name, topic, modes) values ('%s', '%s', '%s')",
			$chan->get_name(),
			$chan->get_topic(),
			$chan->get_modes()
		);
		
		foreach($chan->get_user_list() as $numeric)
		{
			$user = $this->get_user($numeric);
			
			db_queryf("insert into stats_channel_users (channel_name, nick, is_op, is_voice) values 
				('%s', '%s', '%s', '%s')",
				$chan->get_name(),
				$user->get_nick(),
				$chan->is_op($numeric),
				$chan->is_voice($numeric)
			);
		}
	}
	

