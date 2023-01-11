<?php

/**
 * Mock 'Debug Bar' panel class.
 *
 * @package query-monitor
 */
abstract class Debug_Bar_Panel
{
    public $_title = '';
    public $_visible = \true;
    public function __construct($title = '')
    {
    }
    /**
     * Initializes the panel.
     */
    public function init()
    {
    }
    public function prerender()
    {
    }
    /**
     * Renders the panel.
     */
    public function render()
    {
    }
    public function is_visible()
    {
    }
    public function set_visible($visible)
    {
    }
    public function title($title = \null)
    {
    }
    public function debug_bar_classes($classes)
    {
    }
    public function Debug_Bar_Panel($title = '')
    {
    }
}
/**
 * Container for dispatchers.
 *
 * @package query-monitor
 */
class QM_Dispatchers implements \IteratorAggregate
{
    private $items = array();
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
    }
    public static function add(\QM_Dispatcher $dispatcher)
    {
    }
    public static function get($id)
    {
    }
    public static function init()
    {
    }
}
abstract class QM_Output
{
    /**
     * Collector instance.
     *
     * @var QM_Collector Collector.
     */
    protected $collector;
    /**
     * Timer instance.
     *
     * @var QM_Timer Timer.
     */
    protected $timer;
    public function __construct(\QM_Collector $collector)
    {
    }
    public abstract function get_output();
    public function output()
    {
    }
    public function get_collector()
    {
    }
    public final function get_timer()
    {
    }
    public final function set_timer(\QM_Timer $timer)
    {
    }
}
/**
 * Hook processor.
 *
 * @package query-monitor
 */
class QM_Hook
{
    public static function process($name, array $wp_filter, $hide_qm = \false, $hide_core = \false)
    {
    }
}
class QM_Backtrace
{
    protected static $ignore_class = array('wpdb' => \true, 'QueryMonitor' => \true, 'W3_Db' => \true, 'Debug_Bar_PHP' => \true, 'WP_Hook' => \true);
    protected static $ignore_method = array();
    protected static $ignore_func = array('include_once' => \true, 'require_once' => \true, 'include' => \true, 'require' => \true, 'call_user_func_array' => \true, 'call_user_func' => \true, 'trigger_error' => \true, '_doing_it_wrong' => \true, '_deprecated_argument' => \true, '_deprecated_file' => \true, '_deprecated_function' => \true, 'dbDelta' => \true);
    protected static $show_args = array('do_action' => 1, 'apply_filters' => 1, 'do_action_ref_array' => 1, 'apply_filters_ref_array' => 1, 'get_template_part' => 2, 'get_extended_template_part' => 2, 'load_template' => 'dir', 'dynamic_sidebar' => 1, 'get_header' => 1, 'get_sidebar' => 1, 'get_footer' => 1, 'class_exists' => 2, 'current_user_can' => 3, 'user_can' => 4, 'current_user_can_for_blog' => 4, 'author_can' => 4);
    protected static $filtered = \false;
    protected $trace = \null;
    protected $filtered_trace = \null;
    protected $calling_line = 0;
    protected $calling_file = '';
    public function __construct(array $args = array(), array $trace = \null)
    {
    }
    public function get_stack()
    {
    }
    public function get_caller()
    {
    }
    public function get_component()
    {
    }
    public static function get_frame_component(array $frame)
    {
    }
    public function get_trace()
    {
    }
    public function get_display_trace()
    {
    }
    public function get_filtered_trace()
    {
    }
    public function ignore($num)
    {
    }
    public function ignore_current_filter()
    {
    }
    public function filter_trace(array $frame)
    {
    }
}
abstract class QM_Dispatcher
{
    /**
     * Outputter instances.
     *
     * @var QM_Output[] Array of outputters.
     */
    protected $outputters = array();
    /**
     * Query Monitor plugin instance.
     *
     * @var QM_Plugin Plugin instance.
     */
    protected $qm;
    public function __construct(\QM_Plugin $qm)
    {
    }
    public abstract function is_active();
    public final function should_dispatch()
    {
    }
    /**
     * Processes and fetches the outputters for this dispatcher.
     *
     * @param string $outputter_id The outputter ID.
     * @return QM_Output[] Array of outputters.
     */
    public function get_outputters($outputter_id)
    {
    }
    public function init()
    {
    }
    protected function before_output()
    {
    }
    protected function after_output()
    {
    }
    public static function user_can_view()
    {
    }
    public static function user_verified()
    {
    }
    public static function editor_cookie()
    {
    }
    public static function verify_cookie($value)
    {
    }
}
/**
 * Timer that collects timing and memory usage.
 *
 * @package query-monitor
 */
