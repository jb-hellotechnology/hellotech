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

  // Image performance: first image (hero, likely LCP) is eager + high priority;
  // all later images are lazy-loaded. Add intrinsic width/height where missing
  // to stop layout shift.
  if ($post_html) {
      $img_i = 0;
      $post_html = preg_replace_callback('/<img\b[^>]*>/i', function ($m) use (&$img_i) {
          $tag = $m[0];
          $img_i++;
          // Skip the hero/banner (first image): fixed width/height breaks its aspect ratio
          if ($img_i > 1 && !preg_match('/\swidth=/i', $tag) && preg_match('/\ssrc="([^"]+)"/i', $tag, $s)) {
              $file = $_SERVER['DOCUMENT_ROOT'] . parse_url($s[1], PHP_URL_PATH);
              if (is_file($file) && ($size = @getimagesize($file))) {
                  $tag = preg_replace('/<img\b/i', '<img width="' . $size[0] . '" height="' . $size[1] . '"', $tag, 1);
              }
          }
          $extra = ($img_i === 1) ? ' fetchpriority="high" decoding="async"' : ' loading="lazy" decoding="async"';
          if (stripos($tag, 'loading=') === false) {
              $tag = preg_replace('/<img\b/i', '<img' . $extra, $tag, 1);
          }
          return $tag;
      }, $post_html);
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

        // Meta description (previously missing) + canonical URL
        $meta_desc = trim(preg_replace('/\s+/', ' ', strip_tags($post['postDescHTML'] ?? '')));
        $meta_desc = trim(strip_tags($post['postExcerpt'] ?? '')) ?: $meta_desc;
        if (mb_strlen($meta_desc) > 160) $meta_desc = rtrim(mb_substr($meta_desc, 0, 157)) . '…';
      ?>
      <?php if ($meta_desc !== ''): ?><meta name="description" content="<?php echo htmlspecialchars($meta_desc, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
      <link rel="canonical" href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>">
      <?php
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