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
    <section class="pt-48">
      <div class="container container--narrow">
        <?php
          // Editable page intro (a normal Perch content region)
          perch_content_create('Intro', ['template' => 'blog/intro.html']);
          perch_content('Intro');
        ?>
      </div>
    </section>

    <section class="section pt-20">
      <div class="container">
        <?php
          // Perch Blog listing — newest first, paginated. Cards + paging markup
          // live in admin/templates/blog/post_in_list.html.
          perch_blog_custom([
            'template'   => 'post_in_list.html',
            'sort'       => 'postDateTime',
            'sort-order' => 'DESC',
            'count'      => 9,
            'paginate'   => true,
          ]);
        ?>
      </div>
    </section>
  </main>

  <?php perch_layout('cta'); ?>
  <?php perch_layout('global.footer'); ?>

</body>
</html>