class QM_Timer
{
    protected $start = \null;
    protected $end = \null;
    protected $trace = \null;
    protected $laps = array();
    public function start(array $data = \null)
    {
    }
    public function stop(array $data = \null)
    {
    }
    public function lap(array $data = \null, $name = \null)
    {
    }
    public function get_laps()
    {
    }
    public function get_time()
    {
    }
    public function get_memory()
    {
    }
    public function get_start_time()
    {
    }
    public function get_start_memory()
    {
    }
    public function get_end_time()
    {
    }
    public function get_end_memory()
    {
    }
    public function get_trace()
    {
    }
    public function end(array $data = \null)
    {
    }
}
abstract class QM_Plugin
{
    private $plugin = array();
    public static $minimum_php_version = '5.3.6';
    /**
     * Class constructor
     */
    protected function __construct($file)
    {
    }
    /**
     * Returns the URL for for a file/dir within this plugin.
     *
     * @param string $file The path within this plugin, e.g. '/js/clever-fx.js'
     * @return string URL
     */
    public final function plugin_url($file = '')
    {
    }
    /**
     * Returns the filesystem path for a file/dir within this plugin.
     *
     * @param string $file The path within this plugin, e.g. '/js/clever-fx.js'
     * @return string Filesystem path
     */
    public final function plugin_path($file = '')
    {
    }
    /**
     * Returns a version number for the given plugin file.
     *
     * @param string $file The path within this plugin, e.g. '/js/clever-fx.js'
     * @return string Version
     */
    public final function plugin_ver($file)
    {
    }
    /**
     * Returns the current plugin's basename, eg. 'my_plugin/my_plugin.php'.
     *
     * @return string Basename
     */
    public final function plugin_base()
    {
    }
    /**
     * Populates and returns the current plugin info.
     */
    private function _plugin($item, $file = '')
    {
    }
    public static function php_version_met()
    {
    }
    public static function php_version_nope()
    {
    }
}
/**
 * Plugin activation handler.
 *
 * @package query-monitor
 */
class QM_Activation extends \QM_Plugin
{
    protected function __construct($file)
    {
    }
    public function activate($sitewide = \false)
    {
    }
    public function deactivate()
    {
    }
    public function filter_active_plugins($plugins)
    {
    }
    public function filter_active_sitewide_plugins($plugins)
    {
    }
    public function php_notice()
    {
    }
    public static function init($file = \null)
    {
    }
}
/**
 * A convenience class for wrapping certain user-facing functionality.
 *
 * @package query-monitor
 */
class QM
{
    public static function emergency($message, array $context = array())
    {
    }
    public static function alert($message, array $context = array())
    {
    }
    public static function critical($message, array $context = array())
    {
    }
    public static function error($message, array $context = array())
    {
    }
    public static function warning($message, array $context = array())
    {
    }
    public static function notice($message, array $context = array())
    {
    }
    public static function info($message, array $context = array())
    {
    }
    public static function debug($message, array $context = array())
    {
    }
    public static function log($level, $message, array $context = array())
    {
    }
}
/**
 * Mock 'Debug Bar' plugin class.
 *
 * @package query-monitor
 */
