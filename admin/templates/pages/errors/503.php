<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>We'll be right back — hellotechnology</title>
  <meta name="robots" content="noindex">
  <?php perch_layout('global.head'); ?>
</head>
<body>

  <?php perch_layout('global.header'); ?>

  <main>
    <section class="section error-page">
      <div class="container container--text tc">
        <div class="error-page__code">503</div>
        <h1 class="h1 h1--sm">We'll be right back.</h1>
        <p class="lead mt-5">The site's briefly offline for a spot of maintenance. Please check back in a few minutes — thanks for your patience.</p>

        <div class="error-page__actions">
          <a class="btn btn--primary" href="/">Try again</a>
          <a class="btn btn--outline" href="mailto:hi@hellotechnology.co.uk">Email me <i data-lucide="mail"></i></a>
        </div>

        <div class="error-page__links">
          <span class="eyebrow">Need to reach me?</span>
          <div class="error-page__linklist">
            <a class="link" href="mailto:hello@hellotechnology.co.uk"><i data-lucide="mail"></i> hello@hellotechnology.co.uk</a>
            <a class="link" href="tel:01947878108"><i data-lucide="phone"></i> 01947 878 108</a>
          </div>
        </div>
      </div>
    </section>
  </main>

  <?php perch_layout('global.footer'); ?>

</body>
</html>
