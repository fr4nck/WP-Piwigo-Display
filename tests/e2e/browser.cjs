'use strict';
/* Browser smoke against actual disposable WordPress + Piwigo (not mocks). */
const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');

const lab = process.env.WPD_LAB;
const phase = process.argv[2];
assert.ok(lab && ['baseline', 'osm', 'gutenberg', 'classic'].includes(phase), 'bad E2E phase');
const reportDir = path.join(lab, 'reports');
fs.mkdirSync(reportDir, { recursive: true });
const pages = Object.fromEntries(
  fs.readFileSync(path.join(lab, 'pages.txt'), 'utf8').trim().split('\n')
    .map(row => row.split('='))
);
const expected = [
  ['gallery', '.wp-piwigo-display-gallery'],
  ['slider', '.wp-piwigo-display-slider'],
  ['justified', '.wp-piwigo-display-justified'],
  ['collage', '.wp-piwigo-display-collage'],
  ['phototext', '.wp-piwigo-display-photo-text'],
  ['albumlink', '.wp-piwigo-display-gallery'],
  ['photolink', '.wp-piwigo-display-gallery'],
  ['block', '.wp-piwigo-display-gallery']
];

async function run() {
  const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  const observations = {};
  const badAssets = [];
  page.on('response', response => {
    if (response.status() >= 400 && /127\.0\.0\.1:(8088|8090)/.test(response.url()) &&
        /\.(js|css|woff2)(\?|$)/.test(response.url())) {
      badAssets.push(response.status() + ' ' + response.url());
    }
  });
  try {
    if (phase === 'gutenberg' || phase === 'classic') {
      await page.goto('http://127.0.0.1:8088/wp-login.php', { waitUntil: 'domcontentloaded' });
      await page.locator('#user_login').fill('ci_admin');
      await page.locator('#user_pass').fill('ci-only-wordpress');
      await Promise.all([
        page.waitForURL(/wp-admin/, { timeout: 25000 }),
        page.locator('#wp-submit').click()
      ]);
      if (phase === 'classic') {
        await page.goto('http://127.0.0.1:8088/wp-admin/post-new.php?post_type=page', {
          waitUntil: 'domcontentloaded'
        });
        // This is the official Classic Editor screen, loaded after Gutenberg checks.
        const open = page.locator('button.wpd-open-builder').first();
        await open.waitFor({ state: 'visible', timeout: 30000 });
        await open.click();
        const dialog = page.locator('#wpd-classic-builder');
        await dialog.waitFor({ state: 'visible', timeout: 15000 });
        await dialog.locator('[data-wpd="album"]').fill(process.env.WPD_ALBUM);
        await dialog.locator('[data-wpd="type"]').selectOption('justified');
        const preview = await dialog.locator('[data-wpd-preview]').inputValue();
        assert.ok(preview.includes('album="' + process.env.WPD_ALBUM + '"'),
          'Classic builder lost the selected Piwigo album');
        assert.ok(preview.includes('type="justified"'),
          'Classic builder failed to generate the Justified shortcode');
        assert.ok(await dialog.locator('.wpd-justified-options').first().isVisible(),
          'Classic Editor Justified controls are not visible');
        observations.classicGeneratedShortcode = preview;
        console.log('REAL_CLASSIC_EDITOR_BUILDER=PASS');
      } else {
      await page.goto('http://127.0.0.1:8088/wp-admin/post-new.php?post_type=page', {
        waitUntil: 'domcontentloaded'
      });
      await page.waitForFunction(
        () => !!(window.wp && window.wp.blocks &&
          window.wp.blocks.getBlockType('wp-piwigo-display/gallery')),
        null, { timeout: 30000 }
      );
      const attributes = await page.evaluate(() =>
        Object.keys(window.wp.blocks.getBlockType('wp-piwigo-display/gallery').attributes));
      assert.ok(attributes.includes('albumId'));
      assert.ok(attributes.includes('clickAction'));
      assert.ok(attributes.includes('photoText'));
      observations.registeredAttributes = attributes.length;
      await page.goto('http://127.0.0.1:8088/wp-admin/admin.php?page=wp-piwigo-display-compose',
        { waitUntil: 'domcontentloaded' });
      assert.match((await page.locator('body').innerText()), /Composer|galerie|diaporama/i);
      observations.adminComposer = 'PASS';
      console.log('REAL_GUTENBERG_AND_COMPOSER=PASS');
      }
    } else {
      for (const [slug, selector] of expected) {
        const id = pages[slug];
        assert.ok(/^\d+$/.test(id || ''), 'missing fixture for ' + slug);
        const response = await page.goto(
          'http://127.0.0.1:8088/?page_id=' + id, { waitUntil: 'networkidle' });
        assert.equal(response.status(), 200, 'WordPress page status for ' + slug);
        const errors = await page.locator('.wp-piwigo-display-error').allInnerTexts();
        assert.equal(errors.length, 0, slug + ': ' + errors.join(' / '));
        assert.ok(await page.locator(selector).count(), slug + ' has no rendered layout');
        const count = await page.locator('.wp-piwigo-display img').count();
        if (slug !== 'phototext') assert.ok(count > 0, slug + ' has no real images');
        if (slug === 'justified') {
          const cells = await page.locator('.wp-piwigo-display-justified-item')
            .evaluateAll(items => items.map(item => {
              const b = item.getBoundingClientRect();
              return { top: b.top, left: b.left, right: b.right, width: b.width };
            }));
          assert.ok(cells.length >= 6, 'justified gallery lacks six image cells');
          const widthsAndHeights = await page.locator('.wp-piwigo-display-justified-item img')
            .evaluateAll(images => images.map(img => ({
              width: Number(img.getAttribute('width')),
              height: Number(img.getAttribute('height'))
            })));
          assert.ok(widthsAndHeights.every(dim => dim.width > 0 && dim.height > 0),
            'Piwigo image metadata has missing dimensions');
          const ratios = widthsAndHeights.map(dim => Math.round(100 * dim.width / dim.height));
          assert.ok(new Set(ratios).size >= 4,
            'mixed portrait, square, panorama and landscape aspect ratios were not preserved');
          const sharedRowPairs = [];
          for (let i = 0; i < cells.length; ++i) {
            for (let j = i + 1; j < cells.length; ++j) {
              if (Math.abs(cells[i].top - cells[j].top) <= 3) {
                sharedRowPairs.push([cells[i], cells[j]]);
              }
            }
          }
          assert.ok(sharedRowPairs.length > 0,
            'justified layout put every mixed-ratio photo on a separate row');
          for (const [left, right] of sharedRowPairs) {
            assert.ok(left.right <= right.left + 1,
              'justified photo cells overlap horizontally');
          }
          observations.justifiedAspectRatios = ratios;
          observations.justifiedSharedRowPairs = sharedRowPairs.length;
          console.log('REAL_JUSTIFIED_MIXED_RATIO_ROWS=PASS');
        }
        if (slug === 'phototext') assert.ok(await page.locator('svg.wpd-photo-text-svg').count());
        const href = await page.locator('.wp-piwigo-display a[href]').first()
          .getAttribute('href').catch(() => null);
        if (slug === 'albumlink' || slug === 'photolink') {
          assert.ok(href && href.includes('127.0.0.1:8090'), 'not a Piwigo album URL');
          const lightbox = await page.locator('.wp-piwigo-display a[href]').first()
            .getAttribute('data-wpd-lightbox');
          assert.equal(lightbox, 'false', 'Piwigo navigation must bypass local lightbox');
        }
        observations[slug] = { images: count, href: ['albumlink', 'photolink'].includes(slug) ? href : undefined };
        if (['gallery', 'justified', 'collage', 'phototext'].includes(slug)) {
          await page.screenshot({ path: path.join(reportDir, phase + '-' + slug + '.png') });
        }
        if (slug === 'gallery') {
          // Existing shortcodes must still open the WordPress lightbox,
          // not silently redirect to Piwigo after the new click_action support.
          const oldLink = page.locator(
            '.wp-piwigo-display-lightbox-enabled [data-wpd-lightbox="true"]').first();
          await oldLink.click();
          await page.locator('.wp-piwigo-display-lightbox.is-open')
            .waitFor({ state: 'visible', timeout: 10000 });
          assert.ok(page.url().includes('127.0.0.1:8088'),
            'historical lightbox redirected to Piwigo');
          await page.locator('.wp-piwigo-display-lightbox-close').click();
          console.log('REAL_LEGACY_LIGHTBOX_CLICK=PASS');
        }
        if (slug === 'albumlink' || slug === 'photolink') {
          // Exercise the real click; checking href alone would miss an
          // obsolete lightbox handler accidentally intercepting navigation.
          await Promise.all([
            page.waitForURL(url => url.hostname === '127.0.0.1' && url.port === '8090',
              { timeout: 20000 }),
            page.locator('.wp-piwigo-display a[href]').first().click()
          ]);
          const clickResponse = await page.request.get(page.url());
          assert.equal(clickResponse.status(), 200,
            'clicked Piwigo photo/album destination is not HTTP 200');
          assert.ok((await page.locator('body').innerText()).length > 20,
            'clicked Piwigo page did not render');
          console.log('REAL_PIWIGO_' + slug.toUpperCase() + '_CLICK=PASS');
        }
      }
      // Capture four genuinely narrow mobile layouts, without mutating the
      // desktop assertion context. These are inspected separately from PASS.
      await page.setViewportSize({ width: 390, height: 844 });
      for (const slug of ['gallery', 'justified', 'collage', 'phototext']) {
        const id = pages[slug];
        const response = await page.goto(
          'http://127.0.0.1:8088/?page_id=' + id, { waitUntil: 'networkidle' });
        assert.equal(response.status(), 200, slug + ' mobile page status');
        assert.ok(await page.locator('.wp-piwigo-display').first().isVisible(),
          slug + ' mobile gallery is hidden');
        const bounds = await page.locator('.wp-piwigo-display').first()
          .evaluate(element => {
            const rect = element.getBoundingClientRect();
            return { left: rect.left, right: rect.right };
          });
        assert.ok(bounds.left >= -4 && bounds.right <= 394,
          slug + ' gallery exceeds the mobile viewport');
        await page.screenshot({
          path: path.join(reportDir, phase + '-mobile-' + slug + '.png')
        });
      }
      console.log('REAL_MOBILE_LAYOUTS=PASS');
      await page.setViewportSize({ width: 1280, height: 900 });
      const piwigoPage = await page.goto(
        'http://127.0.0.1:8090/index.php?/category/' + process.env.WPD_ALBUM,
        { waitUntil: 'domcontentloaded' });
      assert.equal(piwigoPage.status(), 200);
      assert.ok((await page.locator('body').innerText()).length > 20);
      observations.piwigoAlbum = 'PASS';
      if (phase === 'osm') {
        // The OSM extension must actually render its map on the geotagged
        // Piwigo category. HTTP 200 alone would be a false-positive.
        const map = page.locator('#map');
        assert.ok(await map.count(), 'real Piwigo OpenStreetMap container is absent');
        assert.ok(await map.isVisible(), 'real Piwigo OpenStreetMap container is hidden');
        assert.ok(await page.locator('script[src*="leaflet"]').count(),
          'OpenStreetMap did not enqueue its real Leaflet assets');
        // A visible basemap alone is not sufficient: assert that Piwigo's
        // upstream plugin created a marker from the synthetic GPS photo.
        await page.waitForFunction(() =>
          Array.isArray(window.MarkerClusterList) &&
          window.MarkerClusterList.some(marker => {
            if (typeof marker.getLatLng !== 'function') return false;
            const gps = marker.getLatLng();
            return Math.abs(gps.lat - 48.107) < 0.001 &&
              Math.abs(gps.lng + 1.679) < 0.001;
          }), null, { timeout: 15000 });
        await map.locator('.leaflet-marker-icon').first()
          .waitFor({ state: 'visible', timeout: 15000 });
        const markerCount = await map.locator('.leaflet-marker-icon:visible').count();
        assert.ok(markerCount >= 1, 'the geotagged Piwigo photo has no visible Leaflet marker');
        observations.osmMap = 'VISIBLE';
        observations.geoMarkers = markerCount;
        await map.screenshot({ path: path.join(reportDir, phase + '-piwigo-map.png') });
        console.log('REAL_PIWIGO_OSM_GPS_MARKER=PASS count=' + markerCount);
        console.log('REAL_PIWIGO_OPENSTREETMAP_VISIBLE=PASS');
      }
      console.log('REAL_PIWIGO_BROWSER_' + phase.toUpperCase() + '=PASS');
    }
    assert.deepEqual(badAssets, [], '404/500 on local JS, CSS, WOFF2 assets');
    fs.writeFileSync(path.join(reportDir, phase + '.json'),
      JSON.stringify(observations, null, 2) + '\n');
    if (phase === 'osm') {
      const baseline = JSON.parse(fs.readFileSync(path.join(reportDir, 'baseline.json'), 'utf8'));
      for (const [name] of expected) {
        assert.equal(observations[name].images, baseline[name].images,
          'OpenStreetMap changed displayed count in ' + name);
      }
      console.log('OSM_SAME_REAL_ALBUM_RENDERING=PASS');
    }
  } finally {
    await browser.close();
  }
}

run().catch(error => {
  console.error('REAL_BROWSER_E2E_FAIL: ' + (error.stack || error.message));
  process.exitCode = 1;
});