class Debug_Bar
{
    public $panels = array();
    public function __construct()
    {
    }
    public function enqueue()
    {
    }
    public function init_panels()
    {
    }
    public function ensure_ajaxurl()
    {
    }
    public function Debug_Bar()
    {
    }
}
class QM_Collectors implements \IteratorAggregate
{
    private $items = array();
    private $processed = \false;
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
    }
    public static function add(\QM_Collector $collector)
    {
    }
    /**
     * Fetches a collector instance.
     *
     * @param string $id The collector ID.
     * @return QM_Collector|null The collector object.
     */
    public static function get($id)
    {
    }
    public static function init()
    {
    }
    public function process()
    {
    }
}
abstract class QM_Collector
{
    protected $timer;
    protected $data = array('types' => array(), 'component_times' => array());
    protected static $hide_qm = \null;
    public $concerned_actions = array();
    public $concerned_filters = array();
    public $concerned_constants = array();
    public $tracked_hooks = array();
    public function __construct()
    {
    }
    public final function id()
    {
    }
    protected function log_type($type)
    {
    }
    protected function maybe_log_dupe($sql, $i)
    {
    }
    protected function log_component($component, $ltime, $type)
    {
    }
    public static function timer_stop_float()
    {
    }
    public static function format_bool_constant($constant)
    {
    }
    public final function get_data()
    {
    }
    public final function set_id($id)
    {
    }
    public final function process_concerns()
    {
    }
    public function filter_concerns($concerns)
    {
    }
    public static function format_user(\WP_User $user_object)
    {
    }
    public static function enabled()
    {
    }
    public static function hide_qm()
    {
    }
    public function filter_remove_qm(array $item)
    {
    }
    public function process()
    {
    }
    public function post_process()
    {
    }
    public function tear_down()
    {
    }
    public function get_timer()
    {
    }
    public function set_timer(\QM_Timer $timer)
    {
    }
    public function get_concerned_actions()
    {
    }
    public function get_concerned_filters()
    {
    }
    public function get_concerned_options()
    {
    }
    public function get_concerned_constants()
    {
    }
}
/**
 * @implements ArrayAccess<string,mixed>
 */
abstract class QM_Data implements \ArrayAccess {
    /**
     * @var array<string, mixed>
     */
    public $types = array();

