<?php
	/**
	 * OAuth encoding procedures are taken from yelp's github in order
	 * to make requests to yelp's API
	 * 
	 * Please refer to https://github.com/Yelp/yelp-api/tree/master/v2/php
	 * for the example code provided
	 */
	require_once('lib/OAuth.php');

	// Setting OAuth credentials
	$GLOBALS['CONSUMER_KEY'] = 'k1vwbCTu-We08w-Aq2wfLw';
	$GLOBALS['CONSUMER_SECRET'] = '3UlB8vN6PemIY6f0IcMjQJH63zs';
	$GLOBALS['TOKEN'] = '6vreJsZDVQs5c0yHt6mUJ9Y4YOTOGDCZ';
	$GLOBALS['TOKEN_SECRET'] = '57hHvoRSwQMPnXurNi_btwe5obw';
	$GLOBALS['API_HOST'] = 'api.yelp.com';
	$GLOBALS['DEFAULT_TERM'] = 'Manna Korean bbq';
	$GLOBALS['DEFAULT_LOCATION'] = 'San Diego, CA';
	$GLOBALS['SEARCH_LIMIT'] = 5; 
	$GLOBALS['SEARCH_PATH'] = '/v2/search/';
	$GLOBALS['BUSINESS_PATH'] = '/v2/business/';


	/** 
	 * Makes a request to the Yelp API and returns the response
	 * Function taken from https://github.com/Yelp/yelp-api/tree/master/v2/php
	 * 
	 * @param    $host    The domain host of the API 
	 * @param    $path    The path of the APi after the domain
	 * @return   The JSON response from the request      
	 */
	function request($host, $path) {
	    $unsigned_url = "https://" . $host . $path;
	    // Token object built using the OAuth library
	    $token = new OAuthToken($GLOBALS['TOKEN'], $GLOBALS['TOKEN_SECRET']);
	    // Consumer object built using the OAuth library
	    $consumer = new OAuthConsumer($GLOBALS['CONSUMER_KEY'], $GLOBALS['CONSUMER_SECRET']);
	    // Yelp uses HMAC SHA1 encoding
	    $signature_method = new OAuthSignatureMethod_HMAC_SHA1();
	    $oauthrequest = OAuthRequest::from_consumer_and_token(
	        $consumer, 
	        $token, 
	        'GET', 
	        $unsigned_url
	    );
	    
	    // Sign the request
	    $oauthrequest->sign_request($signature_method, $consumer, $token);
	    
	    // Get the signed URL
	    $signed_url = $oauthrequest->to_url();
	    
	    // Send Yelp API Call
	    try {
	        $ch = curl_init($signed_url);
	        if (FALSE === $ch)
	            throw new Exception('Failed to initialize');
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	        curl_setopt($ch, CURLOPT_HEADER, 0);
	        $data = curl_exec($ch);
	        if (FALSE === $data)
	            throw new Exception(curl_error($ch), curl_errno($ch));
	        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	        if (200 != $http_status)
	            throw new Exception($data, $http_status);
	        curl_close($ch);
	    } catch(Exception $e) {
	        trigger_error(sprintf(
	            'Curl failed with error #%d: %s',
	            $e->getCode(), $e->getMessage()),
	            E_USER_ERROR);
	    }
	    
	    return $data;
	}

	/**
	 * Query the Search API by a search term and location 
	 * 
	 * @param    $term        The search term passed to the API 
	 * @param    $location    The search location passed to the API 
	 * @return   The JSON response from the request 
	 */
	function search($term, $location) {
	    $url_params = array();
	    
	    $url_params['term'] = $term ?: $GLOBALS['DEFAULT_TERM'];
	    $url_params['location'] = $location ?: $GLOBALS['DEFAULT_LOCATION'];
	    $url_params['limit'] = $GLOBALS['SEARCH_LIMIT'];
		$url_params['sort'] = 0;
		$url_params['radius_filter'] = 20000;
		$search_path = $GLOBALS['SEARCH_PATH'] . "?" . http_build_query($url_params);
	    
		//return $search_path;
		return request($GLOBALS['API_HOST'], $search_path);
	}
	/**
	 * Query the Business API by business_id
	 * 
	 * @param    $business_id    The ID of the business to query
	 * @return   The JSON response from the request 
	 */
	function get_business($business_id) {
	    $business_path = $GLOBALS['BUSINESS_PATH'] . urlencode($business_id);
	    
	    return request($GLOBALS['API_HOST'], $business_path);
	}

?>
