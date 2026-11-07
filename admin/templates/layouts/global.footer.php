<?php /* Site footer + Lucide icon initialiser.
   Edit the footer copy/links once here and it updates site-wide.
   Sits just before </body> in every master page template. */ ?>
<footer class="site-footer">
  <div class="container site-footer__inner">
    <div class="site-footer__top">
      <div class="site-footer__brand">
        <img src="/assets/logo-reversed.svg" alt="hellotechnology" width="81" height="34" loading="lazy">
        <p>Friendly, expert technology for small businesses across Whitby and North Yorkshire.</p>
        <a class="btn btn--primary btn--sm" href="/contact">Get in touch</a>
      </div>
      <div class="site-footer__cols">
        <div>
          <div class="foot-col__head">Services</div>
          <div class="foot-col__links">
            <a href="/services/website-design">Websites</a>
            <a href="/services/ai-integration">AI integration</a>
            <a href="/services/it-support">IT support</a>
          </div>
        </div>
        <div>
          <div class="foot-col__head">Get in touch</div>
          <div class="foot-col__links">
            <a href="mailto:hi@hellotechnology.co.uk">hi@hellotechnology.co.uk</a>
            <a href="tel:+441947878108">01947 878 108</a>
            <a href="https://instagram.com/_hellotechnology" target="_blank" rel="noopener">Instagram</a>
            <a href="https://www.linkedin.com/in/jack-barber-uk/" target="_blank" rel="noopener">LinkedIn</a>
            <span class="accent">Whitby &bull; North Yorkshire</span>
            <!-- <div id="wcb" class="carbonbadge wcb-d"></div>
            <script src="https://unpkg.com/website-carbon-badges@1.1.3/b.min.js" defer></script> -->
          </div>
        </div>
      </div>
    </div>
    <div class="site-footer__bar">
      <span>&copy; <?php echo date('Y'); ?> Hello Technology Ltd &bull; Church House, Flowergate, Whitby</span>
      <span><a href="/privacy">Privacy policy</a> &bull; Built by a human, made in Whitby.</span>
    </div>
  </div>
</footer>

<?php
  // Structured data: one server-rendered @graph per page (Organization, Person, WebSite,
  // WebPage, BreadcrumbList + whatever the page template added). See schema-lib.php.
  require_once __DIR__ . '/schema-lib.php';
  ht_schema_render();
?>

<script src="https://cdn.usefathom.com/script.js" data-site="VPKMAXJJ" defer></script>

<script>
  function initIcons(){
    try { if (window.lucide && window.lucide.createIcons) window.lucide.createIcons(); } catch (e) {}
  }
  document.addEventListener('DOMContentLoaded', initIcons);
  window.addEventListener('load', initIcons);
</script>

<script>
  // Mobile pull-out menu
  (function () {
    var toggle   = document.querySelector('.nav__toggle');
    var panel    = document.getElementById('mobile-nav');
    var backdrop = document.querySelector('.mobile-nav__backdrop');
    if (!toggle || !panel) return;

    function setOpen(open) {
      document.body.classList.toggle('nav-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
      if (open) panel.removeAttribute('inert'); else panel.setAttribute('inert', '');
    }

    toggle.addEventListener('click', function () {
      setOpen(!document.body.classList.contains('nav-open'));
    });
    if (backdrop) backdrop.addEventListener('click', function () { setOpen(false); });

    // Close when a menu link is followed
    panel.addEventListener('click', function (e) {
      if (e.target.closest('a')) setOpen(false);
    });

    // Close on Escape, and when resizing up to desktop
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') setOpen(false);
    });
    window.addEventListener('resize', function () {
      if (window.innerWidth > 860) setOpen(false);
    });
  })();
</script>
