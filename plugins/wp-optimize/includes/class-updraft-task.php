<?php

if (class_exists('Updraft_Task_1_0')) return;

abstract class Updraft_Task_1_0 {

	/**
	 * A unique ID for the specific task
	 *
	 * @var int
	 */
	private $id;

	/**
	 * The user id of the creator of this task
	 *
	 * @var string
	 */
	private $user_id;

	/**
	 * A text description for the task
	 *
	 * @var string
	 */
	private $description;

	/**
	 * A type for the task
	 *
	 * @var string
	 */
	private $type;

	/**
	 * A timestamp indicating the time the task was created
	 *
	 * @var string
	 */
	private $time_created;

	/**
	 * A text description describing the status of the task
	 *
	 * @var string
	 */
	private $status;

	/**
	 * A logger object that can be used to capture interesting events / messages
	 *
	 * @var Object
	 */
	protected $logger;


	/**
	 * The Task constructor
	 *
	 * @param UpdraftPlus_Task|object $task UpdraftPlus_Task object.
	 */
	public function __construct($task) {
		foreach (get_object_vars($task) as $key => $value)
			$this->$key = $value;
	}


	/**
	 * Sets the instance ID.
	 *
	 * @param String $instance_id - the instance ID
	 */
	public function set_id($instance_id) {
		$this->id = $instance_id;
	}

	/**
	 * Gets the instance ID.
	 *
	 * @return String the instance ID
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Sets the description.
	 *
	 * @param String $description - the description of the task
	 */
	public function set_description($description) {
		$this->description = $description;
	}

	/**
	 * Gets the task description
	 *
	 * @return String $description - the description of the task
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Sets the type.
	 *
	 * @param String $type - the type of the task
	 */
	public function set_type($type) {
		$this->type = $type;
	}

	/**
	 * Gets the task type
	 *
	 * @return String $type - the type of the task
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Sets the task status.
	 *
	 * @param String $status - the status of the task
	 *
	 * @return Boolean - the result of the status update
	 */
	public function set_status($status) {
		
		if (array_key_exists($status, self::get_allowed_statuses()))
			$this->status = $status;
		else return false;

		return $this->update_task_status($this->task_id, $this->status);
	}

	/**
	 * Gets the task status
	 *
	 * @return String $status - the status of the task
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * Sets the logger for this task.
	 *
	 * @param Object $logger - the logger for the task
	 */
	public function set_logger($logger) {
		$this->logger = $logger;
	}

	/**
	 * Returns the logger used by this task.
	 *
	 * @return Object $logger - the logger for the task
	 */
	public function get_logger() {
		return $this->logger;
	}


	/**
	 * The initialisation function that accepts and processes any parameters needed before the task starts
	 *
	 * @param Array $options - array of options
	 *
	 * @uses update_option
	 */
	public function initialise($options = array()) {

		do_action('task_before_initialise', $this, $options);

		/**
		 * Parse incoming $options into an array and merge it with defaults
		 */
		$defaults = $this->get_default_options();
		$options = wp_parse_args($options, $defaults);

		foreach ($options as $option => $value) {
			$this->update_option($option, $value);
		}

		do_action('task_initialise_complete', $this, $options);

	}

	/**
	 * This function is called to allow for the task to perfrom a small chunk of work.
	 * It should be written in a way that anticipates it being killed  off at any time.
	 */
	public function run() {
	}

	/**
	 * Any clean up code goes here.
	 */
	public function complete() {

		do_action('task_before_complete', $this);

		$this->delete_meta($this->task_id);
		$this->set_status('complete');

		do_action('task_completed', $this);
	}

	/**
	 * Prints any information about the task that the UI can use on the front end.
	 */
	public function print_task_report_widget() {

		$ret = '';

		$status = $this->get_task_status();
		$stage = $this->get_task_status('stage') ? $this->get_task_status('stage') : 'Unknown';
		$description = $this->get_task_status_description($status);


		$ret .= '<div class="task task-'.$this->description.'" id="task-id-'.$this->task_id.'">';

		$ret .= apply_filters('print_task_report_before_warnings', '', $this->task_id, $this);

		$warnings = $this->get_task_data('warnings');
		if (!empty($warnings) && is_array($warnings)) {
			$ret .= '<ul class="disc">';
			foreach ($warnings as $warning) {
				$ret .= '<li>'.sprintf(__('Warning: %s'), make_clickable(htmlspecialchars($warning))).'</li>';
			}
			$ret .= '</ul>';
		}

		$ret .= '<div class="stage">';
		$ret .= htmlspecialchars($stage);

		$ret .= '<div class="task_percentage" data-info="'.esc_attr($stage).'" data-progress="'.(($stage>0) ? (ceil((100/6)*$stage)) : '0').'" style="height: 100%; width:'.(($stage>0) ? (ceil((100/6)*$stage)) : '0').'%"></div>';
		$ret .= '</div></div>';

		$ret .= '</div>';

		return apply_filters('print_task_report_widget', $ret, $this->task_id, $this);

	}

