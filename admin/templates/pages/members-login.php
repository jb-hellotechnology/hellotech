<?php
  if (isset($_GET['logout']) && perch_member_logged_in()) { perch_member_log_out(); header('Location: /members/login'); exit; }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php perch_pages_title(); ?></title>
  <meta name="robots" content="noindex, nofollow">
  <?php perch_page_attributes(); ?>
  <?php perch_layout('global.head'); ?>
</head>
<body>
  <?php perch_layout('global.header'); ?>

  <main id="main" tabindex="-1">
    <section class="section">
      <div class="container" style="max-width:480px">
        <?php if (perch_member_logged_in()): ?>
          <h1 class="h2">You're logged in</h1>
          <p><a class="btn btn--primary" href="/found/dashboard/">Go to your dashboard</a></p>
          <p><a href="/members/login?logout=1">Log out</a></p>
        <?php else: ?>
          <h1 class="h2">Log in</h1>
          <?php perch_members_login_form(); ?>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <?php perch_layout('global.footer'); ?>
</body>
</html>
