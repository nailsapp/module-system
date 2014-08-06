<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * OVERLOADING NAILS' MODELS
 *
 * Note the name of this class; done like this to allow apps to extend this class.
 * Read full explanation at the bottom of this file.
 *
 **/

class NAILS_App_notification_model extends NAILS_Model
{
	protected $_notifications;
	protected $_emails;


	// --------------------------------------------------------------------------


	/**
	 * Construct the notification model, set defaults
	 */
	public function __construct()
	{
		parent::__construct();

		// --------------------------------------------------------------------------

		$this->_table			= NAILS_DB_PREFIX . 'app_notification';
		$this->_table_prefix	= 'n';
		$this->_notifications	= array();
		$this->_emails			= array();

		// --------------------------------------------------------------------------

		$this->_set_definitions();
	}


	// --------------------------------------------------------------------------


	/**
	 * Defines the notifications
	 */
	protected function _set_definitions()
	{
		//	Generic Site notifications
		$this->_notifications['app']													= $this->_get_blank_grouping();
		$this->_notifications['app']->label												= 'Site';
		$this->_notifications['app']->description										= 'General site notifications.';

		$this->_notifications['app']->options['default']								= $this->_get_blank_option();
		$this->_notifications['app']->options['default']->label							= 'Generic';

		// --------------------------------------------------------------------------

		if ( module_is_enabled( 'shop' ) ) :

			$this->_notifications['shop']												= $this->_get_blank_grouping();
			$this->_notifications['shop']->label										= 'Shop';
			$this->_notifications['shop']->description									= 'Shop related notifications.';

			$this->_notifications['shop']->options['new_order']							= $this->_get_blank_option();
			$this->_notifications['shop']->options['new_order']->label					= 'Order Notifications';
			$this->_notifications['shop']->options['new_order']->email_subject			= 'An order has been placed';

			$this->_notifications['shop']->options['product_enquiry']					= $this->_get_blank_option();
			$this->_notifications['shop']->options['product_enquiry']->label			= 'Product Enquiries';
			$this->_notifications['shop']->options['product_enquiry']->email_subject	= 'New Product Enquiry';

			$this->_notifications['shop']->options['delivery_enquiry']					= $this->_get_blank_option();
			$this->_notifications['shop']->options['delivery_enquiry']->label			= 'Delivery Enquiries';
			$this->_notifications['shop']->options['delivery_enquiry']->email_subject	= 'New Delivery Enquiry';

		endif;
	}


	// --------------------------------------------------------------------------


	protected function _get_blank_grouping()
	{
		$_definition = new stdClass();

		//	Human readable label for this type of grouping
		$_definition->label = '';

		//	Text for the fieldset, rendered in admin
		$_definition->description = '';

		//	An empty object array
		$_definition->options = array();

		return $_definition;
	}


	// --------------------------------------------------------------------------


	protected function _get_blank_option()
	{
		$_option = new stdClass();

		//	Human readable label for this type of notification
		$_option->label = '';

		//	A sub label which will be rendered within admin
		$_option->sub_label = '';

		//	A helpful tip, also rendered in admin
		$_option->tip = '';

		//	The subject to give the outgoing email
		$_option->email_subject = '';

		//	The template to use
		$_option->email_tpl = '';

		//	Alternatively, send a message by populating this field
		$_option->email_message = '';

		// --------------------------------------------------------------------------

		return $_option;
	}


	// --------------------------------------------------------------------------


