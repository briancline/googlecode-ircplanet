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
	
	$acct_name = $pargs[1];
	$level = $pargs[2];
	
	if( !($acct = $this->get_account($acct_name)) )
	{
		$bot->noticef( $user, 'The account %s does not exist.', $acct_name );
		return false;
	}
	
	if( !is_numeric($level) )
	{
		$bot->noticef( $user, 'The level you specified is not numeric.' );
		return false;
	}
	
	if( $level >= $user_level )
	{
		$bot->noticef( $user, 'You cannot set someone\'s level higher than or equal to your own.' );
		return false;
	}
	
	if( $level <= 0 )
	{
		$bot->noticef( $user, 'The level must be greater than zero.' );
		return false;
	}
	
	$curr_level = $this->get_user_level( $acct->get_id() );
	if( $curr_level > 0 )
	{
		$bot->noticef( $user, '%s already has level %s access.', $acct->get_name(), $curr_level );
		return false;
	}
	
	db_query( "insert into `ds_admins` (user_id, level) values ('". $acct->get_id() ."', '$level')" );
	$bot->noticef( $user, '%s now has level %d access.', $acct->get_name(), $level );

?>
