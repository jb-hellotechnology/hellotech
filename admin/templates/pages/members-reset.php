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
        <h1 class="h2">Reset your password</h1>
        <?php perch_member_form('reset_password.html'); ?>
        <p><a href="/members/login">Back to log in</a></p>
      </div>
    </section>
  </main>

  <?php perch_layout('global.footer'); ?>
</body>
</html>
