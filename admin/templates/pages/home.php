<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php perch_pages_title(); ?></title>
  <?php perch_page_attributes(); ?>
  <?php perch_layout('global.head'); ?>
</head>
<body>

  <?php perch_layout('global.header'); ?>

  <div id="top"></div>

  <main>
    <?php
      // Home page body — build it from the section blocks (start with a Hero).
      perch_content_create('Page content', [
        'template' => 'home-blocks.html',
      ]);
      perch_content('Page content');
    ?>
  </main>

  <?php perch_layout('global.footer'); ?>

</body>
</html>
