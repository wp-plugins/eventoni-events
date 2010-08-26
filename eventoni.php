<?php
/**
 * @package eventoni
 * @author Benjamin Mock
 * @version 2.1
 */
/*
 Plugin Name: eventoni
 Plugin URI: http://eventoni.com/
 Description: Event-Suche
 Author: Benjamin Mock
 Version: 2.1
 Author URI: http://benjaminmock.de/
 */

error_reporting(E_ALL);
ini_set('display_errors', 'On');

$eventoni_visitor_location = false;

$options = get_option('eventoni_options');

/**************************************************************
 * Aktivierung des Plugins
 **************************************************************/
function eventoni_activate() {
	global $wpdb;
	$customization = $wpdb->get_results("SELECT option_value FROM $wpdb->options WHERE option_name = 'eventoni_options' LIMIT 1");
	// pruefen ob Einstellungen bereits gespeichert wurden
	if($wpdb->num_rows < 1) {
		$customization_string = 'a:9:{s:8:"bg_color";s:7:"#121E29";s:9:"placement";s:7:"sidebar";s:8:"show_map";s:4:"true";s:12:"event_search";s:4:"true";s:13:"event_suggest";s:4:"true";s:11:"geolocation";s:0:"";s:16:"eventoni_api_key";s:0:"";s:18:"googlemaps_api_key";s:0:"";}';
		$wpdb->query("INSERT INTO $wpdb->options (blog_id, option_name, option_value, autoload) VALUES ('0', 'eventoni_options', '$customization_string', 'no')");
	}
}
register_activation_hook(__FILE__, 'eventoni_activate');

/**************************************************************
 * Deaktivierung des Plugins:
 * - Eintraege des Plugins werden aus der DB geloescht
 **************************************************************/
function eventoni_deactivate() {
	global $wpdb;
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name = 'eventoni_options'");
}
register_deactivation_hook(__FILE__, 'eventoni_deactivate');

/**************************************************************
 * JavaScript fuer Frontend laden
 **************************************************************/
function eventoni_init() {
	if (!is_admin()) {
		$options = get_option('eventoni_options');
		wp_deregister_script('jquery');
		wp_register_script('jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js', false, '1.3.2', true);
		wp_enqueue_script('jquery');

		wp_deregister_script('google_maps');
		wp_register_script('google_maps', 'http://www.google.com/jsapi?key='.$options['googlemaps_api_key'], false, '1.0', true);
		wp_enqueue_script('google_maps');

		wp_enqueue_script('my_script', get_bloginfo('wpurl').'/wp-content/plugins/eventoni/js/eventoni.js', array('jquery','google_maps'), '1.0', true);
	}
}
add_action('init', 'eventoni_init');

/**************************************************************
 * JavaScript Bibliotheken im Header laden
 **************************************************************/
function eventoni_add_customization_to_header() {
	global $wpdb;

	$options = get_option('eventoni_options');
	$background_color = $options['bg_color'];

	echo '<link type="text/css" rel="stylesheet" href="'.get_bloginfo('wpurl').'/wp-content/plugins/eventoni/css/eventoni_style.css.php?bgc='.urlencode($background_color).'"/>';
	$customization = $wpdb->get_results("SELECT option_value FROM $wpdb->options WHERE option_name = 'eventoni_options' LIMIT 1");
	// SACK-Bibliothek fuer Ajax-Request
	wp_print_scripts( array( 'sack' ));
	?>
<script type="text/javascript">
//<![CDATA[
	eventoni_ajax_url   = '<?php bloginfo( 'wpurl' ); ?>/wp-admin/admin-ajax.php';
	eventoni_plugin_url = '<?php bloginfo( 'wpurl' );?>/wp-content/plugins/eventoni/';
	eventoni_vistor_location  = '';
//]]>
</script>
	<?php
}
add_action('wp_head', 'eventoni_add_customization_to_header');

/**************************************************************
 * Suchfunktion fuer Ticker
 **************************************************************/
function eventoni_ajax_search()
{
	// Anfrage-String aus POST-Daten zusammensetzen
	$query_string = '';
	if(isset($_POST['longitude']) && isset($_POST['latitude']))
	{
		$latitude  = $_POST['latitude'];
		$longitude = $_POST['longitude'];

		$query_string .= '&pt='.$latitude.':'.$longitude;
	}
	else
	{
		$wt =  $_POST['was'];
		if($wt != '' && $wt != 'z.B. Konzert')
		{
			$wt = str_replace (' ', '%20OR%20', $wt);
			$query_string .= '&wt='.$wt;
		}

		$wn =  $_POST['wann'];
		if($wn != '' && $wn != 'z.B. heute')
		{
			$wn = str_replace (' ', '%20OR%20', $wn);
			$query_string .= '&wn='.$wn;
		}

		$wr =  $_POST['wo'];
		if($wr != '' && $wr != 'z.B. Erzhausen')
		{
			$wr = str_replace (' ', '%20OR%20', $wr);
			$query_string .= '&wr='.$wr;
		}
	}

	$page = 0;
	if(isset($_POST['eventoni_page']))
	{
		$page = $_POST['eventoni_page'];
	}
	$query_string .= '&page='.$page;
	// Suchergebnisse abfragen
	$response = eventoni_fetch($query_string, true);
	// Suchergebnisse ausgeben
	echo $response['xml'];
	die();
}
add_action('wp_ajax_do_eventoni_ajax_search', 'eventoni_ajax_search');
add_action('wp_ajax_nopriv_do_eventoni_ajax_search', 'eventoni_ajax_search');

