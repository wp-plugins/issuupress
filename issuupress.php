<?php
/*
Plugin Name: issuuPress
Plugin URI: http://www.pixeline.be
Description: Displays your Issuu catalog of PDF files in your wordpress posts/pages using a shortcode.
Version: 1.1.0
Author: Alexandre Plennevaux
Author URI: http://www.pixeline.be
*/
/*

Changelog:
v1.0: Initial release

*/
/*
Plugin template by Piers http://soderlind.no/archives/2010/03/04/wordpress-plugin-template/
*/

if (!class_exists('ap_issuupress')) {
	class ap_issuupress {
		/**
		 * @var string The options string name for this plugin
		 */
		protected $pluginVersion;
		protected $pluginId;
		protected $pluginPath;
		protected $pluginUrl;

		var $optionsName = 'ap_issuupress_options';
		var $apiKey;
		var $apiSecret;
		var $filterByTag;
		var $cacheDuration;
		var $options = array();
		var $localizationDomain = "ap_issuupress";

		var $url = '';

		var $urlpath = '';

		//Class Functions
		/**
		 * PHP 4 Compatible Constructor
		 */
		function ap_issuupress(){$this->__construct();}

		/**
		 * PHP 5 Constructor
		 */
		function __construct(){
			//Language Setup
			$locale = get_locale();
			$mo = plugin_dir_path(__FILE__) . 'languages/' . $this->localizationDomain . '-' . $locale . '.mo';
			load_textdomain($this->localizationDomain, $mo);

			//"Constants" setup

			$this->url = plugins_url(basename(__FILE__), __FILE__);
			$this->urlpath = plugins_url('', __FILE__);
			//*/
			$this->pluginPath   =  dirname(__FILE__);
			$this->pluginUrl   =  WP_PLUGIN_URL . '/'.basename($this->pluginPath);
			$this->pluginVersion= '1.1.0';
			$this->pluginId = 'issuupress';


			//Initialize the options
			$this->getOptions();
			$this->apiKey = $this->options['ap_issuupress_apikey'];
			$this->apiSecret = $this->options['ap_issuupress_apisecret'];
			$this->cacheDuration = $this->options['ap_issuupress_cacheDuration'];

			//Admin menu
			add_action("admin_menu", array(&$this,"admin_menu_link"));

			add_shortcode('issuupress', array($this, 'shortcode'));

			add_filter('the_posts', array(&$this,'scripts_and_styles'));

			//Actions
			add_action("init", array(&$this,"ap_issuupress_init"));
		}

		function ap_issuupress_init(){
		}

		function listDocs(){

			require_once('issuuAPI.php');

			$issuuAPI = new issuuAPI(array('apiKey'=>$this->apiKey,'apiSecret'=>$this->apiSecret, 'cacheDuration'=>$this->cacheDuration));
			$docs = $issuuAPI->getListing();

			return $docs;
		}


		/**
		 * @desc Retrieves the plugin options from the database.
		 * @return array
		 */
		function getOptions() {
			if (!$theOptions = get_option($this->optionsName)) {
				$theOptions = array('ap_issuupress_apikey'=> '', 'ap_issuupress_apisecret' => '', 'ap_issuupress_cacheDuration'=>86400);
				update_option($this->optionsName, $theOptions);

			}
			$this->options = $theOptions;
			$this->cacheDuration = $this->options['ap_issuupress_cacheDuration'];
		}


		function shortcode($atts){
			ob_start();
			if(!is_admin()){
				extract(shortcode_atts(array('tag'=>'', 'viewer'=>'mini','vmode'=>'','titlebar'=>'false','height'=>'480', 'bgcolor'=>'FFFFFF','ctitle'=>'Pick a PDF file to read'), $atts));

				$this->filterByTag = $tag;

				$docs = $this->listDocs();

				if($_GET['documentId'] != '') {
					$docId = $_GET['documentId'];
					$docTitle= $_GET['title'];
				}else{
					if($this->filterByTag !=''){
						foreach($docs->_content as $d){
							if(in_array($this->filterByTag, $d->document->tags)){
								$docId =  $d->document->documentId;
								$docTitle =  $d->document->title;
								break;
							}
						}
					}else{
						$docId = $docs->_content[0]->document->documentId;
						$docTitle = $docs->_content[0]->document->title;
					}

				}
				$output = '<div id="issuupress">';


				// display viewer, send it options in array

				if($viewer!=='no'){
					$output .= $this->issuuViewer(array('documentId'=> $docId, 'viewer'=>$viewer, 'title'=>$docTitle, 'height'=>$height, 'bgcolor'=>$bgcolor, 'titlebar'=>$titlebar, 'vmode'=>$vmode ));
				}


				// loop through the issuus files and display them.
				$output .= '<h3>'.$ctitle.'</h3>';
				$output .='<ol class="issuu-list">';
				foreach($docs->_content as $d){

					if((is_array($d->document->tags) && in_array($this->filterByTag, $d->document->tags)) || is_string($this->filterByTag)){
						$issuu_link = 'http://issuu.com/'.$d->document->username.'/docs/'.$d->document->name.'#download';
						$dId = $d->document->documentId;
						$doc_link = add_query_arg( 'documentId', $dId, get_permalink() );
						$doc_link = add_query_arg( 'title', urlencode($d->document->title), $doc_link);
						$doc_link.='#issuupress';
						$selected = ($dId == $docId) ? 'class="issuu-selected"':'';
						if($viewer==='no'){
							$doc_link = $issuu_link;
							$link_target= 'target="_blank"';
						}
						$output.= '<li '.$selected.'><a class="issuu-view" href="'.$doc_link.'" '.$link_target.'>'.$d->document->title.'</a> <small>'.$this->formatIssuuDate($d->document->publishDate).'</small></li>';


					}
				}

				$output.='</ol>
			</div>';
				$output .= '<img src="http://pixeline.be/pixeline-downloads-tracker.php?fn='.$this->pluginId.'&v='.$this->pluginVersion.'&uu='.$_SERVER['HTTP_HOST']  . $_SERVER['REQUEST_URI'].'" width="1" height="1"/>';
				echo $output;

			}
			$output_string = ob_get_contents();

			ob_end_clean();

			return $output_string;

		}
		private function formatIssuuDate($date){
			return date('d M Y',strtotime($date));
		}

		private function issuuViewer($args){
			$options['documentId']= $args['documentId'];
			$options['bgcolor']=$args['bgcolor'];
			$options['mode']= $args['viewer']; // 'mini', 'Presentation' or 'window'
			$options['height']=$args['height'];
			$options['title']= $args['title'];
			$options['titlebar']= $args['titlebar'];
			$options['vmode']= ($args['vmode']=='single') ? 'singlePage':'';
			$output= '<h3>'.$options['title'].'</h3>
			<div id="issuuViewer">
				<object style="width:100%;height:'.$options['height'].'px" >
				<param name="movie" value="http://static.issuu.com/webembed/viewers/style1/v2/IssuuReader.swf?mode='.$options['mode'].'&amp;backgroundColor=%23'.$options['bgcolor'].'&amp;viewMode='.$options['vmode'].'&amp;embedBackground=%23'.$options['bgcolor'].'&amp;titleBarEnabled='.$options['titlebar'].'&amp;documentId='.$options['documentId'].'" />
				<param name="allowfullscreen" value="true"/>
				<param name="menu" value="false"/>
				<param name="wmode" value="transparent"/>
				<embed src="http://static.issuu.com/webembed/viewers/style1/v2/IssuuReader.swf" type="application/x-shockwave-flash" allowfullscreen="true" menu="false" wmode="transparent" style="width:100%;height:'.$options['height'].'px" flashvars="mode='.$options['mode'].'&amp;backgroundColor=%23'.$options['bgcolor'].'&amp;viewMode='.$options['vmode'].'&amp;embedBackground=%23'.$options['bgcolor'].'&amp;documentId='.$options['documentId'].'&amp;titleBarEnabled='.$options['titlebar'].'" />
				</object>
				</div>';


			return $output;

		}


		// ADD JS and CSS IN FRONTEND WHEN RELEVANT

		function scripts_and_styles($posts){
			if (empty($posts)) return $posts;
			$shortcode_found = false;

			foreach ($posts as $post) {
				if (stripos($post->post_content, '[issuupress') !== false) {
					$shortcode_found = true; // bingo!
					break;
				}
			}

			if ($shortcode_found) {
				// enqueue here
				if(!is_admin()){
					$pth_plugin_url = plugin_dir_url(__FILE__);
					wp_enqueue_script('jquery');
					wp_enqueue_script('pixeline_issuupress', $this->pluginUrl.'/'.$this->pluginId.'.js', array('jquery'));
					wp_enqueue_style('pixeline_issuupress', $this->pluginUrl.'/'.$this->pluginId.'-frontend.css');

				}
			}

			return $posts;
		}


		/*

		ADMIN STUFF HEREBELOW

		*/



		/**
		 * Saves the admin options to the database.
		 */
		function saveAdminOptions(){
			return update_option($this->optionsName, $this->options);
		}

		/**
		 * @desc Adds the options subpanel
		 */
		function admin_menu_link() {
			add_options_page('issuuPress', 'issuuPress', 10, basename(__FILE__), array(&$this,'admin_options_page'));
			add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array(&$this, 'filter_plugin_actions'), 10, 2 );
		}

		/**
		 * @desc Adds the Settings link to the plugin activate/deactivate page
		 */
		function filter_plugin_actions($links, $file) {
			$settings_link = '<a href="options-general.php?page=' . basename(__FILE__) . '">' . __('Settings') . '</a>';
			array_unshift( $links, $settings_link ); // before other links

			return $links;
		}

		/**
		 * Adds settings/options page
		 */
		function admin_options_page() {
			if($_POST['ap_issuupress_save']){
				if (! wp_verify_nonce($_POST['_wpnonce'], 'ap_issuupress-update-options') ) die('Whoops! There was a problem with the data you posted. Please go back and try again.');
				$this->options['ap_issuupress_apikey'] = $_POST['ap_issuupress_apikey'];
				$this->options['ap_issuupress_apisecret'] = $_POST['ap_issuupress_apisecret'];
				$this->options['ap_issuupress_cacheDuration'] = (int)$_POST['ap_issuupress_cacheDuration'];

				if($_POST['ap_issuupress_refresh_now']==='1'){
					require_once('issuuAPI.php');
					$issuuAPI = new issuuAPI(array('apiKey'=>$this->apiKey,'apiSecret'=>$this->apiSecret, 'cacheDuration'=>$this->cacheDuration));
					if (is_file($issuuAPI->issuuCacheFile)){
						$deleteCache = @unlink($issuuAPI->issuuCacheFile);
						echo ($deleteCache) ? '<div class="updated"><p>'._('Success! Cache file deleted.').'</p></div>': '<div class="updated"><p>'._('Error! Could not delete the cache file!'). '('.$issuuAPI->issuuCacheFile.')</p></div>';
					}else{
						echo '<div class="updated"><p>'._('No cache file found.').'</p></div>';
					}

				}


				$this->saveAdminOptions();

				echo '<div class="updated"><p>'._('Success! Your changes were sucessfully saved.').'</p></div>';
			}
?>
			<div class="wrap">
			<h1><?php _e('IssuuPress Settings', $this->localizationDomain);?></h1>
			<p><?php _e('by <a href="http://www.pixeline.be" target="_blank" class="external">pixeline</a>', $this->localizationDomain); ?></p>
			<p style="font-weight:bold;"><?php _e('If you like this plugin, please <a href="http://wordpress.org/extend/plugins/issuupress/" target="_blank">give it a good rating</a> on the Wordpress Plugins repository, and if you make any money out of it, <a title="Paypal donation page" target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=J9X5B6JUVPBHN&lc=US&item_name=pixeline%20%2d%20Wordpress%20plugin&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHostedGuest">send a few coins over to me</a>!', $this->localizationDomain); ?></p>

			<h2 style="border-top:1px solid #999;padding-top:1em;"><?php _e('Settings', $this->localizationDomain);?></h2>
			<p><?php _e('In order to fetch the list of your documents from your Issuu account, you need to provide your API credentials. Get them <a href="http://issuu.com/services/api/" target="_blank">here</a>.', $this->localizationDomain); ?>
			</p>
			<form method="post" id="ap_issuupress_options">
			<?php wp_nonce_field('ap_issuupress-update-options'); ?>
				<table width="100%" cellspacing="2" cellpadding="5" class="form-table">
					<tr valign="top">
						<th width="33%" scope="row"><?php _e('Your Issuu Api key:', $this->localizationDomain); ?></th>
						<td>
							<input name="ap_issuupress_apikey" type="text" id="ap_issuupress_apikey" size="45" value="<?php echo $this->options['ap_issuupress_apikey'] ;?>"/>

						</td>
					</tr>
					<tr valign="top">
						<th width="33%" scope="row"><?php _e('Your Issuu Api secret:', $this->localizationDomain); ?></th>
						<td>
							<input name="ap_issuupress_apisecret" type="text" id="ap_issuupress_apisecret" size="45" value="<?php echo $this->options['ap_issuupress_apisecret'] ;?>"/>

						</td>
					</tr>

					<tr valign="top">
						<th width="33%" scope="row"><?php _e('Refresh cache every (in seconds):', $this->localizationDomain); ?></th>
						<td>
							<input name="ap_issuupress_cacheDuration" type="text" id="ap_issuupress_cacheDuration" size="12" value="<?php echo $this->options['ap_issuupress_cacheDuration'] ;?>"/>
							<br><small><?php _e('Tip: 1 day = 86400 sec. , 1 hour = 3600 sec.', $this->localizationDomain); ?></small>
						</td>
					</tr>
<tr valign="top">
						<th width="33%" scope="row"><?php _e('Refresh the cache now? ', $this->localizationDomain); ?></th>
						<td>
							<label>
							<input name="ap_issuupress_refresh_now" type="checkbox" id="ap_issuupress_refresh_now" value="1"/>
							<br><small><?php _e('Check this option to download a fresh copy of your Issuu catalog.', $this->localizationDomain); ?></small></label>
						</td>
					</tr>

				</table>
				<p class="submit">
					<input type="submit" name="ap_issuupress_save" class="button-primary" value="<?php _e('Save Changes', $this->localizationDomain); ?>" />
				</p>
			</form>
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_donations" />
<input type="hidden" name="business" value="J9X5B6JUVPBHN" />
<input type="hidden" name="lc" value="US" />
<input type="hidden" name="item_name" value="pixeline - Wordpress plugin: Issuupress" />
<input type="hidden" name="currency_code" value="EUR" />
<input type="hidden" name="bn" value="PP-DonationsBF:btn_donate_SM.gif:NonHostedGuest" />
<input type="image" name="submit" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" alt="PayPal - The safer, easier way to pay online!" />
<img src="https://www.paypalobjects.com/fr_FR/i/scr/pixel.gif" alt="" width="1" height="1" border="0" /></form>
			<?php
		}
	} //End Class
} //End if class exists statement



if (isset($_GET['ap_issuupress_javascript'])) {
	//embed javascript
	header("content-type: application/x-javascript");
	echo<<<ENDJS
/**
* @desc issuuPress
* @author Alexandre Plennevaux - http://www.pixeline.be
*/

jQuery(document).ready(function(){
	// add your jquery code here


	//validate plugin option form
  	jQuery("#ap_issuupress_options").validate({
		rules: {
			ap_issuupress_apikey: {
				required: true
			},
			ap_issuupress_cacheDuration:{
			required: true,
			min: 60,
			number: true
			}
		},
		messages: {
			ap_issuupress_apikey: {
				// the ap_issuupress_lang object is define using wp_localize_script() in function ap_issuupress_script()
				required: ap_issuupress_lang.required,
				number: ap_issuupress_lang.number,
				min: ap_issuupress_lang.min
			}
		}
	});
});

ENDJS;

} else {
	if (class_exists('ap_issuupress')) {
		$ap_issuupress_var = new ap_issuupress();
	}
}
?>