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
      ob_start();
      perch_content('Page content');
      $ht_main = ob_get_clean();
      echo $ht_main;
      ht_schema_faq_from_html($ht_main);
      ht_schema_crumbs_from_html($ht_main);
    ?>
  </main>

  <?php perch_layout('global.footer'); ?>

</body>
</html>
