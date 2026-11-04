<?php
  // Single blog post (Perch Blog). Slug comes from the {s} URL segment.
  $slug      = perch_get('s');
  $post_html = perch_blog_post($slug, true);

  // Pull the post's raw data + a properly-sized hero image URL for the schema.
  $post = null;
  $post_image = '';
  if ($post_html) {
      $items = perch_blog_custom([
          'filter' => 'postSlug', 'match' => 'eq', 'value' => $slug,
          'count' => 1, 'skip-template' => true,
      ], true);
      if (!empty($items)) $post = $items[0];

      $post_image = trim((string) perch_blog_custom([
          'filter' => 'postSlug', 'match' => 'eq', 'value' => $slug,
          'count' => 1, 'template' => 'blog/schema-image.html',
      ], true));
  }

  $site = 'https://www.hellotechnology.co.uk';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php if ($post_html): ?>
    <?php perch_blog_post_meta($slug); ?>

    <?php if ($post): ?>
      <?php
        $url  = $site . ($post['postURL'] ?? ('/blog/' . $slug));
        $desc = trim(strip_tags($post['postExcerpt'] ?? $post['postDescHTML'] ?? ''));
        if (mb_strlen($desc) > 300) $desc = mb_substr($desc, 0, 297) . '…';

        $author = trim(($post['authorGivenName'] ?? '') . ' ' . ($post['authorFamilyName'] ?? ''));
        if ($author === '') $author = 'Jack Barber';

        $schema = [
            '@context'         => 'https://schema.org',
            '@type'            => 'BlogPosting',
            'headline'         => $post['postTitle'] ?? '',
            'description'      => $desc,
            'url'              => $url,
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
            'author'           => ['@type' => 'Person', 'name' => $author, 'url' => $site . '/about'],
            'publisher'        => [
                '@type' => 'Organization',
                'name'  => 'Hello Technology',
                'logo'  => ['@type' => 'ImageObject', 'url' => $site . '/assets/logo-primary.svg'],
            ],
        ];
        if (!empty($post['postDateTime'])) {
            $iso = date('c', strtotime($post['postDateTime']));
            $schema['datePublished'] = $iso;
            $schema['dateModified']  = $iso;
        }
        if ($post_image !== '') {
            $schema['image'] = (strpos($post_image, 'http') === 0) ? $post_image : $site . '/' . ltrim($post_image, '/');
        }
      ?>
      <script type="application/ld+json"><?php echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
    <?php endif; ?>
  <?php else: ?>
    <title>Post not found — hellotechnology</title>
  <?php endif; ?>
  <?php perch_layout('global.head'); ?>
</head>
<body>

  <?php perch_layout('global.header'); ?>

  <main>
    <?php if ($post_html): ?>
      <?php echo $post_html; ?>

      <section class="section section--sm">
        <div class="container container--text">
          <aside class="author-bio">
            <img class="author-bio__avatar" src="/admin/resources/jb3a-copy-w900.jpg" alt="Jack Barber" width="72" height="72">
            <div class="author-bio__body">
              <div class="author-bio__kicker">Written by</div>
              <h2 class="author-bio__name">Jack Barber</h2>
              <p class="author-bio__text">Founder of Hello Technology — a one-person studio in Whitby building friendly websites, practical AI and reliable IT for small businesses across North Yorkshire.</p>
              <a class="link" href="/about">More about me <i data-lucide="arrow-right"></i></a>
            </div>
          </aside>
        </div>
      </section>
    <?php else: ?>
      <section class="section">
        <div class="container container--narrow">
          <h1 class="h1 h1--sm">Post not found</h1>
          <p class="lead mt-5">Sorry, that post doesn't exist. <a class="link" href="/blog">Back to the blog <i data-lucide="arrow-right"></i></a></p>
        </div>
      </section>
    <?php endif; ?>
  </main>

  <?php perch_layout('cta'); ?>
  <?php perch_layout('global.footer'); ?>

</body>
</html>