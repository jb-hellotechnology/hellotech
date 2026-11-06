<?php
require __DIR__ . '/../_hf.php';

// Log the member out of Perch Members, then show a confirmation.
if (HF_Members::logged_in()) perch_member_log_out();

hf_page_start('Signed out');
?>
<div class="hf-card">
  <h1 class="h2">You've been signed out</h1>
  <p>See you next time.</p>
  <p>
    <a class="btn btn--primary" href="<?= hf_e(HELLOFOUND_LOGIN_URL . '?r=' . rawurlencode(hf_url('dashboard/'))) ?>">Log back in</a>
    <a href="/">Back to hellotechnology.co.uk</a>
  </p>
</div>
<?php hf_page_end();