	/**
	 * Returns the notification defnintions, optionally limited per group
	 * @param  string $grouping The group to limit to
	 * @return array
	 */
	public function get_definitions( $grouping = NULL )
	{
		if ( is_null( $grouping ) ) :

			return $this->_notifications;

		elseif ( isset( $this->_notifications[$grouping] ) ) :

			return $this->_notifications[$grouping];

		else :

			return array();

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Get's emails associated with a particular group/key
	 * @param  string  $key           The key to retrieve
	 * @param  string  $grouping      The group the key belongs to
	 * @param  boolean $force_refresh Whether to force a group refresh
	 * @return array
	 */
	public function get( $key = NULL, $grouping = 'app', $force_refresh = FALSE )
	{
		//	Check that it's a valid key/grouping pair
		if ( ! isset( $this->_notifications[$grouping]->options[$key] ) ) :

			$this->_set_error( $grouping . '/' . $key . ' is not a valid group/key pair.' );
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		if ( empty( $this->_emails[$grouping] ) || $force_refresh ) :

			$this->db->where( 'grouping', $grouping );
			$_notifications = $this->db->get( $this->_table )->result();
			$this->_emails[$grouping] = array();

			foreach ( $_notifications AS $setting ) :

				$this->_emails[$grouping][ $setting->key ] = unserialize( $setting->value );

			endforeach;

		endif;

		// --------------------------------------------------------------------------

		if ( empty( $key ) ) :

			return $this->_emails[$grouping];

		else :

			return isset( $this->_emails[$grouping][$key] ) ? $this->_emails[$grouping][$key] : array();

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Set a group/key either by passing an array of key=>value pairs as the $key
	 * or by passing a string to $key and setting $value
	 * @param mixed  $key      The key to set, or an array of key => value pairs
	 * @param string $grouping The grouping to store the keys under
	 * @param mixed  $value    The data to store, only used if $key is a string
	 * @return boolean
	 */
	public function set( $key, $grouping = 'app', $value = NULL )
	{
		$this->db->trans_begin();

		if ( is_array( $key ) ) :

			foreach ( $key AS $key => $value ) :

				$this->_set( $key, $grouping, $value );

			endforeach;

		else :

			$this->_set( $key, $grouping, $value );

		endif;

		if ( $this->db->trans_status() === FALSE ) :

			$this->db->trans_rollback();
			return FALSE;

		else :

			$this->db->trans_commit();
			return TRUE;

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Inserts/Updates a group/key value
	 * @param string $key      The key to set
	 * @param string $grouping The key's grouping
	 * @param mixed  $value    The value of the group/key
	 * @return void
	 */
	protected function _set( $key, $grouping, $value )
	{
		//	Check that it's a valid key/grouping pair
		if ( ! isset( $this->_notifications[$grouping]->options[$key] ) ) :

			$this->_set_error( $grouping . '/' . $key . ' is not a valid group/key pair.' );
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		$this->db->where( 'key', $key );
		$this->db->where( 'grouping', $grouping );
		if ( $this->db->count_all_results( $this->_table ) ) :

			$this->db->where( 'grouping', $grouping );
			$this->db->where( 'key', $key );
			$this->db->set( 'value', serialize( $value ) );
			$this->db->update( $this->_table);

		else :

			$this->db->set( 'grouping', $grouping );
			$this->db->set( 'key', $key );
			$this->db->set( 'value', serialize( $value ) );
			$this->db->insert( $this->_table );

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Sends a notification to the email addresses associated with a particular key/grouping
	 * @param  string $key      The key to send to
	 * @param  string $grouping The key's grouping
	 * @param  array  $data     An array of values to pass to the email template
	 * @param  array  $override Override any of the definition values (this time only). Useful for defining custom email templates etc.
	 * @return boolean
	 */
	public function notify( $key, $grouping = 'app', $data = array(), $override = array() )
	{
		//	Check that it's a valid key/grouping pair
		if ( ! isset( $this->_notifications[$grouping]->options[$key] ) ) :

			$this->_set_error( $grouping . '/' . $key . ' is not a valid group/key pair.' );
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		//	Fetch emails
		$_emails = $this->get( $key, $grouping );

		if ( empty( $_emails ) ) :

			//	Notification disabled, silently fail
			return TRUE;

		endif;

		//	Definition to use; clone so overrides aren't permenant
		$_definition = clone $this->_notifications[$grouping]->options[$key];

		//	Overriding the definition?
		if ( ! empty( $override ) && is_array( $override ) ) :

			foreach ( $override AS $or_key => $or_value ) :

				if ( isset( $_definition->{$or_key} ) ) :

					$_definition->{$or_key} = $or_value;

				endif;

			endforeach;

		endif;

		if ( empty( $_definition->email_tpl ) ) :

			$this->_set_error( 'No email template defined for ' . $grouping . '/' . $key );
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		//	Send the email
		$this->load->library( 'emailer' );

		//	Build the email
		$_email			= new stdClass();
		$_email->type	= 'app_notification';
		$_email->data	= $data;

		if ( ! empty( $_definition->email_subject ) ) :

			$_email->data['email_subject'] = $_definition->email_subject;

		endif;

		if ( ! empty( $_definition->email_tpl ) ) :

			$_email->data['email_template'] = $_definition->email_tpl;

		endif;

		foreach( $_emails AS $e ) :

			log_message( 'debug', 'Sending notification (' . $grouping . '/' . $key . ') to ' . $e );

			$_email->to_email = $e;

			$this->emailer->send( $_email );

		endforeach;

		return TRUE;
	}
}


// --------------------------------------------------------------------------


/**
 * OVERLOADING NAILS' MODELS
 *
 * The following block of code makes it simple to extend one of the core
 * models. Some might argue it's a little hacky but it's a simple 'fix'
 * which negates the need to massively extend the CodeIgniter Loader class
 * even further (in all honesty I just can't face understanding the whole
 * Loader class well enough to change it 'properly').
 *
 * Here's how it works:
 *
 * CodeIgniter instantiate a class with the same name as the file, therefore
 * when we try to extend the parent class we get 'cannot redeclare class X' errors
 * and if we call our overloading class something else it will never get instantiated.
 *
 * We solve this by prefixing the main class with NAILS_ and then conditionally
 * declaring this helper class below; the helper gets instantiated et voila.
 *
 * If/when we want to extend the main class we simply define NAILS_ALLOW_EXTENSION
 * before including this PHP file and extend as normal (i.e in the same way as below);
 * the helper won't be declared so we can declare our own one, app specific.
 *
 **/

if ( ! defined( 'NAILS_ALLOW_EXTENSION_APP_NOTIFICATION_MODEL' ) ) :

	class App_notification_model extends NAILS_App_notification_model
	{
	}

endif;

/* End of file app_notification_model.php */
/* Location: ./modules/system/models/app_notification_model.php */