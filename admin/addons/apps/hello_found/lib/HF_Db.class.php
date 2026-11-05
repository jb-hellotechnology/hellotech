<?php
/** Thin wrapper over Perch's DB. All queries quote via pdb(). */
class HF_Db
{
	public static function db() { return PerchDB::fetch(); }

	public static function t($name) { return PERCH_DB_PREFIX . 'hellofound_' . $name; }

	public static function q($v) { return self::db()->pdb($v); }

	public static function rows($sql) { $r = self::db()->get_rows($sql); return is_array($r) ? $r : []; }
	public static function row($sql)  { $r = self::db()->get_row($sql);  return is_array($r) ? $r : null; }
	public static function value($sql){ return self::db()->get_value($sql); }
	public static function exec($sql) { return self::db()->execute($sql); }

	public static function insert($table, array $data) { return self::db()->insert(self::t($table), $data); }

	public static function update($table, array $data, $id) { return self::db()->update(self::t($table), $data, 'id', $id); }

	public static function now() { return gmdate('Y-m-d H:i:s'); }
}