	/**
	 * This method gets an option from the task options in the WordPress database if available,
	 * otherwise returns the default for this task type
	 *
	 * @param  String $option  the name of the option to get
	 * @param  Mixed  $default a value to return if the option is not currently set
	 *
	 * @return Mixed  The option from the database
	 */
	public function get_option($option, $default = null) {
		return Updraft_Task_Options::get_task_option($this->task_id, $option, $default);
	}

	/**
	 * This method is used to add a task option stored in the WordPress database
	 *
	 * @param  String $option the name of the option to update
	 * @param  Mixed  $value  the value to save to the option
	 *
	 * @return Mixed          the status of the add operation
	 */
	public function add_option($option, $value) {
		return Updraft_Task_Options::update_task_option($this->task_id, $option, $value);
	}

	/**
	 * This method is used to update a task option stored in the WordPress database
	 *
	 * @param  String $option the name of the option to update
	 * @param  Mixed  $value  the value to save to the option
	 *
	 * @return Mixed          the status of the update operation
	 */
	public function update_option($option, $value) {
		return Updraft_Task_Options::update_task_option($this->task_id, $option, $value);
	}

	/**
	 * This method is used to delete a task option stored in the WordPress database
	 *
	 * @param  String $option the option to delete
	 *
	 * @return Boolean        the result of the delete operation
	 */
	public function delete_option($option) {
		return Updraft_Task_Options::delete_task_option($this->task_id, $option);
	}

	/**
	 * Retrieve default options for this task.
	 * This method should normally be over-ridden by the child.
	 *
	 * @return Array - an array of options
	 */
	public function get_default_options() {

		$this->logger->error('The get_default_options() method was not over-ridden for the class '.$this->get_task_description());

		return array();
	}

	/**
	 * Updates the status of the given task in the DB
	 *
	 * @param String $id     - the id of the task
	 * @param String $status - the status of the task
	 *
	 * @return Boolean - the stauts of the update operation
	 */
	public function update_status($id, $status) {

		if (!array_key_exists($status, self::get_allowed_statuses()))
			return false;

		global $wpdb;
		$sql = $wpdb->prepare("UPDATE {$wpdb->base_prefix}tm_tasks SET status = %s WHERE task_id = %d", $status, $id);

		return $wpdb->query($sql);
	}

	/**
	 * Cleans out the given task from the DB
	 *
	 * @return Boolean - the status of the delete operation
	 */
	public function delete() {
		global $wpdb;

		if ($this->task_id > 0) {
			$sql = $wpdb->prepare("DELETE t, tm FROM {$wpdb->base_prefix}tm_tasks t JOIN {$wpdb->base_prefix}tm_taskmeta tm ON t.task_id = tm.task_id WHERE t.task_id = %d", $this->task_id);
			return $wpdb->query($sql);
		}

		return true;
	}

	/**
	 * Cleans out the given task meta from the DB
	 *
	 * @return Boolean - the status of the delete operation
	 */
	public function delete_meta() {
		return Updraft_Task_Meta::bulk_delete_task_meta($this->id);
	}

	/**
	 * Helper function to convert object to array.
	 *
	 * @return array Object as array.
	 */
	public function to_array() {
		$task = get_object_vars($this);

		foreach (array( 'task_options', 'task_data', 'task_logs', 'task_extras' ) as $key) {
			if ($this->__isset($key))
				$task[$key] = $this->__get($key);
		}

		return $task;
	}

	/**
	 * Retrieve all the supported task statuses.
	 *
	 * Tasks should have a limited set of valid status values, this method provides a
	 * list of values and descriptions.
	 *
	 * @return array List of task statuses.
	 */
	public static function get_allowed_statuses() {
		$status = array(
			'initialised' => __('Initialised'),
			'active'   => __('Active'),
			'paused' => __('Paused'),
			'complete' => __('Completed')
		);

		return apply_filters('allowed_task_statuses', $status);
	}

	/**
	 * Retrieve the text description of the task status.
	 *
	 * @param String $status - The task status
	 *
	 * @return String 	Description of the task status.
	 */
	public static function get_status_description($status) {
		$list = self::get_allowed_statuses();

		if (!array_key_exists($status, self::get_allowed_statuses()))
			return __('Unknown');

		return apply_filters('task_status_description_{$status}', $list[$status], $status, $list);
	}


	/**
	 * Creates a new task instance and returns it
	 *
	 * @access public
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param String $description A description of the task
	 * @param Mixed  $options     A list of options to initialise the task
	 *
	 * @return Updraft_Task|false Task object, false otherwise.
	 */
	public static function create_task($description, $options = array()) {
		global $wpdb;

		$user_id = get_current_user_id();

		if (!$user_id)
			return false;

		$sql = $wpdb->prepare("INSERT INTO {$wpdb->base_prefix}tm_tasks (user_id, description, status) VALUES (%d, %s, %s)", $user_id, $description, 'active');

		$wpdb->query($sql);

		$task_id = $wpdb->insert_id;

		if (!$task_id)
			return false;

		$_task = $wpdb->get_row("SELECT * FROM {$wpdb->base_prefix}tm_tasks WHERE task_id = {$task_id} LIMIT 1");

		if (!$_task)
			return false;

		$task = new Updraft_Task($_task);
		$task->initialise($options);

		return $task;
	}
}
