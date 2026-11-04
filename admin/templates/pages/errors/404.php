<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Page not found — hellotechnology</title>
  <meta name="robots" content="noindex">
  <?php perch_layout('global.head'); ?>
</head>
<body>

  <?php perch_layout('global.header'); ?>

  <main>
    <section class="section error-page">
      <div class="container container--text tc">
        <div class="error-page__code">404</div>
        <h1 class="h1 h1--sm">We can't find that page.</h1>
        <p class="lead mt-5">The page you're after might have moved, or the link could be a little out of date. Let's get you back on track.</p>

        <div class="error-page__actions">
          <a class="btn btn--primary" href="/">Back to home</a>
          <a class="btn btn--outline" href="/contact">Get in touch <i data-lucide="arrow-right"></i></a>
        </div>

        <div class="error-page__links">
          <span class="eyebrow">Popular pages</span>
          <div class="error-page__linklist">
            <a class="link" href="/services">Services <i data-lucide="arrow-right"></i></a>
            <a class="link" href="/blog">Blog <i data-lucide="arrow-right"></i></a>
            <a class="link" href="/contact">Contact <i data-lucide="arrow-right"></i></a>
          </div>
        </div>
      </div>
    </section>
  </main>

  <?php perch_layout('global.footer'); ?>

</body>
</html>
