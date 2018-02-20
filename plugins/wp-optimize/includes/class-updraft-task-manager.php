<?php

if (!defined('Updraft_Task_Manager')) :

class Updraft_Task_Manager {

	private $_tasklist;

	protected $_logger;

	protected static $_instance = null;

	/**
	 * The Task Manager constructor
	 */
	public function __construct() {


	}

	/**
	 * Returns the only instance of this class
	 *
	 * @return Updraft_Task_Manager
	 */
	public static function instance() {
		if (empty(self::$_instance)) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Doc Stub
	 */
	public function perform_task() {
		
	}

	/**
	 * Gets a list of all active tasks
	 *
	 * @return Mixed - array of UpdraftPlus_Task ojects or NULL if none found
	 */
	public function get_active_tasks() {
		return $this->get_tasks('active');
	}

	/**
	 * Gets a list of all completed tasks
	 *
	 * @return Mixed - array of UpdraftPlus_Task ojects or NULL if none found
	 */
	public function get_completed_tasks() {
		return $this->get_tasks('complete');
	}

	/**
	 * Gets a list of all tasks that matches the $status flag
	 *
	 * @param String $status - status of tasks to return, defaults to all tasks
	 *
	 * @return Mixed - array of UpdraftPlus_Task ojects or NULL if none found
	 */
	public function get_tasks($status) {
		global $wpdb;

		$tasks = array();
		
		if (array_key_exists($status, Updraft_Task::get_task_statuses())) {
			$sql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}tm_tasks WHERE status = %s", $status);
		} else {
			$sql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}tm_tasks");
		}

		$_tasks = $wpdb->get_results($sql);
		if (!$_tasks) return;


		foreach ($_tasks as $_task) {
			$task = new Updraft_Task($_task);
			array_push($tasks, $task);
		}

		return $tasks;
	}

	/**
	 * Gets a list of all tasks that matches the $status flag
	 *
	 * @param String $task_id - id of task to return, defaults to all tasks
	 *
	 * @return String - status of UpdraftPlus_Task ojects or false if none found
	 */
	public function get_task_status($task_id) {
		$task = $this->get_task_instance($task_id);
		if (!$task) return;

		return $task->get_task_status();
	}

	/**
	 * Ends a given task
	 *
	 * @param int|Updraft_Task - $task Task ID or object.
	 */
	public function end_task($task) {
		if ($task instanceof Updraft_Task) {
			$task->complete();
		} else {
			$task = get_task_instance($task_id);
			$task->complete();
		}
	}

	/**
	 * Retrieve the task instance using its ID
	 *
	 * @access public
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $task_id Task ID.
	 * @return Task|false Task object, false otherwise.
	 */
	public function get_task_instance($task_id) {
		global $wpdb;

		$task_id = (int) $task_id;
		if (!$task_id) return false;

		$_task = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}tm_tasks WHERE task_id = {$task_id} LIMIT 1");

		if (! $_task)
			return false;

		return new Updraft_Task($_task);
	}

	/**
	 * Cleans out all complete tasks from the DB.
	 */
	public function clean_up_old_tasks() {
		$completed_tasks = $this->get_completed_tasks();

		foreach ($completed_tasks as $tasks) {
			$task->delete_task_meta($this->task_id);
			$task->delete_task($this->task_id);
		}
	}

	/**
	 * Sets the logger for this task.
	 *
	 * @param Object $logger - the logger for the task
	 */
	public function set_logger($logger) {
		$this->_logger = $logger;
	}

	/**
	 * Returns the logger used by this task.
	 *
	 * @return Object $logger - the logger for the task
	 */
	public function get_logger() {
		return $this->_logger;
	}
}

/**
 * Returns the singleton Updraft_Task_Manager class
 */
function Updraft_Task_Manager() {
	return Updraft_Task_Manager::instance();
}

$GLOBALS['updraft_task_manager'] = Updraft_Task_Manager();

endif;