/**************************************************************
 * liefert die ungefähre Gegend des Benutzers über die IP zurück
 **************************************************************/
function eventoni_get_visitor_location()
{
	$ip = eventoni_get_visitor_ip();

	$ch = curl_init();
	// set url
	curl_setopt($ch, CURLOPT_URL, "http://ipinfodb.com/ip_query.php?ip={$ip}&timezone=false");
	//return the transfer as a string
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	// $output contains the output string
	$output = curl_exec($ch);
	// close curl resource to free up system resources
	curl_close($ch);

	echo $output;

	die();
}
add_action('wp_ajax_eventoni_get_visitor_location', 'eventoni_get_visitor_location');
add_action('wp_ajax_nopriv_eventoni_get_visitor_location', 'eventoni_get_visitor_location');

/**************************************************************
 * liefert IP-Adresse des Benutzers zurueck
 **************************************************************/
function eventoni_get_visitor_ip()
{
	return $_SERVER['REMOTE_ADDR'];
}

/**************************************************************
 * Platzierung des Eventoni-Tickers je nach Einstellung:
 * - ueber Content
 * - unter Content
 * - Sidebar (nach Aufruf von Meta)
 **************************************************************/
function eventoni_add_searchform()
{
	$options = get_option('eventoni_options');

	$input_width = 30;
	if( $options['placement'] == 'sidebar' ){
		$input_width = 100;
	}
	$ip = eventoni_get_visitor_ip();
	?>

<div id="eventoni_container" class="eventoni_shadow">

<div id="eventoni_container_normale_suche">
<div style="width: 90%; margin: 0 auto; text-align: center;">
<form id="eventoni_form" action="#" method="GET">
<input type="hidden" name="eventoni_search" value="1" />
<div style="width: <?php echo $input_width; ?>%; float: left; padding: 0; text-align: center; color: #C8D7B9;">
	Was?<br />
	<input tabindex=11 style="width: 90%; margin: 0 auto;" type="text" id="eventoni_was" name="was" value="<?php echo get_query_var('was');?>" />
</div>
<div style="width: <?php echo $input_width; ?>%; float: right; padding: 0; text-align: center; color: #C8D7B9;">
	Wann?<br />
	<input tabindex=13 style="width: 90%; margin: 0 auto;" type="text" id="eventoni_wann" name="wann" value="<?php echo get_query_var('wann');?>" />
</div>
<div style="width: <?php echo $input_width; ?>%; margin-top: 10px; margin-left: auto; margin-right: auto; margin-top: 10px; padding: 0; text-align: center; color: #C8D7B9;">
	Wo?<br />
	<input tabindex=12 style="width: 90%; margin: 0 auto;" type="text" id="eventoni_wo" name="wo" value="<?php echo get_query_var('wo');?>" />
</div>
<input tabindex=14 type="image" style="margin-top: 10px;"
	src="<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/eventoni/img/search-button.png"
	name="submit" value="submit">
	<input type="hidden" name="eventoni_page" id="eventoni_page" value="1" autocomplete="off"/>
	<input type="hidden" name="eventoni_subpage" id="eventoni_subpage" value="0" autocomplete="off"/>
	</form>
</div>
</div>

	<?php

	if( isset($options['geolocation'])){
		echo '<div><a href="#" id="eventoni_umkreissuche" style="display:none">Im Umkreis suchen (<span id="eventoni_umkreis"></span>)</a></div>';
	}

	echo '<div class="tsr">';
	for($z=1;$z<=5;$z++)
	{
	?>
	<div class="text font_0">
		<div class="top5tsr big" id="top5_<?php echo $z; ?>">
			<div class="right">
				<div class="teaserText">
					<a href="#" class="link image">
						<img class="image" src="" align="left">
					</a>
					<div class="headline">
						<h3>
							<a href="#" class="link font_46"></a>
						</h3>
					</div>
					<br clear="left">
					<div class="texte font_43">
					</div>
					<div style="float:right">
						<a class="facebook_link" href="#" target="_blank">
							<img src="<?php echo get_bloginfo('wpurl').'/wp-content/plugins/eventoni/img/facebook.png'; ?>" />
						</a>
					</div>
					<div style="float:right">
						<a class="twitter_link" href="TODO" target="_blank">
							<img src="<?php echo get_bloginfo('wpurl').'/wp-content/plugins/eventoni/img/twitter.png'; ?>" />
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php

	}
	echo '</div>';
	echo '<div id="eventoni_pagination"><span id="eventoni_last" style="float:left;display:none;">zur&uuml;ck</span><span id="eventoni_next"  style="float:right">vor</span></div><br/>';
	echo '<div><a href="http://www.eventoni.de/event/add.html">Event melden</a></div>';
	if( isset($options['show_map'])){
		echo '<div id="eventoni_map" style="width:100%; height:200px;"></div>';
	}
	echo '<div style="float:right"><small style="color:#ffffff;font-size:10px;">powered by</small> <a href="http://www.eventoni.de/" title="Eventoni Startseite"><img alt="Eventoni Veranstaltungen und Termine in Deiner Region" src="'.get_bloginfo('wpurl').'/wp-content/plugins/eventoni/img/logo.png" /></a></div>';
	echo '</div>';
}
if( $options['placement'] == 'sidebar' ){
	add_action('wp_meta', 'eventoni_add_searchform');
} else if($options['placement'] == 'unten'){
	add_action('loop_end', 'eventoni_add_searchform');
} else{
	add_action('loop_start', 'eventoni_add_searchform');
}