    /**
     * @var array<string, array<string, mixed>>
     * @phpstan-var array<string, array{
     *   component: string,
     *   ltime: float,
     *   types: array<array-key, int>,
     * }>
     */
    public $component_times = array();

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    #[ReturnTypeWillChange]
    final public function offsetSet( $offset, $value ) {
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    #[ReturnTypeWillChange]
    final public function offsetExists( $offset ) {
    }

    /**
     * @param mixed $offset
     * @return void
     */
    #[ReturnTypeWillChange]
    final public function offsetUnset( $offset ) {
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    #[ReturnTypeWillChange]
    final public function offsetGet( $offset ) {
    }
}
/**
 * Cache data transfer object.
 *
 * @package query-monitor
 */
class QM_Data_Cache extends QM_Data {
    /**
     * @var bool
     */
    public $has_object_cache;

    /**
     * @var bool
     */
    public $display_hit_rate_warning;

    /**
     * @var bool
     */
    public $has_opcode_cache;

    /**
     * @var int
     */
    public $cache_hit_percentage;

    /**
     * @var array<string, mixed>
     */
    public $stats;

    /**
     * @var array<string, bool>
     */
    public $object_cache_extensions;

    /**
     * @var array<string, bool>
     */
    public $opcode_cache_extensions;

}
/**
 * Plugin CLI command.
 *
 * @package query-monitor
 */
class QM_CLI extends \QM_Plugin
{
    protected function __construct($file)
    {
    }
    /**
     * Enable QM by creating the symlink for db.php
     */
    public function enable()
    {
    }
    public static function init($file = \null)
    {
    }
}
/**
 * The main Query Monitor plugin class.
 *
 * @package query-monitor
 */
class QueryMonitor extends \QM_Plugin
{
    protected function __construct($file)
    {
    }
    public function filter_plugin_action_links(array $actions)
    {
    }
    /**
     * Filter a user's capabilities so they can be altered at runtime.
     *
     * This is used to:
     *  - Grant the 'view_query_monitor' capability to the user if they have the ability to manage options.
     *
     * This does not get called for Super Admins.
     *
     * @param bool[]   $user_caps     Array of key/value pairs where keys represent a capability name and boolean values
     *                                represent whether the user has that capability.
     * @param string[] $required_caps Required primitive capabilities for the requested capability.
     * @param array    $args {
     *     Arguments that accompany the requested capability check.
     *
     *     @type string    $0 Requested capability.
     *     @type int       $1 Concerned user ID.
     *     @type mixed  ...$2 Optional second and further parameters.
     * }
     * @param WP_User  $user          Concerned user object.
     * @return bool[] Concerned user's capabilities.
     */
    public function filter_user_has_cap(array $user_caps, array $required_caps, array $args, \WP_User $user)
    {
    }
    public function action_plugins_loaded()
    {
    }
    public function action_init()
    {
    }
    public static function symlink_warning()
    {
    }
    /**
     * Registers the Query Monitor user capability group for the Members plugin.
     *
     * @link https://wordpress.org/plugins/members/
     */
    public function action_register_members_groups()
    {
    }
    /**
     * Registers the View Query Monitor user capability for the Members plugin.
     *
     * @link https://wordpress.org/plugins/members/
     */
    public function action_register_members_caps()
    {
    }
    /**
     * Registers the Query Monitor user capability group for the User Role Editor plugin.
     *
     * @link https://wordpress.org/plugins/user-role-editor/
     *
     * @param array[] $groups Array of existing groups.
     * @return array[] Updated array of groups.
     */
    public function filter_ure_groups(array $groups)
    {
    }
    /**
     * Registers the View Query Monitor user capability for the User Role Editor plugin.
     *
     * @link https://wordpress.org/plugins/user-role-editor/
     *
     * @param array[] $caps Array of existing capabilities.
     * @return array[] Updated array of capabilities.
     */
    public function filter_ure_caps(array $caps)
    {
    }
    public static function init($file = \null)
    {
    }
}
class QM_Util
{
    protected static $file_components = array();
    protected static $file_dirs = array();
    protected static $abspath = \null;
    protected static $contentpath = \null;
    protected static $sort_field = \null;
    private function __construct()
    {
    }
    public static function convert_hr_to_bytes($size)
    {
    }
    public static function standard_dir($dir, $path_replace = \null)
    {
    }
    public static function normalize_path($path)
    {
    }
    public static function get_file_dirs()
    {
    }
    public static function get_file_component($file)
    {
    }
    public static function populate_callback(array $callback)
    {
    }
    public static function is_ajax()
    {
    }
    public static function is_async()
    {
    }
    public static function get_admins()
    {
    }
    public static function is_multi_network()
    {
    }
    public static function get_client_version($client)
    {
    }
    public static function get_query_type($sql)
    {
    }
    public static function display_variable($value)
    {
    }
    /**
     * Shortens a fully qualified name to reduce the length of the names of long namespaced symbols.
     *
     * This initialises portions that do not form the first or last portion of the name. For example:
     *
     *     Inpsyde\Wonolog\HookListener\HookListenersRegistry->hook_callback()
     *
     * becomes:
     *
     *     Inpsyde\W\H\HookListenersRegistry->hook_callback()
     *
     * @param string $fqn A fully qualified name.
     * @return string A shortened version of the name.
     */
    public static function shorten_fqn($fqn)
    {
    }
    /**
     * Helper function for JSON encoding data and formatting it in a consistent and compatible manner.
     *
     * @param mixed $data The data to be JSON encoded.
     * @return string The JSON encoded data.
     */
    public static function json_format($data)
    {
    }
    public static function is_stringy($data)
    {
    }
    public static function sort(array &$array, $field)
    {
    }
    public static function rsort(array &$array, $field)
    {
    }
    private static function _rsort($a, $b)
    {
    }
    private static function _sort($a, $b)
    {
    }
}
/**
 * Abstract output class for HTML pages.
 *
 * @package query-monitor
 */
abstract class QM_Output_Html extends \QM_Output
{
    protected static $file_link_format = \null;
    protected $current_id = \null;
    protected $current_name = \null;
    public function name()
    {
    }
    public function admin_menu(array $menu)
    {
    }
    public function get_output()
    {
    }
    protected function before_tabular_output($id = \null, $name = \null)
    {
    }
    protected function after_tabular_output()
    {
    }
    protected function before_non_tabular_output($id = \null, $name = \null)
    {
    }
    protected function after_non_tabular_output()
    {
    }
    protected function output_concerns()
    {
    }
    protected function before_debug_bar_output($id = \null, $name = \null)
    {
    }
    protected function after_debug_bar_output()
    {
    }
    protected function build_notice($notice)
    {
    }
    public static function output_inner($vars)
    {
    }
    /**
     * Returns the table filter controls. Safe for output.
     *
     * @param  string   $name   The name for the `data-` attributes that get filtered by this control.
     * @param  string[] $values Option values for this control.
     * @param  string   $label  Label text for the filter control.
     * @param  array    $args {
     *     @type string $highlight The name for the `data-` attributes that get highlighted by this control.
     *     @type array  $prepend   Associative array of options to prepend to the list of values.
     *     @type array  $append    Associative array of options to append to the list of values.
     * }
     * @return string Markup for the table filter controls.
     */
    protected function build_filter($name, array $values, $label, $args = array())
    {
    }
    /**
     * Returns the column sorter controls. Safe for output.
     *
     * @param string $heading Heading text for the column. Optional.
     * @return string Markup for the column sorter controls.
     */
    protected function build_sorter($heading = '')
    {
    }
    /**
     * Returns a toggle control. Safe for output.
     *
     * @return string Markup for the column sorter controls.
     */
    protected static function build_toggler()
    {
    }
    protected function menu(array $args)
    {
    }
    /**
     * Returns the given SQL string in a nicely presented format. Safe for output.
     *
     * @param  string $sql An SQL query string.
     * @return string      The SQL formatted with markup.
     */
    public static function format_sql($sql)
    {
    }
    /**
     * Returns the given URL in a nicely presented format. Safe for output.
     *
     * @param  string $url A URL.
     * @return string      The URL formatted with markup.
     */
    public static function format_url($url)
    {
    }
    /**
     * Returns a file path, name, and line number, or a clickable link to the file. Safe for output.
     *
     * @link https://querymonitor.com/blog/2019/02/clickable-stack-traces-and-function-names-in-query-monitor/
     *
     * @param  string $text        The display text, such as a function name or file name.
     * @param  string $file        The full file path and name.
     * @param  int    $line        Optional. A line number, if appropriate.
     * @param  bool   $is_filename Optional. Is the text a plain file name? Default false.
     * @return string The fully formatted file link or file name, safe for output.
     */
    public static function output_filename($text, $file, $line = 0, $is_filename = \false)
    {
    }
    /**
     * Provides a protocol URL for edit links in QM stack traces for various editors.
     *
     * @param string $editor the chosen code editor
     * @param string $default_format a format to use if no editor is found
     *
     * @return string a protocol URL format
     */
    public static function get_editor_file_link_format($editor, $default_format)
    {
    }
    public static function get_file_link_format()
    {
    }
    public static function get_file_path_map()
    {
    }
    public static function has_clickable_links()
    {
    }
}
/**
 * Abstract output class for HTTP headers.
 *
 * @package query-monitor
 */
abstract class QM_Output_Headers extends \QM_Output
{
    public function output()
    {
    }
}
/**
 * HTTP redirects output for HTTP headers.
 *
 * @package query-monitor
 */
class QM_Output_Headers_Redirects extends \QM_Output_Headers
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_Redirects Collector.
     */
    protected $collector;
    public function get_output()
    {
    }
}
/**
 * General overview output for HTTP headers.
 *
 * @package query-monitor
 */
class QM_Output_Headers_Overview extends \QM_Output_Headers
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_Overview Collector.
     */
    protected $collector;
    public function get_output()
    {
    }
}
/**
 * PHP error output for HTTP headers.
 *
 * @package query-monitor
 */
