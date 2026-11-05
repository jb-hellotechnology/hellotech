<?php
/**
 * Adapter over Perch Members. ALL Members API use is isolated here, because
 * Members is a separate paid add-on that is not in this repo. If a call below
 * differs in your installed Members version, fix it here only.
 */
class HF_Members
{
	public static function available()
	{
		return class_exists('PerchMembers_Members') && function_exists('perch_member_logged_in');
	}

	public static function logged_in() { return self::available() && perch_member_logged_in(); }

	/** @return int|null current member ID */
	public static function current_id()
	{
		if (!self::logged_in()) return null;
		$id = perch_member_get('id');
		return $id ? (int)$id : null;
	}

	/** @return array|null ['id','email','name'] */
	public static function details($member_id)
	{
		if (!self::available()) return null;
		$row = HF_Db::row('SELECT * FROM ' . PERCH_DB_PREFIX . 'members WHERE memberID=' . (int)$member_id . ' LIMIT 1');
		if (!$row) return null;
		$props = json_decode($row['memberProperties'] ?? '', true) ?: [];
		$name  = trim(($props['first_name'] ?? '') . ' ' . ($props['last_name'] ?? ''));
		if ($name === '') $name = $props['name'] ?? '';
		return ['id' => (int)$row['memberID'], 'email' => $row['memberEmail'], 'name' => $name !== '' ? $name : $row['memberEmail']];
	}

	public static function find_id_by_email($email)
	{
		$v = HF_Db::value('SELECT memberID FROM ' . PERCH_DB_PREFIX . 'members WHERE memberEmail=' . HF_Db::q($email) . ' LIMIT 1');
		return $v ? (int)$v : null;
	}

	/** Create a member with a random password and tag them into the customers group. @return int|null */
	public static function create($email, $name)
	{
		if (!self::available()) throw new RuntimeException('Perch Members is not installed.');
		$API = new PerchAPI(1.0, 'perch_members');
		$Members = new PerchMembers_Members($API);

		$parts = explode(' ', trim($name), 2);
		$props = ['first_name' => $parts[0] ?? '', 'last_name' => $parts[1] ?? ''];

		// Members hashes with phpass, not password_hash(). The customer sets a real one via /members/reset.
		$Hasher = PerchUtil::get_password_hasher();
		$Member = $Members->create([
			'memberAuthType'   => 'native',
			'memberEmail'      => $email,
			'memberPassword'   => $Hasher->HashPassword(bin2hex(random_bytes(16))),
			'memberStatus'     => 'active',
			'memberCreated'    => HF_Db::now(),
			'memberProperties' => PerchUtil::json_safe_encode($props),
		]);
		if (!$Member) return null;

		$Member->update(['memberAuthID' => $Member->memberID()]);
		$Member->add_tag(HELLOFOUND_MEMBER_TAG, 'hello.found customers');
		return (int)$Member->memberID();
	}

	/** Start a Members session for the given member ID (same session Members' own login creates). */
	public static function log_in($member_id)
	{
		if (!self::available()) return false;
		$row = HF_Db::row('SELECT * FROM ' . PERCH_DB_PREFIX . 'members WHERE memberID=' . (int)$member_id . " AND memberStatus='active' LIMIT 1");
		if (!$row) return false;
		unset($row['memberPassword']);
		$Auth = new HF_MembersAuth(new PerchAPI(1.0, 'perch_members'));
		$Auth->start_for($row);
		return true;
	}
}

/** Exposes Members' protected session creation (declared only when Members is present). */
if (class_exists('PerchMembers_Auth')) {
	class HF_MembersAuth extends PerchMembers_Auth
	{
		public function start_for(array $row) { $this->_generate_session($row); $this->recover_session(); }
	}
}
