<?php
class App_Shortcode_WorkerLocationsShortcode extends App_Shortcode {

	protected $_requested_location_id;

	public static function serve () {
		$me = new self;
		$me->register('app_provider_locations');
	}

	protected function __construct () {
		$this->addColumn();
		$this->_defaults = array(
			'select' => array(
				'value' => __('Please select a location:', 'appointments'),
				'help' => __('Text above the select menu. Default: "Please select a provider location"', 'appointments'),
				'example' => __('Please select a location:', 'appointments'),
			),
			'show' => array(
				'value' => __('Show available providers', 'appointments'),
				'help' => __('Button text to show the results for the selected. Default: "Show available providers"', 'appointments'),
				'example' => __('Show available providers', 'appointments'),
			),
			'autorefresh' => array(
				'value' => 0,
				'help' => __('If set as 1, Show button will not be displayed and page will be automatically refreshed as client changes selection. Note: Client cannot browse through the selections and thus check descriptions on the fly (without the page is refreshed). Default: "0" (disabled)', 'appointments'),
				'example' => '1',
			),
			'order_by' => array(
				'value' => 'ID',
				'help' => __('Sort order, by service providers. Possible values: ID, name. Optionally DESC (descending) can be used, e.g. "name DESC" will reverse the order. Default: "ID"', 'appointments'),
				'example' => 'ID',
			),
		);

		if (!empty($_REQUEST['app_provider_location']) && is_numeric($_REQUEST['app_provider_location'])) {
			$this->_requested_location_id = (float)$_REQUEST['app_provider_location'];
		}

		if (!is_admin() && !empty($this->_requested_location_id)) {
			add_filter('app_workers', array($this, 'filter_workers'));
		}
	}

	public function addColumn()
	{
		global $wpdb;
		$table_name=$wpdb->prefix."app_appointments";
		$myCustomer = $wpdb->get_row("SELECT * FROM $table_name");
		//print_r($myCustomer);die();
		//Add column if not present.
		if(!isset($myCustomer->area)){
		    $wpdb->query("ALTER TABLE $table_name ADD area VARCHAR(100) AFTER city");
		}
	}

	public function filter_workers ($workers) {
		$result = array();
		foreach ($workers as $wrk) {
			if (empty($wrk->ID)) continue;
			$location_id = App_Locations_WorkerLocations::worker_to_location_id($wrk->ID);
			if (!empty($location_id) && $this->_requested_location_id == $location_id) {
				$result[] = $wrk;
			}
		}

		return $result;
	}