class QM_Output_Headers_PHP_Errors extends \QM_Output_Headers
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_PHP_Errors Collector.
     */
    protected $collector;
    public function get_output()
    {
    }
}
/**
 * General overview output for HTML pages.
 *
 * @package query-monitor
 */
class QM_Output_Html_Overview extends \QM_Output_Html
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_Overview Collector.
     */
    protected $collector;
    public function __construct(\QM_Collector $collector)
    {
    }
    public function name()
    {
    }
    public function output()
    {
    }
    public function admin_title(array $existing)
    {
    }
}
/**
 * Request data output for HTML pages.
 *
 * @package query-monitor
 */
class QM_Output_Html_Request extends \QM_Output_Html
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_Request Collector.
     */
    protected $collector;
    public function __construct(\QM_Collector $collector)
    {
    }
    public function name()
    {
    }
    public function output()
    {
    }
    public function admin_menu(array $menu)
    {
    }
}
/**
 * Database query output for HTML pages.
 *
 * @package query-monitor
 */
class QM_Output_Html_DB_Queries extends \QM_Output_Html
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_DB_Queries Collector.
     */
    protected $collector;
    public $query_row = 0;
    public function __construct(\QM_Collector $collector)
    {
    }
    public function name()
    {
    }
    public function output()
    {
    }
    protected function output_empty_queries()
    {
    }
    protected function output_error_queries(array $errors)
    {
    }
    protected function output_expensive_queries(array $expensive)
    {
    }
    protected function output_queries($name, \stdClass $db, array $data)
    {
    }
    protected function output_query_row(array $row, array $cols)
    {
    }
    public function admin_title(array $existing)
    {
    }
    public function admin_class(array $class)
    {
    }
    public function admin_menu(array $menu)
    {
    }
    public function panel_menu(array $menu)
    {
    }
}
/**
 * Language and locale output for HTML pages.
 *
 * @package query-monitor
 */
