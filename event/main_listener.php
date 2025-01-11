<?php
/**
 *
 * Username History. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2025, Anișor, https://phpbb.ro
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace anix\unamehistory\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Username History Event listener.
 */
class main_listener implements EventSubscriberInterface
{
	public static function getSubscribedEvents()
	{
		return [
			'core.user_setup'							=> 'load_language_on_setup',
	        'core.acp_users_overview_modify_data'       => 'acp_users_overview_modify_data',
            'core.ucp_profile_reg_details_validate'     => 'ucp_profile_reg_details_validate',
            'core.memberlist_view_profile'              => 'memberlist_view_profile'
		];
	}

    /** @var \phpbb\db\driver\driver_interface */
    protected $db;

    /** @var string phpEx */
    protected $table_prefix;

    /** @var user */
    protected $user;

    /** @var template $template */
    protected $template;

	/* @var \phpbb\language\language */
	protected $language;

	/**
	 * Constructor
	 *
	 * @param \phpbb\language\language	$language	Language object
	 */
	public function __construct(
        \phpbb\db\driver\driver_interface $db,
        $table_prefix,
        \phpbb\user $user,
        \phpbb\template\template $template,
        \phpbb\language\language $language
    )
	{
        $this->db = $db;
        $this->table_prefix = $table_prefix;
        $this->user = $user;
        $this->template = $template;
		$this->language = $language;
	}

	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = [
			'ext_name' => 'anix/unamehistory',
			'lang_set' => 'common',
		];
		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function acp_users_overview_modify_data($event)
	{
        //Get the data we need
        $user_row = $event['user_row'];
        $data = $event['data'];
        $user_id = $user_row['user_id'];

        //Check if the username input is different from the old one
        $uname_changed = ($user_row['username'] != $data['username']) ? $data['username'] : false;

        //Screen
        $screen = $this->language->lang('UH_ACP');

        //If changed, we perform the log
        if ($uname_changed) {
            $this->log_old_username($user_id, $user_row['username'], $screen);
            $this->increment_changes($user_id);
        }
	}

    public function ucp_profile_reg_details_validate($event) {
        //Get the data we need
        $data = $event['data'];
        $current_username = $this->user->data['username'];
        $user_id = $this->user->data['user_id'];

        //Check if the username input is different from the old one
        $uname_changed = ($data['username'] != $current_username) ? $current_username : false;

        //Screen
        $screen = $this->language->lang('UH_UCP');

        //If changed, we perform the log
        if ($uname_changed) {
            $this->log_old_username($user_id, $current_username, $screen);
            $this->increment_changes($user_id);
        }
    }

    public function memberlist_view_profile($event) {
        //Get the data we need
        $member = $event['member'];
        $user_id = $member['user_id'];

        // Fetch previous usernames
        $username_history = $this->get_previous_usernames($user_id);
        // Fetch the latest username
        $latest_username = $this->get_latest_username($user_id);
        // Fetch username change counter
        $username_counter = $this->get_change_counter($user_id);

        $this->template->assign_vars([
            'USERNAME_HISTORY'   => $username_history,
            'LATEST_USERNAME'    => $latest_username,
            'USERNAME_COUNTER'   => $username_counter,
        ]);
    }

    /**
     * Logging the data in the designed table for the given user
     * @param int $user_id
     * @param string $username
     * @param string $screen
     * @return void
     */
    protected function log_old_username(int $user_id, string $username, string $screen = ''): void {
        $sql = 'INSERT INTO ' . $this->table_prefix . 'anix_uname_history
            (uname_id, uname_name, uname_date, screen)
            VALUES ('
            . $user_id . ", '"
            . $this->db->sql_escape($username) . "', "
            . time() . ", '"
            . $this->db->sql_escape($screen) . "')";

        $this->db->sql_query($sql);
    }

    /**
     * Incrementing the username changes for the given user
     * @param int $user_id
     * @return void
     */
    protected function increment_changes(int $user_id): void {
        $sql = 'INSERT INTO ' . $this->table_prefix . 'anix_uname_counter
            (user_id, change_counter)
            VALUES (' . intval($user_id) . ', 1)
            ON DUPLICATE KEY UPDATE change_counter = change_counter + 1';

        $this->db->sql_query($sql);
    }

    /**
     * Returns a list of previous usernames apart from the most recent one
     * @param int $user_id
     * @return array
     */
    protected function get_previous_usernames(int $user_id): array
    {
        $cache_time = 300; // 5 minutes cache
        $username_history = [];

        // Query the table for username history
        // We exclude the most recent username changed
        $sql = 'SELECT id, uname_name, uname_date, screen
            FROM ' . $this->table_prefix . 'anix_uname_history
            WHERE uname_id = ' . $user_id . '
              AND id != (
                SELECT MAX(id) 
                FROM ' . $this->table_prefix . 'anix_uname_history
                WHERE uname_id = ' . $user_id . '
              )
            ORDER BY uname_date DESC';

        $result = $this->db->sql_query($sql, $cache_time);

        while ($row = $this->db->sql_fetchrow($result)) {
            $username_history[] = [
                'uname_name'    => $row['uname_name'],
                'uname_date'    => $row['uname_date'],
                'screen'        => $row['screen']
            ];
        }
        $this->db->sql_freeresult($result);

        return $username_history;
    }

    /**
     * Returns the data about the most recent username
     * @param int $user_id
     * @return array|null
     */
    protected function get_latest_username(int $user_id): ?array {
        $cache_time = 300; // 5 minutes cache

        // Query the table to get the record with the highest ID for the given user
        $sql = 'SELECT uname_name, uname_date, screen
            FROM ' . $this->table_prefix . 'anix_uname_history
            WHERE uname_id = ' . $user_id . '
            ORDER BY id DESC
            LIMIT 1'; // Limit the results to 1

        $result = $this->db->sql_query($sql, $cache_time);
        $latest_record = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        // Return the record or null if no record found
        return $latest_record ?: null;
    }

    /**
     * How many times this user change their username?
     * @param int $user_id
     * @return int
     */
    protected function get_change_counter(int $user_id): int {
        $cache_time = 300; // 5 minutes cache

        $sql = 'SELECT change_counter 
        FROM ' . $this->table_prefix . 'anix_uname_counter 
        WHERE user_id = ' . $user_id;

        $result = $this->db->sql_query($sql, $cache_time);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        //Return the record or 0 if no record found
        return $row['change_counter'] ?? 0;
    }
}
