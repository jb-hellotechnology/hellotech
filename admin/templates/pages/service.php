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

  <main>
    <?php
      // Service landing page — build from blocks (Service hero, Trust strip,
      // Features, Process, Pricing, FAQ, CTA).
      perch_content_create('Page content', [
        'template' => 'service-blocks.html',
      ]);
      perch_content('Page content');
    ?>
  </main>

  <?php perch_layout('global.footer'); ?>

</body>
</html>
