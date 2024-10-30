<?php
/*
Plugin Name:  Serpbook WordPress Integration
Plugin URI:   https://kijo.co/serpbook-wp
Description:  Display Serpbook SERP Data
Version:      0.1
Author:       KIJO
Author URI:   https://kijo.co
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  serpbook-wp
Domain Path:  /languages
*/

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class Serpbook_WP {

  private $api_url = 'https://serpbook.com/serp/api/';

	/**
	 * Return a value, using a desired retrieval method.
	 * @access  public
	 * @param  string $key option key.
	 * @param  string $default default value.
	 * @param  string $section field section.
	 * @since   1.0.0
	 * @return  mixed Returned value.
	 */
	private function ssrt_get_value ( $key, $default, $section ) {
		$values = get_option( 'serpbook-api-' . $section, array() );

		if ( is_array( $values ) && isset( $values[$key] ) ) {
			$response = $values[$key];
		} else {
			$response = $default;
		}

		return $response;
	} // End ssrt_get_value()


  /*
  *  cURL
  *  Pass in a URL and have it sent via cURL
  */

  private function ssrt_curl( $url ) {

    // Init cURL
    $curl = curl_init();

    // cURL options
    curl_setopt_array( $curl, array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      //CURLOPT_TIMEOUT => 30,
      //CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      //CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "cache-control: no-cache"
      ),
    ));

    // Response
    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    return $response;

  }


  /*
  *  Get All Categories on account
  */

  public function ssrt_get_all_categories() {

    // Build URL
    $url = $this->api_url . '?action=getcategories&auth=' . $this->ssrt_get_value ( 'apikey', '', 'standard-fields' );

    $api_response = $this->ssrt_curl( $url );

    return json_decode( $api_response );

  }

	public function ssrt_get_data_for_selected_company($sb_view_key_url = "https://serpbook.com/serp/api/?viewkey=h2bi55n&auth=e6e6394158f7b8a0d08906428c9789c6") {

		$response = $this->ssrt_curl( $sb_view_key_url );

		return json_decode( $response );
	}

  /*
  *  Get Individual Category
  */

  public function ssrt_get_individual_category( $sb_cat_url ) {

    $response = $this->ssrt_curl( $sb_cat_url );

    return json_decode( $response );

  }


}
