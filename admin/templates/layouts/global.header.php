<?php /* Site header / primary navigation.
   The menu is built from your Perch page tree (2 levels, so pages with
   sub-pages become a desktop dropdown). Pass ['cta_label' => '…',
   'cta_url' => '…'] to override the call-to-action button.

   The mobile pull-out lives OUTSIDE <header> on purpose: .site-header uses
   backdrop-filter, which would otherwise trap position:fixed children inside
   the header box. The nav is rendered twice (Perch caches it, so it's cheap). */

  $cta_url   = perch_layout_var('cta_url', true)   ?: '/contact';
  $cta_label = perch_layout_var('cta_label', true) ?: 'Get in touch';

  $nav_opts = [
    'levels'          => 2,
    'template'        => 'nav-link.html',
    'hide-extensions' => true,
  ];
?>
<header class="site-header">
  <nav class="nav container">
    <a class="nav__logo" href="/"><img src="/assets/logo-primary.svg" alt="hellotechnology"></a>

    <div class="nav__right">
      <div class="nav__menu">
        <?php perch_pages_navigation($nav_opts); ?>
      </div>
      <a class="btn btn--primary btn--sm" href="<?php echo $cta_url; ?>"><?php echo $cta_label; ?></a>
    </div>

    <button class="nav__toggle" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="mobile-nav">
      <span class="nav__toggle-box"><span class="nav__toggle-inner"></span></span>
    </button>
  </nav>
</header>

<!-- Mobile pull-out menu -->
<nav class="mobile-nav" id="mobile-nav" aria-label="Menu" aria-hidden="true">
  <div class="mobile-nav__panel">
    <?php perch_pages_navigation($nav_opts); ?>
    <a class="btn btn--primary mobile-nav__cta" href="<?php echo $cta_url; ?>"><?php echo $cta_label; ?></a>
  </div>
</nav>
<div class="mobile-nav__backdrop"></div>
