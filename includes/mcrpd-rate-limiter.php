<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MCRPD_Rate_Limiter {

	/**
	 * Check if the given IP address is rate-limited.
	 * If limited, outputs a 429 JSON response and exits.
	 *
	 * @param string $ip The client IP address.
	 */
	public static function check( $ip ) {
		if ( empty( $ip ) ) {
			return; // Bypass check if IP cannot be resolved.
		}

		// Allow modifying rate limits (default: 60 requests in 60 seconds)
		$config = apply_filters( 'mcrpd_rate_limit_config', array(
			'limit'  => 60,
			'window' => 60,
		) );

		$transient_key = 'mcrpd_rl_' . md5( $ip );
		$data          = get_transient( $transient_key );
		$current_time  = time();

		if ( false === $data ) {
			// First request in this window
			$data = array(
				'count'      => 1,
				'reset_time' => $current_time + $config['window'],
			);
			set_transient( $transient_key, $data, $config['window'] );
		} else {
			if ( ! is_array( $data ) ) {
				// Re-initialize if data got corrupted or legacy transient structure exists
				$data = array(
					'count'      => 1,
					'reset_time' => $current_time + $config['window'],
				);
				set_transient( $transient_key, $data, $config['window'] );
				return;
			}

			if ( $current_time > $data['reset_time'] ) {
				// Reset window if it somehow survived beyond reset_time
				$data = array(
					'count'      => 1,
					'reset_time' => $current_time + $config['window'],
				);
				set_transient( $transient_key, $data, $config['window'] );
			} else {
				$data['count']++;
				
				if ( $data['count'] > $config['limit'] ) {
					// Log the rate limit event
					global $slm_debug_logger;
					if ( isset( $slm_debug_logger ) ) {
						$slm_debug_logger->log_debug( "Rate Limit Exceeded for IP: $ip (Count: {$data['count']})" );
					}
					
					status_header( 429 );
					$args = array(
						'result'     => 'error',
						'message'    => 'Rate limit exceeded. Please try again later.',
						'error_code' => SLM_Error_Codes::RATE_LIMIT_EXCEEDED,
					);
					SLM_API_Utility::output_api_response( $args );
				}
				
				// Save updated count, preserving remaining TTL
				$remaining = $data['reset_time'] - $current_time;
				if ( $remaining > 0 ) {
					set_transient( $transient_key, $data, $remaining );
				}
			}
		}
	}
}