class QM_Output_Html_Languages extends \QM_Output_Html
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_Languages Collector.
     */
    protected $collector;
    public function __construct(\QM_Collector $collector)
    {
    }
    public function name()
    {
    }
    public function output()
    {
    }
    public function admin_menu(array $menu)
    {
    }
}
/**
 * Template and theme output for HTML pages.
 *
 * @package query-monitor
 */
class QM_Output_Html_Theme extends \QM_Output_Html
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_Theme Collector.
     */
    protected $collector;
    public function __construct(\QM_Collector $collector)
    {
    }
    public function name()
    {
    }
    public function output()
    {
    }
    public function admin_menu(array $menu)
    {
    }
    public function panel_menu(array $menu)
    {
    }
}
/**
 * PHP error output for HTML pages.
 *
 * @package query-monitor
 */
class QM_Output_Html_PHP_Errors extends \QM_Output_Html
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_PHP_Errors Collector.
     */
    protected $collector;
    public function __construct(\QM_Collector $collector)
    {
    }
    public function name()
    {
    }
    public function output()
    {
    }
    public function admin_class(array $class)
    {
    }
    public function admin_menu(array $menu)
    {
    }
    public function panel_menu(array $menu)
    {
    }
}
/**
 * Database query calling function output for HTML pages.
 *
 * @package query-monitor
 */
class QM_Output_Html_DB_Callers extends \QM_Output_Html
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_DB_Callers Collector.
     */
    protected $collector;
    public function __construct(\QM_Collector $collector)
    {
    }
    public function name()
    {
    }
    public function output()
    {
    }
    public function panel_menu(array $menu)
    {
    }
}
/**
 * Duplicate database query output for HTML pages.
 *
 * @package query-monitor
 */
class QM_Output_Html_DB_Dupes extends \QM_Output_Html
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_DB_Dupes Collector.
     */
    protected $collector;
    public function __construct(\QM_Collector $collector)
    {
    }
    public function name()
    {
    }
    public function output()
    {
    }
    public function admin_menu(array $menu)
    {
    }
    public function panel_menu(array $menu)
    {
    }
}
/**
 * Template conditionals output for HTML pages.
 *
 * @package query-monitor
 */
class QM_Output_Html_Conditionals extends \QM_Output_Html
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_Conditionals Collector.
     */
    protected $collector;
    public function __construct(\QM_Collector $collector)
    {
    }
    public function name()
    {
    }
    public function output()
    {
    }
    public function admin_menu(array $menu)
    {
    }
    public function panel_menu(array $menu)
    {
    }
}
/**
 * Scripts and styles output for HTML pages.
 *
 * @package query-monitor
 */
abstract class QM_Output_Html_Assets extends \QM_Output_Html
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_Assets Collector.
     */
    protected $collector;
    public function __construct(\QM_Collector $collector)
    {
    }
    public abstract function get_type_labels();
    public function output()
    {
    }
    protected function dependency_row($handle, array $asset, $label)
    {
    }
    public function _prefix_type($val)
    {
    }
    public function admin_class(array $class)
    {
    }
    public function admin_menu(array $menu)
    {
    }
}
/**
 * Enqueued scripts output for HTML pages.
 *
 * @package query-monitor
 */
class QM_Output_Html_Assets_Scripts extends \QM_Output_Html_Assets
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_Assets_Scripts Collector.
     */
    protected $collector;
    public function name()
    {
    }
    public function get_type_labels()
    {
    }
}
/**
 * Timing and profiling output for HTML pages.
 *
 * @package query-monitor
 */
class QM_Output_Html_Timing extends \QM_Output_Html
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_Timing Collector.
     */
    protected $collector;
    public function __construct(\QM_Collector $collector)
    {
    }
    public function name()
    {
    }
    public function output()
    {
    }
    public function admin_menu(array $menu)
    {
    }
}
/**
 * Database query calling component output for HTML pages.
 *
 * @package query-monitor
 */
class QM_Output_Html_DB_Components extends \QM_Output_Html
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_DB_Components Collector.
     */
    protected $collector;
    public function __construct(\QM_Collector $collector)
    {
    }
    public function name()
    {
    }
    public function output()
    {
    }
    public function panel_menu(array $menu)
    {
    }
}
/**
 * Admin screen output for HTML pages.
 *
 * @package query-monitor
 */