/**************************************************************
 * Fuegt die Eventoni-Ergebnisse zur Blogsuche hinzu, falls
 * diese Funktion im Admin-Bereich aktiviert wurde
 **************************************************************/
function add_eventoni_to_search()
{
	if(is_search())
	{
		$results = eventoni_fetch('&wt='.urlencode(get_search_query()));
		if( $results['total'] <= 0 )
		{
			return;
		}
		echo '<div class="events-container">';
		echo '<img src="'.get_bloginfo('wpurl').'/wp-content/plugins/eventoni/img/logo.png" />';
		$counter = 0;
		foreach($results['xml'] as $event)
		{
			$counter++;
			$datetime = strtotime($event->start_date.' '.$event->start_time);
			$hours = getdate($datetime);
			$hour = $hours['hours'];
			$tageszeit = '';
			if( $hour < 6 ){
				$tageszeit = 'nachts';
			} else if( $hour < 12 ){
				$tageszeit = 'morgens';
			} else if( $hour < 14 ){
				$tageszeit = 'mittags';
			} else if( $hour < 18 ){
				$tageszeit = 'nachmittags';
			} else if( $hour < 22 ){
				$tageszeit = 'abends';
			} else {
				$tageszeit = 'nachts';
			}
			echo ' <div class="event-item">';
			echo '<a class="event-item-link" href="'.$event->permalink.'">';
			if(isset($event->media_list->media->thumbnail_url)) {
				echo '<img width="60px" height"60px" align="left" src="'.$event->media_list->media->thumbnail_url.'"/>';
			} else {
				echo '<img width="60px" height"60px" align="left" src="http://static.eventoni.com/images/image-blank.png"/>';
			}
			echo '</a>';
			echo '	 <div class="event-item-content">';
			echo '		<div class="event-item-content-date">'.date( "d.m.Y", $datetime ).', '.date( "H:i \U\h\\r", $datetime ).'</div>';
			echo '		<div class="event-item-content-city"><img src="'.get_bloginfo('wpurl').'/wp-content/plugins/eventoni/img/my_location.png"/> '.$event->location->city.'</div>';
			echo '	 </div>';
			echo '	 <div class="event-item-content-name"><b><a class="event-item-link" href="'.$event->permalink.'">'.$event->title.'</a></b></div>';
			echo '	 <div style="float:right;"><a class="facebook_link" href="http://www.facebook.com/sharer.php?u='.$event->permalink.'&t=Dieses Event musst Du gesehen haben: " target="_blank"><img src="'.get_bloginfo('wpurl').'/wp-content/plugins/eventoni/img/facebook.png" /></a></div>';
			echo '	 <div style="float:right;"><a class="twitter_link" href="http://twitter.com/home?status=Dieses Event musst Du gesehen haben: '.$event->permalink.'" target="_blank"><img src="'.get_bloginfo('wpurl').'/wp-content/plugins/eventoni/img/twitter.png" /></a></div>';
			echo ' </div>';
			if( $counter >= 3 ){
				break;
			}
		}
		echo '</div>';
	}
}
if( isset($options['event_search']) ){
	add_action('loop_end', 'add_eventoni_to_search');
}

/**************************************************************
 * führt den Request per Eventoni API durch und liefert die Ergebnisse zurück:
 * - als raw PHP-Daten
 * - als XML-Baum
 * je nachdem wie $raw angegeben wurde
 **************************************************************/
function eventoni_fetch($get_fields = '', $raw = false, $events_array = false )
{
	$options = get_option('eventoni_options');
	$ch = curl_init();

	// set url
	if( $events_array ){
		$xml_name = implode('-',$events_array);
		curl_setopt($ch, CURLOPT_URL, "http://api.eventoni.com/v1/events/$xml_name.xml?api_key=".$options['eventoni_api_key']);
	} else {
		curl_setopt($ch, CURLOPT_URL, "http://api.eventoni.com/v1/search.xml?api_key=".$options['eventoni_api_key'].$get_fields);
	}

	//return the transfer as a string
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// $output contains the output string
	$output = curl_exec($ch);

	// close curl resource to free up system resources
	curl_close($ch);

	// default Werte
	$overall_pages = 0;
	$current_page  = 0;
	$total_events  = 0;
	$xml = $output;

	// falls keine raw-Daten gewünscht sind, wird hier das XML geparst
	if(!$raw)
	{
		$xml = simplexml_load_string($output);
		$root_attributes = $xml->attributes();
		$overall_pages = $root_attributes->pages;
		$current_page = $root_attributes->page;
		$total_events = $root_attributes->total;
	}
	$data['xml']   = $xml;
	$data['pages'] = $overall_pages;
	$data['page']  = $current_page;
	$data['total'] = $total_events;

	return $data;
}

/**************************************************************
 * Einstellungsmenue fuer Plugin im Admin-Bereich
 **************************************************************/
function eventoni_create_menu() {

	//create new top-level menu
	add_menu_page('Eventoni', 'Eventoni', 'administrator', __FILE__, 'eventoni_settings_page');

	//call register settings function
	add_action( 'admin_init', 'register_mysettings' );
}
add_action('admin_menu', 'eventoni_create_menu');

function register_mysettings() {
	register_setting( 'eventoni_options', 'eventoni_options' );
}


/**************************************************************
 * Eventoni Options Seite erstellen.
 **************************************************************/
