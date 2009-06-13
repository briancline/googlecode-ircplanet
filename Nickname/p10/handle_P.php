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
	
	$is_public = false;
	$is_private = false;
	$cmd_msg = assemble( $args, 3 );
	$cmd_target = $privmsg_target;
	
	if( empty($chan_key) && array_key_exists($cmd_target, $this->users) && $this->users[$cmd_target]->is_bot() )
	{
		$bot = $this->users[$cmd_target];
		$is_private = true;
	}
	if( !empty($chan_key) && $message[0] == '!' )
	{
		$cmd_msg = substr( $cmd_msg, 1 );
		$bot = $this->default_bot;
		$is_public = true;
	}
	
	if( $is_public || $is_private )
	{
		$user_numeric = $args[0];
		$user = $this->get_user( $user_numeric );
		$pargs = line_get_args( $cmd_msg, false );
		$cmd_name = strtolower( $pargs[0] );
		
		$last_char = substr($cmd_msg, strlen($cmd_msg) - 1);
		$is_ctcp = ($cmd_name[0] == CTCP_START && $last_char == CTCP_END);
		
		if( $is_ctcp )
		{
			$cmd_msg = trim( $cmd_msg, CTCP_START . CTCP_END );
			$cmd_name = trim( $cmd_name, CTCP_START . CTCP_END );
			$cmd_name = "ctcp_". $cmd_name;
		}
		
		$spoofed_ctcp = ( !$is_ctcp && substr($cmd_name, 0, 5) == 'ctcp_' );
		$cmd_handler_file = CMD_HANDLER_DIR . $cmd_name . '.php';
		
		if( ($this->command_exists($cmd_name) || $is_ctcp) && file_exists($cmd_handler_file) && !$spoofed_ctcp )
		{
			$user_level = $this->get_user_level( $user );
			$cmd_level = $this->get_command_level( $cmd_name );
			$cmd_req_args = $this->get_command_arg_count( $cmd_name );
			$cmd_num_args = count( $pargs ) - 1;
			
			if( $user_level >= $cmd_level )
			{
				if( $cmd_num_args >= $cmd_req_args )
				{
					include( $cmd_handler_file );

					/**
					 * Since we use 'return false' statements inside of command handlers, if 
					 * we reach this point then we should report a successful command to the
					 * log channel.
					 */
					if( REPORT_COMMANDS )
					{
						$log_cmd_name = strtoupper( $cmd_name );
						$log_cmd_args = $pargs;
						array_shift( $log_cmd_args );
						$bot->messagef( COMMAND_CHANNEL, '[%-'. NICKLEN .'H] %s%s%s %A',
							$user, BOLD_START, $log_cmd_name, BOLD_END, $log_cmd_args );
					}
				}
				else
				{
					$bot->noticef( $user, "%sSyntax:%s %s %s", BOLD_START, BOLD_END, 
						$cmd_name, $this->get_command_syntax($cmd_name) );
				}
			}
			else
			{
				$bot->noticef( $user, "You do not have enough access to use that command!" );
			}
		}
		else if( !$is_public )
		{
			$bot->noticef( $user->numeric, 
				"Invalid command! Use %sshowcommands%s to get a list of available commands.",
				BOLD_START, BOLD_END );
		}
	}

?>