	public function process_shortcode ($args=array(), $content='') {
		global $appointments;
		$args = wp_parse_args($args, $this->_defaults_to_args());

		$workers = $appointments->get_workers($args['order_by']);
		$model = App_Locations_Model::get_instance();
		$locations = array();

		foreach ($workers as $wrk) {
			if (empty($wrk->ID)) continue;
			$location_id = App_Locations_WorkerLocations::worker_to_location_id($wrk->ID);
			if (!empty($location_id)) $locations[$location_id] = $model->find_by('id', $location_id);
		}
		$locations = array_values(array_filter($locations));

		if (empty($locations)) return $content;
		$ret = '';

		$ret .= '<div class="app_provider_locations">';
		$ret .= '<div class="app_provider_locations_dropdown">';
		$ret .= '<div class="app_provider_locations_dropdown_title">';
		$ret .= $args['select'];
		$ret .= '</div>';
		$ret .= '<div class="app_provider_locations_dropdown_select_city">';
		if(isset($_REQUEST['app_service_city']) && $_REQUEST['app_service_city']!="")
		{
			$sel_city=$_REQUEST['app_service_city'];
		}
		else
		{
			$sel_city="";
		}
		$ret .= '<input type="text" name="app_provider_location10" class="app_service_location_10" id="app_service_location_10" value="'.$sel_city.'" list="location-datalist-id" placeholder="Select City"/>';
		$ret .= '<datalist  name="location-datalist-id" id="location-datalist-id">';
		$ret .= '<option value="">Select City</option>';
		$a=array();
		foreach ($locations as $location) {
			$x=$location->to_storage()['address'];
			$a[]=$x;
			/*$ret .= '<option value="' . esc_attr($location->get_id()) . '" ' . selected($this->_requested_location_id, $location->get_id(), false) . '>' . esc_html($location->get_display_markup(false).'|'.$location->to_storage()['area']) . '</option>';
			/*$ret .= '<option value="' . esc_attr($location->get_id()) . '" ' . selected($this->_requested_location_id, $location->get_id(), false) . '>' . esc_html($location->to_storage()['area']) . '</option>';*/
		}
		$b1=array_unique($a);
		//print_r($b1);
		//$count=max($b);
		$b=array();
		foreach($b1 as $b2):
		$b[]=$b2;
		endforeach;
		//print_r($b);
		$count=count($b);
		//echo $count;
		
		for($i=0;$i<=$count;$i++){
		if($sel_city!="" && $b[$i]!=""):
		if($sel_city==$b[$i]):	
		$ret .= '<option selected="selected" value = '.$b[$i].'>'.$b[$i].'</option>';
		else:
		$ret .= '<option value = '.$b[$i].'>'.$b[$i].'</option>';
		endif;
		else:
		if($b[$i]!=""):
		$ret .= '<option value = '.$b[$i].'>'.$b[$i].'</option>';
		endif;
		endif;
		}
		$ret .= '</datalist>';
		/* New Code */
		$ret .= '</div>';
		if(isset($_REQUEST['app_provider_location']) && $_REQUEST['app_provider_location']!=""):
		foreach ($locations as $location) {
			if(isset($sel_city) && $sel_city!=""):
			if($sel_city==$location->to_storage()['address']):
			$area = $location->to_storage()['area'];
			endif;
			endif;
		}	
		endif;
		$ret .= '<div class="app_provider_locations_dropdown_select">';
		if(isset($area)):
		$ret .= '<input type="text" name="app_provider_location_2" class="app_service_location_2" id="app_service_location_2" value="'.$area.'" list="area-datalist-id" placeholder="Select Area"/>';
		else:
		$ret .= '<input type="text" name="app_provider_location_2" class="app_service_location_2" id="app_service_location_2" value="" list="area-datalist-id" placeholder="Select Area"/>';
		endif;
		$ret .= '<datalist  name="area-datalist-id" id="area-datalist-id">';
		//$ret .= '<select name="app_provider_location_2" class="app_service_location_2" id="app_service_location_2">';
		//$ret .= '<option value="">Select Area</option>';
		
		foreach ($locations as $location) {
			if(isset($sel_city) && $sel_city!=""):
			if($sel_city==$location->to_storage()['address']):
			$ret .= '<option data-value="' .$location->get_id(). '" value="'. $location->to_storage()['area'] . '"></option>';
			endif;
			else:
			$ret .= '<option data-value="' .$location->get_id(). '" value="'. $location->to_storage()['area'] . '"></option>';
			endif;
		}
		$ret .= '</datalist>';
				
		/*Code Ends*/
		
		if (empty($args['autorefresh'])) $ret .= '<input type="button" class="app_provider_locations_button" value="'.esc_attr($args['show']).'">';
		$ret .= '</div>';
		

		$href = add_query_arg(
			'app_provider_location', '::apl::',
			remove_query_arg(array(
				//'app_service_location',
				'app_provider_location',
				//'app_provider_id',
				'app_service_id'
			))
		);
		
		$script =<<<EO_SELECTION_JAVASCRIPT
	
function app_provider_locations_redirect () {
	var selected = $(".app_provider_locations_dropdown_select select").first().val();
	window.location = '{$href}'.replace(/::apl::/, selected);
}
$(".app_provider_locations_button").click(app_provider_locations_redirect);

EO_SELECTION_JAVASCRIPT;

		if (!empty($args['autorefresh'])) {
			$script .= '$(".app_provider_locations_dropdown_select select").change(app_provider_locations_redirect);';
		}
		$appointments->add2footer($script);
		
		return $ret;
	}

	public function get_usage_info () {
		return __('Creates a dropdown menu of available provider locations.', 'appointments');
	}
}



class App_Shortcode_RequiredWorkerLocationsShortcode extends App_Shortcode_WorkerLocationsShortcode {

	public static function serve () {
		$me = new self;
		$me->register('app_required_provider_locations');
	}

	public function get_usage_info () {
		return __('Creates a dropdown menu of available provider locations which will be converted to a provider list once a location has been chosen.', 'appointments');
	}

	public function process_shortcode ($args=array(), $content='') {
		$instance = App_Shortcodes::get_shortcode_instance('app_service_providers');
		if (!empty($this->_requested_location_id) && $instance && method_exists($instance, 'process_shortcode')) return $instance->process_shortcode($args, $content);
		else return parent::process_shortcode($args, $content);
	}
}