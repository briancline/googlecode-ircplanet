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

	if(!($target = $this->get_user_by_nick($pargs[1])))
	{
		$bot->noticef($user, 'There is no user named %s.', $pargs[1]);
		return false;
	}
	
	$acct_str = '';
	if($target->has_account_name())
	{
		$acct_str = ', logged in as ';
		
		if(!$target->is_logged_in())
			$acct_str .= 'unknown account ';
		
		$acct_str = $target->get_account_name();
		
		if($acct = $this->get_account($target->get_account_name()))
			$acct_str .= ' (Registered on '. get_date($acct->get_register_ts()) .')';
	}
	
	$server = $this->get_server($target->get_server_numeric());
	
	$channels = $target->get_channel_list();
	$chan_list = '';
	foreach($channels as $chan_name)
	{
		$chan = $this->get_channel($chan_name);
		
		if($chan->is_voice($target->get_numeric()))
			$chan_list .= '+';
		
		if($chan->is_op($target->get_numeric()))
			$chan_list .= '@';
		
		$chan_list .= $chan->get_name() .' ';
	}
	
	$bot->noticef($user, 'Nick:         %s (User modes +%s)', $target->get_nick(), $target->get_modes());
	if($target->is_logged_in())
		$bot->noticef($user, 'Account:      %s', $acct_str);
	
	if($target->is_host_hidden())
		$bot->noticef($user, 'Hidden host:  %s', $target->get_full_mask_safe());
		
	$bot->noticef($user, 'Full mask:    %s [%s]', $target->get_full_mask(), $target->get_ip());
	$bot->noticef($user, 'Channels:     %s', $chan_list);
	$bot->noticef($user, 'Server:       %s', $server->get_name());
	$bot->noticef($user, 'Signed on:    '. get_date($target->get_signon_ts()));