function eventoni_settings_page() {
	$options = get_option('eventoni_options');
	?>
<div class="wrap">
<h2>Eventoni Einstellungen</h2>

<form method="post" action="options.php"><?php //settings_fields( 'eventoni-settings-group' );
	settings_fields( 'eventoni_options' );
	?>
<table class="form-table">
	<tr valign="top">
		<th scope="row">Eventoni API key</th>
		<td><input type="text" name="eventoni_options[eventoni_api_key]"
			value="<?php echo $options['eventoni_api_key']; ?>" /></td>
	</tr>

	<tr valign="top">
		<th scope="row">Hintergrundfarbe (#xxxxxx)</th>
		<td><input type="text" name="eventoni_options[bg_color]"
			value="<?php echo $options['bg_color']; ?>" /></td>
	</tr>

	<tr valign="top">
		<th scope="row">Platzierung</th>
		<?php
		$checked = array('oben' => ' checked="checked"', 'unten' => '', 'sidebar' => '');
		if($options['placement'] == 'unten')
		{
			$checked = array('oben' => '', 'unten' => 'checked="checked"', 'sidebar' => '');
		} else if($options['placement'] == 'sidebar')
		{
			$checked = array('oben' => '', 'unten' => '', 'sidebar' => 'checked="checked"');
		}
		?>
		<td><input type="radio" name="eventoni_options[placement]"
			value="oben" <?php echo $checked['oben']; ?>>oben<br>
		<input type="radio" name="eventoni_options[placement]" value="unten"
		<?php echo $checked['unten']; ?>>unten<br>
		<input type="radio" name="eventoni_options[placement]" value="sidebar"
		<?php echo $checked['sidebar']; ?>>sidebar<br>
		</td>
	</tr>

	<?php
	$checked = '';
	if(isset($options['show_map']) && $options['show_map'] == 'true')
	{
		$checked = 'checked="checked"';
	}
	?>
	<tr valign="top">
		<th scope="row">Google Map anzeigen</th>
		<td><input type="checkbox" name="eventoni_options[show_map]"
			value="true" <?php echo $checked;?>></td>
	</tr>

	<tr valign="top">
		<th scope="row">Google Maps API key</th>
		<td><input type="text" name="eventoni_options[googlemaps_api_key]"
			value="<?php echo $options['googlemaps_api_key']; ?>" /></td>
	</tr>

	<?php
	$checked = '';
	if(isset($options['event_search']) && $options['event_search'] == 'true')
	{
		$checked = 'checked="checked"';
	}
	?>
	<tr valign="top">
		<th scope="row">Eventsuche in die Blogsuche integrieren</th>
		<td><input type="checkbox" name="eventoni_options[event_search]"
			value="true" <?php echo $checked;?>></td>
	</tr>

	<?php
	$checked = '';
	if(isset($options['event_suggest']) && $options['event_suggest'] == 'true')
	{
		$checked = 'checked="checked"';
	}
	?>
	<tr valign="top">
		<th scope="row">Eventvorschläge beim Schreiben von Posts anzeigen</th>
		<td><input type="checkbox" name="eventoni_options[event_suggest]"
			value="true" <?php echo $checked;?>></td>
	</tr>

	<?php
	$checked = '';
	if(isset($options['geolocation']) && $options['geolocation'] == 'true')
	{
		$checked = 'checked="checked"';
	}
	?>
	<tr valign="top">
		<th scope="row">Geolokalisierung des Benutzers aktivieren</th>
		<td><input type="checkbox" name="eventoni_options[geolocation]"
			value="true" <?php echo $checked;?>></td>
	</tr>
</table>

<p class="submit"><input type="submit" class="button-primary"
	value="<?php _e('Save Changes') ?>" /></p>

</form>
</div>
	<?php }

/**************************************************************
 * Methode zum hinzufügen von benötigeten javascripts und css.
 **************************************************************/
function add_scripts_to_admin()
{
	// JQuery
	wp_deregister_script('jquery');
	wp_register_script('jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js', false, '1.3.2', true);
	wp_enqueue_script('jquery');

	// Eigene Javascripts
	echo '<script src="'.get_bloginfo('wpurl').'/wp-content/plugins/eventoni/js/eventoni_admin.js"></script>';

	// CSS
	echo '<link type="text/css" rel="stylesheet" href="'.get_bloginfo('wpurl').'/wp-content/plugins/eventoni/css/eventoni_style.css.php"/>';

	// URLs für Wordpress Ajax und Plug-In Location als Javascript Variablen hinterlegen
	?> <script type="text/javascript">
//<![CDATA[
	eventoni_ajax_url   = '<?php bloginfo( 'wpurl' ); ?>/wp-admin/admin-ajax.php';
	eventoni_plugin_url = '<?php bloginfo( 'wpurl' );?>/wp-content/plugins/eventoni/';
//]]>
</script> <?php
}

// Hinzufügen der javascripts zum Header der Adminseiten.
add_action('admin_head','add_scripts_to_admin');

/**************************************************************
 * HTML Inhalt für die meta box für das Vorschlagen von Events.
 **************************************************************/
function eventoni_write_post_box()
{
	?> <a href="#" id="refresh_events">Vorschläge aktualisieren</a>
<div id="eventoni_content"></div>
	<?php
}

/**************************************************************
 * HTML Inhalt für die meta box für das Anzeigen von Events, die
 * dem Post bereits hinzugefügt wurden.
 **************************************************************/
function eventoni_write_post_box_added_events()
{
	?> <a href="#" id="refresh_added_events">Hinzugefügte Events aktualisieren</a>
<div id="eventoni_added_events_content"></div>
		<?php
	}

	/**************************************************************
	 * Fügt der Seite um Posts zu schreiben die Eventoni metaboxen
	 * zum Vorschlagen und ansehen von Events.
	 **************************************************************/
	function add_eventoni_to_write_post()
	{
		add_meta_box('eventoni_widget','Eventoni: Eventvorschläge','eventoni_write_post_box','post','side');
		add_meta_box('eventoni_widget_added_events','Eventoni: hinzugefügte Events','eventoni_write_post_box_added_events','post','advanced');
	}

	// Falls in den Eventoni Optionen das Vorschlagen von Events beim
	// Schreiben von Posts aktiviert ist, werden die nötigen meta boxen
	// der entsprechenden Seite hinzugefügt.
	if( isset($options['event_suggest']) ){
		add_action('admin_menu', 'add_eventoni_to_write_post');
	}

/**************************************************************
 * Entfernt html etc. aus dem übergebenen Text.
 * @param $html				Übergebene html text.
 * @return					Bearbeiteter Text ohne html etc.
 **************************************************************/
function clean_content($html)
{
	// Leerzeichen zwischen HTML-Tags einfügen, damit Worte aufeinanderfolgender Tags getrennt bleiben
	$html = str_replace('><', '> <', $html);

	# strip all html tags
	$wc = strip_tags($html);

	# remove 'words' that don't consist of alphanumerical characters or punctuation
	$pattern = "#[^(\w|\d|\'|\"|\.|\!|\?|;|,|\\|\/|\-|:|\&|@)]+#";
	$wc = trim(preg_replace($pattern, " ", $wc));

	# remove one-letter 'words' that consist only of punctuation
	$wc = trim(preg_replace("#\s*[(\'|\"|\.|\!|\?|;|,|\\|\/|\-|:|\&|@)]\s*#", " ", $wc));

	# remove superfluous whitespace
	$wc = preg_replace("/\s\s+/", " ", $wc);

	return $wc;
}

/**************************************************************
 * Berechnet die im Text enthaltenen Wörter (ohne Stopwörter)
 * in Reihenfolge der Vorkommen und baut daraus den
 * Query-String zum GET-Request
 * @param $content			Der Inhalt des Posts.
 * @return					Die häufigsten Wörter als String
 **************************************************************/
function analyse_content($content)
{
	// Sonderzeichen & Stopwörter entfernen
	$content = strtolower(clean_content($content));
	$stopwords = array('ab','bei','da','deshalb','ein','so','für','haben','hier','ich','ja','kann','machen','muesste','nach','oder','seid','sonst','und','vom','wann','wenn','wie','zu','bin','eines','hat','manche','solches','an','anderm','bis','das','deinem','demselben','dir','doch','einig','er','eurer','hatte','ihnen','ihre','ins','jenen','keinen','manchem','meinen','nichts','seine','soll','unserm','welche','werden','wollte','wührend','alle','allem','allen','aller','alles','als','also','am','ander','andere','anderem','anderen','anderer','anderes','andern','anderr','anders','auch','auf','aus','bist','bsp.','daher','damit','dann','dasselbe','dazu','daü','dein','deine','deinen','deiner','deines','dem','den','denn','denselben','der','derer','derselbe','derselben','des','desselben','dessen','dich','die','dies','diese','dieselbe','dieselben','diesem','diesen','dieser','dieses','dort','du','durch','eine','einem','einen','einer','einige','einigem','einigen','einiger','einiges','einmal','es','etwas','euch','euer','eure','eurem','euren','eures','ganz','ganze','ganzen','ganzer','ganzes','gegen','gemacht','gesagt','gesehen','gewesen','gewollt','hab','habe','hatten','hin','hinter','ihm','ihn','ihr','ihrem','ihren','ihrer','ihres','im','in','indem','ist','jede','jedem','jeden','jeder','jedes','jene','jenem','jener','jenes','jetzt','kein','keine','keinem','keiner','keines','konnte','künnen','künnte','mache','machst','macht','machte','machten','man','manchen','mancher','manches','mein','meine','meinem','meiner','meines','mich','mir','mit','muss','musste','müüt','nicht','noch','nun','nur','ob','ohne','sage','sagen','sagt','sagte','sagten','sagtest','sehe','sehen','sehr','seht','sein','seinem','seinen','seiner','seines','selbst','sich','sicher','sie','sind','so','solche','solchem','solchen','solcher','sollte','sondern','um','uns','unse','unsen','unser','unses','unter','viel','von','vor','war','waren','warst','was','weg','weil','weiter','welchem','welchen','welcher','welches','werde','wieder','will','wir','wird','wirst','wo','wolle','wollen','wollt','wollten','wolltest','wolltet','würde','würden','z.B.','zum','zur','zwar','zwischen','über','aber','abgerufen','abgerufene','abgerufener','abgerufenes','acht','acute','allein','allerdings','allerlei','allg','allgemein','allmühlich','allzu','alsbald','amp','and','andererseits','andernfalls','anerkannt','anerkannte','anerkannter','anerkanntes','anfangen','anfing','angefangen','angesetze','angesetzt','angesetzten','angesetzter','ansetzen','anstatt','arbeiten','aufgehürt','aufgrund','aufhüren','aufhürte','aufzusuchen','ausdrücken','ausdrückt','ausdrückte','ausgenommen','ausser','ausserdem','author','autor','auüen','auüer','auüerdem','auüerhalb','background','bald','bearbeite','bearbeiten','bearbeitete','bearbeiteten','bedarf','bedurfte','bedürfen','been','befragen','befragte','befragten','befragter','begann','beginnen','begonnen','behalten','behielt','beide','beiden','beiderlei','beides','beim','beinahe','beitragen','beitrugen','bekannt','bekannte','bekannter','bekennen','benutzt','bereits','berichten','berichtet','berichtete','berichteten','besonders','besser','bestehen','besteht','betrüchtlich','bevor','bezüglich','bietet','bisher','bislang','biz','bleiben','blieb','bloss','bloü','border','brachte','brachten','brauchen','braucht','bringen','brüuchte','bzw','büden','ca','ca.','collapsed','com','comment','content','da?','dabei','dadurch','dafür','dagegen','dahin','damals','danach','daneben','dank','danke','danken','dannen','daran','darauf','daraus','darf','darfst','darin','darum','darunter','darüber','darüberhinaus','dass','davon','davor','demnach','denen','dennoch','derart','derartig','derem','deren','derjenige','derjenigen','derzeit','desto','deswegen','diejenige','diesseits','dinge','direkt','direkte','direkten','direkter','doc','doppelt','dorther','dorthin','drauf','drei','dreiüig','drin','dritte','drunter','drüber','dunklen','durchaus','durfte','durften','dürfen','dürfte','eben','ebenfalls','ebenso','ehe','eher','eigenen','eigenes','eigentlich','einbaün','einerseits','einfach','einführen','einführte','einführten','eingesetzt','einigermaüen','eins','einseitig','einseitige','einseitigen','einseitiger','einst','einstmals','einzig','elf','ende','entsprechend','entweder','ergünze','ergünzen','ergünzte','ergünzten','erhalten','erhielt','erhielten','erhült','erneut','erst','erste','ersten','erster','erüffne','erüffnen','erüffnet','erüffnete','erüffnetes','etc','etliche','etwa','fall','falls','fand','fast','ferner','finden','findest','findet','folgende','folgenden','folgender','folgendes','folglich','for','fordern','fordert','forderte','forderten','fortsetzen','fortsetzt','fortsetzte','fortsetzten','fragte','frau','frei','freie','freier','freies','fuer','fünf','gab','ganzem','gar','gbr','geb','geben','geblieben','gebracht','gedurft','geehrt','geehrte','geehrten','geehrter','gefallen','gefiel','gefülligst','gefüllt','gegeben','gehabt','gehen','geht','gekommen','gekonnt','gemocht','gemüss','genommen','genug','gern','gestern','gestrige','getan','geteilt','geteilte','getragen','gewissermaüen','geworden','ggf','gib','gibt','gleich','gleichwohl','gleichzeitig','glücklicherweise','gmbh','gratulieren','gratuliert','gratulierte','gute','guten','güngig','güngige','güngigen','güngiger','güngiges','günzlich','haette','halb','hallo','hast','hattest','hattet','heraus','herein','heute','heutige','hiermit','hiesige','hinein','hinten','hinterher','hoch','html','http','hundert','hütt','hütte','hütten','hüchstens','igitt','image','immer','immerhin','important','indessen','info','infolge','innen','innerhalb','insofern','inzwischen','irgend','irgendeine','irgendwas','irgendwen','irgendwer','irgendwie','irgendwo','je','jed','jedenfalls','jederlei','jedoch','jemand','jenseits','jührig','jührige','jührigen','jühriges','kam','kannst','kaum','kei nes','keinerlei','keineswegs','klar','klare','klaren','klares','klein','kleinen','kleiner','kleines','koennen','koennt','koennte','koennten','komme','kommen','kommt','konkret','konkrete','konkreten','konkreter','konkretes','konnten','künn','künnt','künnten','künftig','lag','lagen','langsam','lassen','laut','lediglich','leer','legen','legte','legten','leicht','leider','lesen','letze','letzten','letztendlich','letztens','letztes','letztlich','lichten','liegt','liest','links','lüngst','lüngstens','mag','magst','mal','mancherorts','manchmal','mann','margin','med','mehr','mehrere','meist','meiste','meisten','meta','mindestens','mithin','mochte','morgen','morgige','muessen','muesst','musst','mussten','muü','muüt','müchte','müchten','müchtest','mügen','müglich','mügliche','müglichen','müglicher','müglicherweise','müssen','müsste','müssten','müüte','nachdem','nacher','nachhinein','nahm','natürlich','ncht','neben','nebenan','nehmen','nein','neu','neue','neuem','neuen','neuer','neues','neun','nie','niemals','niemand','nimm','nimmer','nimmt','nirgends','nirgendwo','nter','nutzen','nutzt','nutzung','nüchste','nümlich','nütigenfalls','nützt','oben','oberhalb','obgleich','obschon','obwohl','oft','online','org','padding','per','pfui','plützlich','pro','reagiere','reagieren','reagiert','reagierte','rechts','regelmüüig','rief','rund','sang','sangen','schlechter','schlieülich','schnell','schon','schreibe','schreiben','schreibens','schreiber','schwierig','schützen','schützt','schützte','schützten','sechs','sect','sehrwohl','sei','seit','seitdem','seite','seiten','seither','selber','senke','senken','senkt','senkte','senkten','setzen','setzt','setzte','setzten','sicherlich','sieben','siebte','siehe','sieht','singen','singt','sobald','sodaü','soeben','sofern','sofort','sog','sogar','solange','solc hen','solch','sollen','sollst','sollt','sollten','solltest','somit','sonstwo','sooft','soviel','soweit','sowie','sowohl','spielen','spüter','startet','startete','starteten','statt','stattdessen','steht','steige','steigen','steigt','stets','stieg','stiegen','such','suchen','sümtliche','tages','tat','tatsüchlich','tatsüchlichen','tatsüchlicher','tatsüchliches','tausend','teile','teilen','teilte','teilten','titel','total','trage','tragen','trotzdem','trug','trügt','tun','tust','tut','txt','tüt','ueber','umso','unbedingt','ungeführ','unmüglich','unmügliche','unmüglichen','unmüglicher','unnütig','unsem','unser','unsere','unserem','unseren','unserer','unseres','unten','unterbrach','unterbrechen','unterhalb','unwichtig','usw','var','vergangen','vergangene','vergangener','vergangenes','vermag','vermutlich','vermügen','verrate','verraten','verriet','verrieten','version','versorge','versorgen','versorgt','versorgte','versorgten','versorgtes','verüffentlichen','verüffentlicher','verüffentlicht','verüffentlichte','verüffentlichten','verüffentlichtes','viele','vielen','vieler','vieles','vielleicht','vielmals','vier','vollstündig','voran','vorbei','vorgestern','vorher','vorne','vorüber','vüllig','wührend','wachen','waere','warum','weder','wegen','weitere','weiterem','weiteren','weiterer','weiteres','weiterhin','weiü','wem','wen','wenig','wenige','weniger','wenigstens','wenngleich','wer','werdet','weshalb','wessen','wichtig','wieso','wieviel','wiewohl','willst','wirklich','wodurch','wogegen','woher','wohin','wohingegen','wohl','wohlweislich','womit','woraufhin','woraus','worin','wurde','wurden','wührenddessen','wür','würe','würen','zahlreich','zehn','zeitweise','ziehen','zieht','zog','zogen','zudem','zuerst','zufolge','zugleich','zuletzt','zumal','zurück','zusammen','zuviel','zwanzig','zwei','zwülf','ühnlich','übel','überall','überallhin','überdies','übermorgen','übrig','übrigens');
	$content = preg_replace('/\b('.implode('|',$stopwords).')\b/','',$content);

	// Text in einzelne Wörter aufsplitten
	$all_words = preg_split('%[\s,]+%', $content);

	// Häufigkeitstabelle der Wörter erstellen
	$words = array();
	foreach($all_words as $word)
	{
		if(array_key_exists ($word, $words))
		{
			// Wort existiert bereits -> count wird erhöht
			$words[$word] = $words[$word] + 1;
		}
		else
		{
			// Wort existierte noch nicht -> wird hinzugefügt
			$words[$word] = 1;
		}
	}
	// Wörter nach Ihrer Häufigkeit absteigend sortieren
	arsort($words);

	// 5 häufigste wörter für query benutzen
	$query = array_slice($words,0,5);

	// einzelne wörter per OR verknüpfen
	return urlencode(implode(' OR ', array_keys($query)));
}

// Die Methode "eventoni_suggest_events" als Wordpress Ajax call
// unter dem namen "suggest_events" ermöglichen.
add_action('wp_ajax_suggest_events', 'eventoni_suggest_events');

/**************************************************************
 * Vorschlagen von Events, die zum Inhalt eines Post passen.
 * @param $content			Der Inhalt des Posts.
 * @return					Informationen zu den Events als XML
 **************************************************************/
function eventoni_suggest_events()
{
	// POST-Daten (Inhalt + Titel) auslesen und analysieren
	$content = $_POST['content'];
	$what = analyse_content($content);

	// hole Rohdaten zu den Events anhand des analysierten Text-Inhalts
	$data = eventoni_fetch('&wt='.$what, true);
	echo $data['xml'];
	die();
}

// Die Methode "eventoni_get_events_by_id" als Wordpress Ajax call
// unter dem namen "get_events_by_id" ermöglichen.
add_action('wp_ajax_get_events_by_id', 'eventoni_get_events_by_id');

/**************************************************************
 * Holt die Informationen mehrere Events anhand ihrer Id.
 * @param $event_ids		Die Event-Ids in Form eines Strings,
 * 							wobei diese mittels bindestriche
 * 							voneinander getrennt sind.
 * @return					Informationen zu den Events als XML
 **************************************************************/
function eventoni_get_events_by_id()
{
	$event_ids = $_POST['event_ids'];

	// Aufsplitten des Strings in ein Array mit den Events
	$event_ids = explode('-',$event_ids);

	// Holen der Informationen zu den Events
	$data = eventoni_fetch('',true,$event_ids);

	// Rückgabe der Informationen zu den Events in XML
	echo $data['xml'];
	die();
}

// Die Methode "add_event_as_custom_field" als Wordpress Ajax call ermöglichen
add_action('wp_ajax_add_event_as_custom_field', 'add_event_as_custom_field');

/**************************************************************
 * Fügt einem Post eine Event-Id als custom field hinzu
 * @param $post_id		Die Id des zu bearbeitenden Posts.
 * @param $event_id		Die Id des zu löschenden Events.
 **************************************************************/
function add_event_as_custom_field(){
	$post_id = $_POST['post_id'];
	$event_id = $_POST['event_id'];

	if( $post_id >= 0 ){
		// Holen der bereits dem Post angehängten Events
		$custom_fields = get_post_custom($post_id);



		// Check ob bereits Events an den Post angehängt wurden
		if( isset( $custom_fields['added_events'] )){

		// Falls das hinzuzufügende Event bereits enthalten ist
		// aus der Methode rausspringen
			$my_custom_field = $custom_fields['added_events'];
			foreach ( $my_custom_field as $key => $value ){
				if( $value == $event_id ){
					return;
				}
			}
		}

		// Hinzufügen des Events als custom field an den Post
		add_post_meta($post_id, 'added_events', $event_id);
	}
	die();
}

// Die Methode "get_added_events" als Wordpress Ajax call ermöglichen
add_action('wp_ajax_get_added_events', 'get_added_events');

/**************************************************************
 * Holen der Events, die einem Post als custom fields hinzu-
 * gefügt wurden. Die Rückgabe der Events wird in json voll-
 * zogen.
 * @param $post_id		Die Id des zu bearbeitenden Posts.
 * @return				Die dem Post angehängten Events als json
 **************************************************************/
function get_added_events(){
	$post_id = $_POST['post_id'];
	// Holen der Events eines Posts
	$custom_fields = get_post_custom_values('added_events', $post_id);
	// Events als json codieren und zurückgeben
	$custom_fields = json_encode($custom_fields);
	echo $custom_fields;
	die();
}

// Die Methode "delete_event_custom_field" als Wordpress Ajax call ermöglichen
add_action('wp_ajax_delete_event_custom_field', 'delete_event_custom_field');

/**************************************************************
 * Löschen eines Events aus den custom fields eines Posts.
 * Event-Id und Post-Id werden als Post übermittelt.
 * @param $post_id		Die Id des zu bearbeitenden Posts.
 * @param $event_id		Die Id des zu löschenden Events.
 **************************************************************/
function delete_event_custom_field(){
	$post_id = $_POST['post_id'];
	$event_id = $_POST['event_id'];
	delete_post_meta($post_id,'added_events',$event_id);
	die();
}

/**************************************************************
 * Baut zu einem array aus Events den HTML Code zur Dartstellung
 * zusammen.
 * @param $events		Array mit Event-Ids
 * @return				HTML Code zur Darstellung der Events
 **************************************************************/
function insert_events( $events ){
	// Informationen zu den Events holen
	$results = eventoni_fetch('',false,$events);

	// Falls keine Informationen zu Events gefunden, aus Methode rausspringen
	if( $results['total'] <= 0 )
	{
		return;
	}

	// HTML Code erstellen
	$result = '';
	$result.= '<div class="events-container">';
	$result.= '<img src="'.get_bloginfo('wpurl').'/wp-content/plugins/eventoni/img/logo.png" />';

	// Jedes Event durchlaufen
	foreach($results['xml'] as $event)
	{
		// Berechnung der Zeitangabe und Tageszeit als Wort
		$datetime = strtotime($event->start_date.' '.$event->start_time);
		$hours = getdate($datetime);
		$hour = $hours['hours'];
		$tageszeit = '';
		if( $hour < 6 ){
			$tageszeit = 'nachts';
		} else if( $hour < 12 ){
			$tageszeit = 'morgens';
		} else if( $hour < 14 ){
			$tageszeit = 'mittags';
		} else if( $hour < 18 ){
			$tageszeit = 'nachmittags';
		} else if( $hour < 22 ){
			$tageszeit = 'abends';
		} else {
			$tageszeit = 'nachts';
		}
		$result.= ' <div class="event-item">';
		$result.= '<a class="event-item-link" href="'.$event->permalink.'">';

		// Falls kein Vorschaubild vorhanden, nehme Standardbild
		if(isset($event->media_list->media->thumbnail_url)) {
			$result.= '<img width="60px" height"60px" align="left" src="'.$event->media_list->media->thumbnail_url.'"/>';
		} else {
			$result.= '<img width="60px" height"60px" align="left" src="http://static.eventoni.com/images/image-blank.png"/>';
		}
		$result.= '</a>';
		$result.= '	 <div class="event-item-content">';
		$result.= '		<div class="event-item-content-date">'.date( "d.m.Y", $datetime ).', '.date( "H:i \U\h\\r", $datetime ).'</div>';
		$result.= '		<div class="event-item-content-city"><img src="'.get_bloginfo('wpurl').'/wp-content/plugins/eventoni/img/my_location.png"/> '.$event->location->city.'</div>';
		$result.= '	 </div>';
		$result.= '	 <div class="event-item-content-name"><b><a class="event-item-link" href="'.$event->permalink.'">'.$event->title.'</a></b></div>';
		$result.= '<div style="float:right"><a class="facebook_link" href="http://www.facebook.com/sharer.php?u='.$event->permalink.'&t=Dieses Event musst Du gesehen haben: " target="_blank"><img src="'.get_bloginfo('wpurl').'/wp-content/plugins/eventoni/img/facebook.png" /></a></div>';
		$result.= '<div style="float:right"><a class="twitter_link" href="http://twitter.com/home?status=Dieses Event musst Du gesehen haben: '.$event->permalink.'" target="_blank"><img src="'.get_bloginfo('wpurl').'/wp-content/plugins/eventoni/img/twitter.png" /></a></div>';
		$result.= ' </div>';

	}
	$result.= '</div>';

	// HTML code zurückgeben
	return $result;
}

/**************************************************************
 * Fügt einem post die events hinzu die als custom fields im
 * post abgespeichert sind.
 * @param $content		Der Inhalt des Posts
 * @return				Der modifizierte Inhalt
 **************************************************************/
function add_event_custom_fields_to_post($content){
	global $post;
	$post_id = $post->ID;

	// Holen der Event-Ids aus den custom fields des posts.
	$event_array = get_post_custom_values('added_events', $post_id);

	// Falls Events vorhanden sind, diese an den content anhängen
	if( isset($event_array) ){
		$event_string = insert_events( $event_array );

		return $content.$event_string;
	}
	return $content;
}
// Anhängen der Methode "add_event_cusomt_fields_to_post" als Filter
add_filter('the_content','add_event_custom_fields_to_post');
?>