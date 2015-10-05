<?php
/**
 * Plugin Name: Woo Product Add Tab
 * Plugin URI: https://github.com/shvlv/woocommerce-add-tab
 * Description: Plugin allows you to add additional tabs on the product page in WooCommerce
 * Version: 0.8
 * Author: shvv
 * Author URI: http://shvlv.github.io/
 * License: GPLv2 or later
 * Tested up to: 4.3
 * Text Domain: woocommerce-add-tab
 * Domain Path: /languages/
 *
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'Woocommerce_Add_Tab' ) ) {

	class Woocommerce_Add_Tab {

		/**
		 * @var Woocommerce_Add_Tab Use for singleton
		 */

		private static $instance;

		/**
		 * @var array of post with post_type='produst_tab'
		 * @see set_tab
		 *
		 */

		private $tabs;


		/**
		 * Singleton
		 *
		 * @return Woocommerce_Add_Tab
		 */
		public static final function get_instance() {
			if (!self::$instance) {
				self::$instance = new self;
			}
			return self::$instance;
		}


		public function __construct()
		{
			/**
			 * Backend WooCommerce hook
			 */

			add_action('woocommerce_product_write_panel_tabs', array($this,'panel_tabs'));
			add_action('woocommerce_product_write_panels', array($this,'options_tabs'));
			add_action('woocommerce_process_product_meta', array($this,'save_options'), 10, 2);

			/**
			 * Frontend WooCommerce hook
			 */
			add_filter( 'woocommerce_product_tabs', array($this,'display_tabs') );

			/**
			 * Admin Wordpress hook
			 */

			add_action('admin_enqueue_scripts', array($this,'add_script'));
			add_action( 'init', array($this, 'create_post_type') , 100);
			add_action( 'save_post_product_tab', array($this, 'save_tab') );
			add_filter('manage_product_tab_posts_columns' , array($this, 'add_priority_columns'));
			add_action( "manage_posts_custom_column", array($this, 'set_priority_columns'), 10, 2 );
			add_filter( "manage_edit-product_tab_sortable_columns", array($this, 'sortable_columns') );
			add_action('quick_edit_custom_box', array($this, 'priority_quick_edit'), 10, 2);
			add_action( 'plugins_loaded', array($this, 'load_textdomain') );

			// Set list user tab
			$this->set_tab();

		}

		/**
		 * Load translation file
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'woocommerce-add-tab', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Add style and script at Tabs edit page
		 * @param $hook
		 */
		public function add_script($hook)
		{
			if ( ('edit.php' == $hook) && ($_GET['post_type'] == 'product_tab') ) {
				wp_enqueue_style( 'add', plugins_url( '/assets/', __FILE__ ) . "add.css" );
				wp_enqueue_script( 'add-js', plugins_url( '/assets/', __FILE__ ) . "add.js", array( 'jquery' ), '0.1', true );
			}
		}

		/**
		 * Register product_tab post type
		 */
		public function create_post_type() {
			register_post_type( 'product_tab',
					array(
							'labels' => array(
									'name' =>  __('Tabs', 'woocommerce-add-tab'),
									'singular_name' => __( 'Tab', 'woocommerce-add-tab') ,
									'add_new' =>  __('Add tab', 'woocommerce-add-tab'),
									'add_new_item' =>  __('Add tab', 'woocommerce-add-tab'),
									'edit_item' =>  __('Edit tab', 'woocommerce-add-tab'),
							),
							'show_ui' => true,
							'show_in_menu' => 'edit.php?post_type=product',
							'show_in_nav_menus' => true,
							'supports' => 'title',
							'capability_type'     => 'product',
							'register_meta_box_cb' => array($this, 'add_field')
					)
			);
		}

		/**
		 * Add priority metabox
		 * @uses create_post_type As callback
		 */
		public function add_field() {
			add_meta_box(
					'tab_priority',
					__('Set tab priority', 'woocommerce-add-tab'),
					array($this, 'display_field'),
					'product_tab'   ,
					'normal',
					'high'

			);
		}

		/**
		 * @uses add_field As callback
		 * @param $post  - Post Object
		 */
		function display_field($post) {
			$value = get_post_meta( $post->ID, '_priority_tab', true);
			?>

			<p><?php _e( 'Tabs <i>priority</i> determine its position in relation to other tabs. Standard tabs on the product page have the following priority: <strong>Description</strong> - 10, <strong>Additional Information</strong> (shown only when you specify the weight or size) - 20, <strong>Reviews</strong> - 30. For example, for place tab on second place set priority - 15.', 'woocommerce-add-tab' ) ?>
			</p>

			<input type="text" class="short"  name="priority_tab" id="priority_tab" value="<?php echo $value ?>" placeholder="<?php _e('Input number', 'woocommerce-add-tab')?> ">

		<?php }

		/**
		 * Add priority metadata at saving post
		 * @param $post_id
		 *
		 */
		public function save_tab( $post_id ) {
			if ( array_key_exists('priority_tab', $_POST ) ) {
				$safe_priority = intval($_POST['priority_tab']);
				if ( ! $safe_priority ) {
					$safe_priority = '';
				}
				update_post_meta( $post_id,
						'_priority_tab',
						$safe_priority
				);
			}
		}

		/**
		 * @return array Tabs edit page columns
		 */
		public function add_priority_columns() {
			return array(
					'cb'       => '<input type="checkbox" />',
					'title' => __('Tab title', 'woocommerce-add-tab'),
					'priority' => __('Priority', 'woocommerce-add-tab')
			);
		}

		/**
		 * Set value when rendering Tabs edit page
		 * @param string $column column id
		 * @param $post_id
		 */
		public function set_priority_columns( $column, $post_id ) {
			switch ( $column ) {
				case "priority":
					$value = get_post_meta( $post_id, '_priority_tab', true);
					echo  $value;
					break;

			}
		}


		public function sortable_columns() {
			return array(
					'priority' => __('Priority', 'woocommerce-add-tab')
			);
		}

		/**
		 * Display quick edit box
		 *
		 * @param $column_name
		 * @param $post_type
		 */
		public function priority_quick_edit($column_name, $post_type) {

			if (($column_name != 'priority') || ($post_type != 'product_tab')) return;
			?>
			<fieldset class="priority-field">
				<div class="priority-input ">
					<span class="title"><?php _e('Priority', 'woocommerce-add-tab') ?></span>
					<input id="priority_tab" class="short" type="text" name="priority_tab" value=""/>
					<p><?php _e( 'Tabs <i>priority</i> determine its position in relation to other tabs. Standard tabs on the product page have the following priority: <strong>Description</strong> - 10, <strong>Additional Information</strong> (shown only when you specify the weight or size) - 20, <strong>Reviews</strong> - 30. For example, for place tab on second place set priority - 15.', 'woocommerce-add-tab' ) ?>
					</p>
				</div>
			</fieldset>
			<?php
		}

		public function get_tabs()
		{
			return $this->tabs;
		}


		protected function set_tab()
		{
			$this->tabs = get_posts(array(
				'post_type'        => 'product_tab',
			));
		}

		/**
		 * Display product admin page tabs
		 */
		public function panel_tabs()
		{
			foreach ($this->tabs as $tab) {
				?>
				<li class="custom_tab"><a href="<?php echo '#custom_tab_' . $tab->ID ?>"><?php echo $tab->post_title; ?></a></li>
				<?php
			}
		}

		/**
		 *
		 * Display product admin page tabs options
		 */
		public function options_tabs()
		{
			global $post;
			$post_id = $post->ID;

			foreach ($this->tabs as $tab) {
				?>
				<div id="<?php echo 'custom_tab_' . $tab->ID ?>" class="panel woocommerce_options_panel">
					<h3> <?php echo $tab->post_title;  ?> </h3>

					<div class="options_group custom_tab_options">
						<table class="form-table">
							<tr>
								<td>
									<?php
									$settings = array(
										'text_area_name' => 'custom_tab_content_' . $tab->ID,
										'quicktags' => true,
										'tinymce' => true,
										'media_butons' => false,
										'textarea_rows' => 98,
										'editor_class' => 'contra',
										'editor_css' => '<style>#wp-custom_tab_content_spec-editor-container .wp-editor-area{height:250px; width:100%;} #custom_tab_data3 .quicktags-toolbar input {width:auto;}</style>'
									);

									$id = 'custom_tab_content_' . $tab->ID;
									$content = get_post_meta($post_id, 'custom_tab_content_' . $tab->ID, true);

									wp_editor($content, $id, $settings);

									?>
								</td>
							</tr>
						</table>
					</div>
				</div>
				<?php
			}
		}

		/**
		 * Save tabs content while saving product
		 *
		 * @param $post_id
		 */
		public function save_options($post_id) {
			foreach ($this->tabs as $tab) {
				update_post_meta( $post_id, 'custom_tab_content_' . $tab->ID, $_POST['custom_tab_content_' . $tab->ID]);
			}
		}

		/**
		 * Set tabs to render at frontend product page
		 *
		 * @param array $list_tabs Default WooCommerce tab
		 *
		 * @return array Array tab to render at frontend product page
		 */
		public function display_tabs($list_tabs){
			global $post;

			foreach ($this->tabs as $tab) {
				$content = get_post_meta($post->ID, 'custom_tab_content_' . $tab->ID, true);
				$priority = get_post_meta( $tab->ID, '_priority_tab', true);


				if ( $content ){
					$list_tabs[ $tab->ID ] = array(
						'title'    => $tab->post_title,
						'priority' => $priority,
						'callback' => array($this, 'render_tabs'),
						'content'  => $content
					);
				}
			}
			return $list_tabs;
		}

		/**
		 * @uses  display_tabs As callback
		 *
		 * @param string $key
		 * @param array $tab
		 */
		public function render_tabs($key, $tab) {
			echo '<p>'. $tab['content'] . '</p>';

		}
	}
}

Woocommerce_Add_Tab::get_instance();