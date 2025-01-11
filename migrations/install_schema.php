<?php
/**
 *
 * Username History. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2025, Anișor, https://phpbb.ro
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace anix\unamehistory\migrations;

class install_schema extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'anix_uname_history');
	}

	public static function depends_on()
	{
		return ['\phpbb\db\migration\data\v320\v320'];
	}

	public function update_schema()
	{
		return [
			'add_tables'		=> [
				$this->table_prefix . 'anix_uname_history'	=> [
					'COLUMNS'		=> [
						'id'                => ['UINT', null, 'auto_increment'],
                        'uname_id'          => ['INT:10', 0],
						'uname_name'        => ['VCHAR:255', ''],
                        'uname_date'        => ['INT:11', 0],
                        'screen'            => ['VCHAR:255', '']
					],
					'PRIMARY_KEY'	=> 'id'
				],
                $this->table_prefix . 'anix_uname_counter'  => [
                    'COLUMNS'       => [
                        'user_id'           => ['INT:10', 0],
                        'change_counter'    => ['INT:10', 0]
                    ],
                    'PRIMARY_KEY'   => 'user_id'
                ]
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables'		=> [
				$this->table_prefix . 'anix_uname_history',
                $this->table_prefix . 'anix_uname_counter'
			],
		];
	}
}
