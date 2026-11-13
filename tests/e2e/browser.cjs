'use strict';
/* Browser smoke against actual disposable WordPress + Piwigo (not mocks). */
const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');

const lab = process.env.WPD_LAB;
const phase = process.argv[2];
assert.ok(lab && ['baseline', 'osm', 'gutenberg'].includes(phase), 'bad E2E phase');
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
    if (phase === 'gutenberg') {
      await page.goto('http://127.0.0.1:8088/wp-login.php', { waitUntil: 'domcontentloaded' });
      await page.locator('#user_login').fill('ci_admin');
      await page.locator('#user_pass').fill('ci-only-wordpress');
      await Promise.all([
        page.waitForURL(/wp-admin/, { timeout: 25000 }),
        page.locator('#wp-submit').click()
      ]);
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
        if (slug === 'phototext') assert.ok(await page.locator('svg.wpd-photo-text-svg').count());
        const href = await page.locator('.wp-piwigo-display a[href]').first()
          .getAttribute('href').catch(() => null);
        if (slug === 'albumlink') {
          assert.ok(href && href.includes('127.0.0.1:8090'), 'not a Piwigo album URL');
          const lightbox = await page.locator('.wp-piwigo-display a[href]').first()
            .getAttribute('data-wpd-lightbox');
          assert.equal(lightbox, 'false', 'Piwigo navigation must bypass local lightbox');
        }
        observations[slug] = { images: count, href: slug === 'albumlink' ? href : undefined };
        if (['gallery', 'justified', 'collage', 'phototext'].includes(slug)) {
          await page.screenshot({ path: path.join(reportDir, phase + '-' + slug + '.png') });
        }
      }
      const piwigoPage = await page.goto(
        'http://127.0.0.1:8090/index.php?/category/' + process.env.WPD_ALBUM,
        { waitUntil: 'domcontentloaded' });
      assert.equal(piwigoPage.status(), 200);
      assert.ok((await page.locator('body').innerText()).length > 20);
      observations.piwigoAlbum = 'PASS';
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
