<?php
/**
 * Plugin Name: ElastiQuery Tester
 * Description: Tool for testing Elasticsearch queries from the dashboard.
 * Plugin URI:	https://github.com/openfcci/
 * Author:			Forum Communications Company
 * Author URI:	http://www.forumcomm.com/
 * License:		 GPL v2 or later
 * Version:		 1.17.03.01
 */

/**
 * NOTE: using "fields" in the query to restrict the returned fields,
 * the resulting JSON will be different and the response table will break.
 */

/*--------------------------------------------------------------
 # PLUGIN SETUP
 --------------------------------------------------------------*/

/*
 * Enqueue scripts and styles
 */
function elastiquery_tester_style_scripts() {
	wp_enqueue_script( 'eqt_ajax', plugin_dir_url( __FILE__ ) . '/includes/js/eqt_ajax.js' );
	wp_enqueue_style( 'elastiquery-tester-css', plugins_url( 'elastiquery-tester.css', __FILE__ ) );
}
add_action( 'admin_enqueue_scripts', 'elastiquery_tester_style_scripts' );

/*
 * Create the admin menu item
 */
function ep_ajax_tester_create_admin_page() {
	$icon = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48IURPQ1RZUEUgc3ZnIFBVQkxJQyAiLS8vVzNDLy9EVEQgU1ZHIDEuMS8vRU4iICJodHRwOi8vd3d3LnczLm9yZy9HcmFwaGljcy9TVkcvMS4xL0RURC9zdmcxMS5kdGQiPjxzdmcgdmVyc2lvbj0iMS4xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiI+PHBhdGggZmlsbD0iIzllYTNhOCIgZD0iTTMuMzc5IDIyLjk0NGgyMS4xMDdjMi4yOTcgMCA0LjM1NCAxLjAyNyA1LjczNyAyLjY0Ni0yLjg5NSAzLjc5OC03LjQ2NiA2LjI1LTEyLjYwNCA2LjI1LTYuMjUzIDAtMTEuNjY0LTMuNjMxLTE0LjIzOS04Ljg5N3pNMi40MTIgMTEuNTUyaDIwLjE5NGMyLjQ2IDAgNC40NTMgMS45OTYgNC40NDggNC40NTYtMC4wMDQgMi40NTQtMS45OTQgNC40NC00LjQ0OCA0LjQ0aC0yMC4xOTRjLTAuNDEzLTEuNDExLTAuNjM0LTIuOTA0LTAuNjM0LTQuNDQ4czAuMjIyLTMuMDM3IDAuNjM0LTQuNDQ4ek0zLjM3OSA5LjA1NmMyLjU3NS01LjI2NiA3Ljk4Ni04Ljg5NiAxNC4yMzktOC44OTYgNS4xMTYgMCA5LjY2OCAyLjQzIDEyLjU2NSA2LjE5OS0xLjM4NSAxLjY0OS0zLjQ2MSAyLjY5OC01Ljc4MyAyLjY5OGgtMjEuMDIxeiI+PC9wYXRoPjwvc3ZnPg==';
	add_menu_page( 'ElastiQuery', 'ElastiQuery', 'edit_pages', 'ep_ajax_tester_admin/ve-admin.php', 'ep_ajax_tester_admin_page', $icon, 49 );
}
add_action( 'admin_menu', 'ep_ajax_tester_create_admin_page' );

/**
 * Admin Page
 */
function ep_ajax_tester_admin_page() {

	$default_query = '{
  "size": 10,
  "query": {
    "query_string": { "query": "*" }
    }
  }
}';

	$html = '<div class="wrap">';
	$html .= '<h2>Elastic Query Tester</h2><br />';
	$html .= '<span class="wp-ui-text-icon" style="line-height: 2;">Query JSON:</span>';
	$html .= '<textarea name="ep_query" rows="15" cols="50" id="ep-ajax-option-id" class="large-text code">' . $default_query . '</textarea>';
	$html .= '<button id="ep-wp-ajax-button" class="button-primary">Test Query</button>';
	$html .= '<br><br><hr>';

	// Response Div
	$html .= '<div style="margin-bottom: 10px;">';
	$html .= '<a href="javascript:void(1)" onclick="jQuery(this).parent().next(&#x27;div&#x27;).slideToggle();" style="background: url(&#x27;images/arrows.png&#x27;) no-repeat; padding-left: 15px;">';
	$html .= '<h3 style="display: inline-block;">JSON Response</h3>';
	$html .= '</a>';
	$html .= '</div>';
	$html .= '<div style="display: none;">'; // set to "display: none;" to unhide by default
	$html .= '<textarea id="ep-response" style="width: 100%; height: 200px;"></textarea>';
	$html .= '</div>';

	$html .= '<table id="ep-ajax-table">
		<thead>
			<tr>
				<th class="eqt-rw--1">Result</th>
				<th class="eqt-rw--2">Site / Index </th>
				<th class="eqt-rw--3">Post Title</th>
				<th class="eqt-rw--4">Post Date</th>
			</tr>
		</thead>
		<tbody>';
	$html .= '</tbody></table>';
	$html .= '</div>';
	echo $html;
}

/*--------------------------------------------------------------
 # PLUGIN FUNCTIONS
 --------------------------------------------------------------*/

function ep_ajax_tester_ajax_handler() {

	/**
	 * Gets the current configuration setting of magic_quotes_gpc
	 * Returns 0 if magic_quotes_gpc is off, 1 otherwise.
	 * Or always returns FALSE as of PHP 5.4.0.
	 * @link http://php.net/manual/en/function.get-magic-quotes-gpc.php
	 */
	if ( get_magic_quotes_gpc() ) {
		$qry = $_POST['id'];
	} else {
		$qry = stripslashes( $_POST['id'] );
	}

	$ch = curl_init();
	$method = 'GET';

	if ( function_exists( 'ep_get_host' ) ) {
		$url = untrailingslashit( ep_get_host() ) . '/*/post/_search?pretty=true';
	} else {
		$url = '127.0.0.1:9200' . '/*/post/_search?pretty=true';
	}

	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, strtoupper( $method ) );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $qry );

	$data = curl_exec( $ch );
	curl_close( $ch );

	echo $data;
	wp_die(); // just to be safe

}
add_action( 'wp_ajax_ep_ajax_tester_approal_action', 'ep_ajax_tester_ajax_handler' );

?>
