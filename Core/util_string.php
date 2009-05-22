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
	
	function right( $str, $len )
	{
		return substr( $str, (-1 * $len) );
	}
	
	
	function is_valid_email( $email )
	{
		$b = eregi( '^[a-z0-9._-]+@[a-z0-9._-]+\.[a-z]{2,4}$', $email );
		
		return $b;
	}
	
	
	function is_ip( $s )
	{
		return eregi( '^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$', $s );
	}


	function is_private_ip( $ip )
	{
		$private_ranges = array(
			'0.0.0.0/8',	  // Self-identification
			'1.0.0.0/8',      // IANA Unallocated
			'2.0.0.0/8',      // IANA Unallocated
			'5.0.0.0/8',      // IANA Unallocated
			'10.0.0.0/8',     // Private networks
			'127.0.0.0/8',    // Loopback
			'169.254.0.0/16', // DHCP Self-assignment
			'172.16.0.0/12',  // Private networks
			'192.168.0.0/16'  // Private networks
		);
		
		foreach( $private_ranges as $range )
		{
			list( $range_start, $range_mask ) = explode( '/', $range );
			$tmp_mask = 0xffffffff << ( 32 - $range_mask );
			$tmp_range_mask = ip2long( $range_start ) & $tmp_mask;
			$tmp_ip_mask = ip2long( $ip ) & $tmp_mask;

			if( $tmp_ip_mask == $tmp_range_mask )
				return true;
		}

		return false;
	}


	function fix_host_mask( $mask )
	{
		$ex_pos = strpos( $mask, '!' );
		$at_pos = strpos( $mask, '@' );
		$ident = substr( $mask, $ex_pos + 1, $at_pos - $ex_pos - 1 );
		
		if( strlen($ident) > IDENT_LEN )
		{
			$mask = substr($mask, 0, $ex_pos) .'!*'. right($ident, IDENT_LEN - 1) . substr($mask, $at_pos);
		}
		
		return $mask;
	}
	

	function line_num_args( $s )
	{
		$tokens = 1;
		$s = trim($s);
		$len = strlen( $s );
		
		if($len == 0)
			return 0;
		
		for( $i = 0; $i < strlen($s) - 1; ++$i )
		{
			if( $s[$i] == ' ' && $s[$i + 1] == ':' )
			{
				$tokens++;
				break;
			}
			else if( $s[$i] == ' ' )
			{
				$tokens++;
			}
		}
		
		return $tokens;
	}
	
	
	function line_get_args( $s, $stop_at_colon = true )
	{
		$start = 0;
		$tokens = array();
		$s = trim( $s );
		$len = strlen( $s );
		
		if( $len == 0 )
			return 0;
		
		for( $i = 0; $i < $len; ++$i )
		{
			if( $stop_at_colon && ($s[$i] == ' ' && $i < ($len - 1) && $s[$i + 1] == ':') )
			{
				$tokens[] = substr( $s, $start, $i - $start );
				$tokens[] = substr( $s, $i + 2 );
				break;
			}
			else if( $s[$i] == ' ' )
			{
				$tokens[] = substr( $s, $start, $i - $start );
				$start = $i + 1;
			}
			else if( $i == ($len - 1) )
			{
				$tokens[] = substr( $s, $start );
			}
		}
		
		return $tokens;
	}
	
	
	function get_pretty_size( $bytes )
	{
		$units = array( 'bytes', 'KB', 'MB', 'GB', 'TB', 'PB' );
		$precision = 2;
		
		for( $i = 0; $bytes >= 1024; ++$i )
			$bytes /= 1024;
		
		if( $i > 0 )
			$bytes = sprintf( '%0.'. $precision .'f', $bytes );
		
		return ($bytes .' '. $units[$i]);
	}
	
	
	/**
	 * irc_sprintf provides a cleaner way of sending services-specific data structures
	 * to sprintf without having to repeatedly call the desired member functions. Since
	 * we almost always use the same ones, irc_sprintf takes a lot of legwork out of
	 * the picture and makes for cleaner code.
	 *
	 * The custom flags that can be used with irc_sprintf follow:
	 *  %A    A space-delimited string representing all of an array's elements.
	 *        Designed for string or numeric arrays only.
	 *  
	 *  %H    Human-readable name of the referenced object.
	 *        For channels:  channel name.
	 *        For servers:   full server name.
	 *        For users:     nick name.
	 *        For glines:    the full mask of the gline.
	 *  
	 *  %C    Same as %H. Pneumonically represents channel names; provided as an extra
	 *        flag only for readability in longer format strings.
	 *  
	 *  %N    The ircu numeric of the referenced object.
	 *        For servers:   two-character server numeric (ex., Sc).
	 *        For users:     five-character server+user numeric (ex., ScAAA).
	 *  
	 *  %U    The account name of the referenced object.
	 *        For users:     user's logged-in account name, if any.
	 *        For accounts:  account name.
	 *  
	 * Examples:
	 *    sprintf('%s', $user_obj->get_nick());    // Nick name
	 *    irc_sprintf('%H', $user_obj);            // Nick name
	 *    
	 *    sprintf('%s', $server_obj->get_name());  // Server name
	 *    irc_sprintf('%H', $server_obj);          // Server name
	 * 
	 * Note: irc_sprintf ONLY supports basic formatting of IRC objects, such as '%N'.
	 *       It is not yet possible to format these the same way as string objects
	 *       using alignment, padding, or width specifiers. Formatting of basic types
	 *       is still possible.
	 *
	 * TODO: Rewrite to replace custom flag type with 's', and replacing corresponding
	 *       input with string. Should allow for printf-style formatting of objects,
	 *       such as %'#-12N.
	 */    
	function irc_sprintf( $format )
	{
		$valid_flag_list = 'ACHNU';

		$args = func_get_args();
		array_shift( $args );
		
		$f = -1;
		$len = strlen( $format );
		$pct_count = 0;

		for( $i = 0; $i < $len - 1; $i++ )
		{
			$char = $format[$i];
			$next = $format[$i + 1];

			if( $char == '%' )
				$pct_count++;
			else
				$pct_count = 0;

			if( $pct_count != 1 || $next == '%' )
				continue;

			$f++;

			if( false === strpos($valid_flag_list, $next) )
				continue;

			$input = $args[$f];
			$text = '';

			switch( $next )
			{
				case 'A':
					$text = implode( ' ', $input );
					break;

				case 'C':
				case 'H':
					if( is_user($input) )
						$text = $input->get_nick();
					elseif( is_channel($input) || is_server($input) )
						$text = $input->get_name();
					elseif( is_gline($input) )
						$text = $input->get_mask();

					break;

				case 'N':
					if( is_user($input) || is_server($input) )
						$text = $input->get_numeric();

					break;

				case 'U':
					if( is_user($input) )
						$text = $input->get_account_name();
					elseif( is_account($input) )
						$text = $input->get_name();

					break;

				default:
					continue;
			}
			
			if( $i < ($len - 1) )
				$rhs_start_pos = $i + 2;
			else
				$rhs_start_pos = $len - 1;

			$lhs = substr( $format, 0, $i );
			$rhs = substr( $format, $rhs_start_pos );

			$format = $lhs . $text . $rhs;
			unset( $args[$f] );

			$i += strlen( $text );
			$len = strlen( $format );
		}

		return vsprintf( $format, $args );
	}
	

	function random_kick_reason()
	{
		$ban_reasons = array(
			"Don't let the door hit you on the way out!",
			"Sorry to see you go... actually no, not really.",
			"This is your wake-up call...",
			"Behave yourself!",
			"Ooh, behave...",
			"Not today, child.",
			"All your base are belong to me",
			"Watch yourself!",
			"Better to remain silent and be thought a fool than to speak out and remove all doubt.",
			"kthxbye."
		);
		
		$index = rand(0, count($ban_reasons) - 1);
		return $ban_reasons[$index];
	}

	

?>