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

	$numerics = array();

	$chan = $this->get_channel( $chan_name );
	if( !$chan )
	{
		$bot->noticef( $user, "Nobody is on channel %s.", $chan_name );
		return false;
	}
	if( !$chan->is_on($bot->get_numeric()) )
	{
		$bot->noticef( $user, 'I am not on %s.', $chan->get_name() );
		return false;
	}
	
	if( $cmd_num_args == 1 )
	{
		$numerics[] = $user->get_numeric();
	}
	else
	{
		for( $i = 2; $i < count($pargs); ++$i )
		{
			$nick = $pargs[$i];
			$tmp_user = $this->get_user_by_nick($nick);
			
			if( !$tmp_user || !$chan->is_on($tmp_user->get_numeric()) )
			{
				$bot->noticef( $user, "The user %s%s%s was not found on channel %s.",
					BOLD_START, $nick, BOLD_END, $chan->get_name() );
				continue;
			}
			
			$numerics[] = $tmp_user->get_numeric();
			$chan->add_op( $tmp_user->get_numeric() );
		}
	}
	
	$bot->op( $chan->get_name(), $numerics );

?>