class QM_Output_Html_Admin extends \QM_Output_Html
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_Admin Collector.
     */
    protected $collector;
    public function __construct(\QM_Collector $collector)
    {
    }
    public function name()
    {
    }
    public function output()
    {
    }
}
/**
 * Block editor data output for HTML pages.
 *
 * @package query-monitor
 */
class QM_Output_Html_Block_Editor extends \QM_Output_Html
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_Block_Editor Collector.
     */
    protected $collector;
    public function __construct(\QM_Collector $collector)
    {
    }
    public function name()
    {
    }
    public function output()
    {
    }
    protected static function render_block($i, array $block, array $data)
    {
    }
    public function admin_menu(array $menu)
    {
    }
}
/**
 * User capability checks output for HTML pages.
 *
 * @package query-monitor
 */
class QM_Output_Html_Caps extends \QM_Output_Html
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_Caps Collector.
     */
    protected $collector;
    public function __construct(\QM_Collector $collector)
    {
    }
    public function name()
    {
    }
    public function output()
    {
    }
    public function admin_menu(array $menu)
    {
    }
}
/**
 * HTTP API request output for HTML pages.
 *
 * @package query-monitor
 */
class QM_Output_Html_HTTP extends \QM_Output_Html
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_HTTP Collector.
     */
    protected $collector;
    public function __construct(\QM_Collector $collector)
    {
    }
    public function name()
    {
    }
    public function output()
    {
    }
    public function admin_class(array $class)
    {
    }
    public function admin_menu(array $menu)
    {
    }
}
/**
 * Enqueued styles output for HTML pages.
 *
 * @package query-monitor
 */
class QM_Output_Html_Assets_Styles extends \QM_Output_Html_Assets
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_Assets_Styles Collector.
     */
    protected $collector;
    public function name()
    {
    }
    public function get_type_labels()
    {
    }
}
/**
 * 'Debug Bar' output for HTML pages.
 *
 * @package query-monitor
 */
class QM_Output_Html_Debug_Bar extends \QM_Output_Html
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_Debug_Bar Collector.
     */
    protected $collector;
    public function __construct(\QM_Collector $collector)
    {
    }
    public function name()
    {
    }
    public function output()
    {
    }
}
/**
 * Hooks and actions output for HTML pages.
 *
 * @package query-monitor
 */
class QM_Output_Html_Hooks extends \QM_Output_Html
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_Hooks Collector.
     */
    protected $collector;
    public function __construct(\QM_Collector $collector)
    {
    }
    public function name()
    {
    }
    public function output()
    {
    }
    public static function output_hook_table(array $hooks)
    {
    }
}
/**
 * Environment data output for HTML pages.
 *
 * @package query-monitor
 */
class QM_Output_Html_Environment extends \QM_Output_Html
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_Environment Collector.
     */
    protected $collector;
    public function __construct(\QM_Collector $collector)
    {
    }
    public function name()
    {
    }
    public function output()
    {
    }
}
/**
 * Request and response headers output for HTML pages.
 *
 * @package query-monitor
 */
class QM_Output_Html_Headers extends \QM_Output_Html
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_Raw_Request Collector.
     */
    protected $collector;
    public function __construct(\QM_Collector $collector)
    {
    }
    /**
     * Collector name.
     *
     * This is unused.
     *
     * @return string
     */
    public function name()
    {
    }
    public function output()
    {
    }
    public function output_request()
    {
    }
    public function output_response()
    {
    }
    protected function output_header_table(array $headers, $title)
    {
    }
    public function panel_menu(array $menu)
    {
    }
}
/**
 * PSR-3 compatible logging output for HTML pages.
 *
 * @package query-monitor
 */
class QM_Output_Html_Logger extends \QM_Output_Html
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_Logger Collector.
     */
    protected $collector;
    public function __construct(\QM_Collector $collector)
    {
    }
    public function name()
    {
    }
    public function output()
    {
    }
    public function admin_class(array $class)
    {
    }
    public function admin_menu(array $menu)
    {
    }
}
/**
 * Transient storage output for HTML pages.
 *
 * @package query-monitor
 */
class QM_Output_Html_Transients extends \QM_Output_Html
{
    /**
     * Collector instance.
     *
     * @var QM_Collector_Transients Collector.
     */
    protected $collector;
    public function __construct(\QM_Collector $collector)
    {
    }
    public function name()
    {
    }
    public function output()
    {
    }
    public function admin_menu(array $menu)
    {
    }
}
