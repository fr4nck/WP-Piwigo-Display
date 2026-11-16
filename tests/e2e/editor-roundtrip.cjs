'use strict';
/* S-05/S-07 : saisie et sauvegarde Classic, réédition, conversion et rendu réel. */
const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const phase = process.argv[2];
const lab = process.env.WPD_LAB;
const proof = path.join(lab, 'reports', 'editor-roundtrip.json');
const input = 'La "Roche" [aux Fées] & \\ / ? # % + = $';

(async () => {
  const browser = await chromium.launch({headless:true, args:['--no-sandbox']});
  const context = await browser.newContext({viewport:{width:1440,height:1000}});
  const page = await context.newPage();
  try {
    await page.goto('http://127.0.0.1:8088/wp-login.php');
    await page.locator('#user_login').fill('ci_admin');
    await page.locator('#user_pass').fill('ci-only-wordpress');
    await Promise.all([page.waitForURL(/wp-admin/),page.locator('#wp-submit').click()]);
    if (phase === 'classic') {
      await page.goto('http://127.0.0.1:8088/wp-admin/post-new.php?post_type=page');
      await page.locator('button.wpd-open-builder').first().click();
      const dialog = page.locator('#wpd-classic-builder');
      await dialog.locator('[data-wpd="album"]').fill(process.env.WPD_ALBUM);
      await dialog.locator('[data-wpd="type"]').selectOption('photo-text');
      await dialog.locator('[data-wpd="photo_text"]').fill(input);
      const shortcode = await dialog.locator('[data-wpd-preview]').inputValue();
      assert.ok(shortcode.includes('wpd_codec="uri"') && shortcode.includes(encodeURIComponent(input)),
        'Classic preview did not refresh after textarea input');
      const changed = input + ' seconde saisie';
      await dialog.locator('[data-wpd="photo_text"]').fill(changed);
      assert.ok((await dialog.locator('[data-wpd-preview]').inputValue()).includes(encodeURIComponent(changed)),
        'Classic preview did not refresh after subsequent textarea input');
      await dialog.locator('[data-wpd="photo_text"]').fill(input);
      await page.getByRole('button',{name:'Insérer dans la page',exact:true}).click();
      await page.locator('#title').fill('CI codec roundtrip');
      await Promise.all([page.waitForNavigation(),page.locator('#save-post').click()]);
      const id = Number(await page.locator('#post_ID').inputValue());
      await page.frameLocator('#content_ifr').locator('.wpd-tinymce-shortcode').first().dblclick();
      assert.equal(await dialog.locator('[data-wpd="photo_text"]').inputValue(), input,
        'Classic saved/reopened value differs');
      await page.getByRole('button',{name:'Annuler',exact:true}).click();
      const persisted = await page.evaluate(() => window.tinymce.get('content').getContent());
      assert.ok(persisted.includes(encodeURIComponent(input)), 'persisted Classic content lost codec');
      fs.writeFileSync(proof, JSON.stringify({id,input,shortcode,classicSavedAndReopened:true},null,2));
      console.log('CLASSIC_SAVE_REOPEN_SPECIAL_CHARACTERS=PASS');
    } else {
      const record = JSON.parse(fs.readFileSync(proof,'utf8'));
      await context.grantPermissions(['clipboard-read','clipboard-write'],{origin:'http://127.0.0.1:8088'});
      await page.goto('http://127.0.0.1:8088/wp-admin/post.php?post='+record.id+'&action=edit');
      await page.waitForFunction(() => window.wp?.blocks?.getBlockType('wp-piwigo-display/gallery') &&
        window.wp?.data?.select('core/editor')?.getCurrentPostId());
      // Le parseur WordPress et le vrai transform enregistré par le plugin sont
      // appelés dans Gutenberg, puis le bloc est sauvegardé via l'éditeur réel.
      const attrs = await page.evaluate(() => {
        const raw=wp.data.select('core/editor').getEditedPostContent();
        const parsed=wp.shortcode.next('piwigo',raw);
        if (!parsed) throw new Error('saved Classic shortcode missing');
        const type=wp.blocks.getBlockType('wp-piwigo-display/gallery');
        const block=type.transforms.from.find(t=>t.type==='shortcode').transform(parsed.shortcode.attrs);
        wp.data.dispatch('core/block-editor').resetBlocks([block]);
        wp.data.dispatch('core/block-editor').selectBlock(block.clientId);
        wp.data.dispatch('core/edit-post').openGeneralSidebar('edit-post/block');
        return block.attributes;
      });
      assert.equal(attrs.photoText,input,'Classic to Gutenberg transform lost characters');
      // Dismiss the core welcome guide if it obscures the sidebar.
      if (await page.locator('.components-guide').count()) {
        await page.locator('.components-guide').getByRole('button',{name:/Close|Fermer/}).click();
      }
      await page.getByRole('button',{name:'Filtres avancés',exact:true}).click();
      await page.getByRole('button',{name:'Copier le shortcode équivalent',exact:true}).click();
      const copied=await page.evaluate(()=>navigator.clipboard.readText());
      assert.ok(copied.includes('wpd_codec="uri"'));
      const restored=await page.evaluate(code=>{
        const type=wp.blocks.getBlockType('wp-piwigo-display/gallery');
        return type.transforms.from.find(t=>t.type==='shortcode')
          .transform(wp.shortcode.next('piwigo',code).shortcode.attrs).attributes.photoText;
      },copied);
      assert.equal(restored,input,'Gutenberg copied shortcode cannot round-trip');
      const iframe=page.locator('iframe[name="editor-canvas"]');
      const canvas=(await iframe.count())?page.frameLocator('iframe[name="editor-canvas"]'):page;
      const preview=canvas.locator('.wp-piwigo-display-photo-text').first();
      await preview.waitFor({state:'visible',timeout:30000});
      const editorStyles=await preview.evaluate(el=>({position:getComputedStyle(el).position,
        overflow:getComputedStyle(el).overflow,svgDisplay:getComputedStyle(el.querySelector('svg')).display,
        css:Array.from(el.ownerDocument.styleSheets).some(s=>(s.href||'').includes('wp-piwigo-display-photo-text.css'))}));
      assert.equal(editorStyles.position,'relative');
      assert.equal(editorStyles.overflow,'hidden');
      assert.equal(editorStyles.svgDisplay,'block');
      assert.ok(editorStyles.css,'photo-text CSS absent from editor canvas');
      await preview.screenshot({path:path.join(lab,'reports','editor-photo-text.png')});
      await page.evaluate(async()=>{
        wp.data.dispatch('core/editor').editPost({status:'publish'});
        await wp.data.dispatch('core/editor').savePost();
        if (wp.data.select('core/editor').didPostSaveRequestFail() ||
          !wp.data.select('core/editor').didPostSaveRequestSucceed()) throw new Error('block save failed');
      });
      const frontend=await context.newPage();
      await frontend.goto('http://127.0.0.1:8088/?page_id='+record.id);
      assert.equal(await frontend.locator('.wpd-photo-text-semantic').innerText(),input);
      const frontStyles=await frontend.locator('.wp-piwigo-display-photo-text').evaluate(el=>({
        position:getComputedStyle(el).position,overflow:getComputedStyle(el).overflow,
        svgDisplay:getComputedStyle(el.querySelector('svg')).display}));
      assert.deepEqual(frontStyles,{position:editorStyles.position,overflow:editorStyles.overflow,svgDisplay:editorStyles.svgDisplay});
      await frontend.locator('.wp-piwigo-display-photo-text').screenshot({path:path.join(lab,'reports','frontend-photo-text-roundtrip.png')});
      await page.evaluate(async code=>{
        wp.data.dispatch('core/block-editor').resetBlocks([wp.blocks.createBlock('core/shortcode',{text:code})]);
        await wp.data.dispatch('core/editor').savePost();
        if (wp.data.select('core/editor').didPostSaveRequestFail() ||
          !wp.data.select('core/editor').didPostSaveRequestSucceed()) throw new Error('shortcode save failed');
      },copied);
      await frontend.reload();
      assert.equal(await frontend.locator('.wpd-photo-text-semantic').innerText(),input);
      fs.writeFileSync(proof,JSON.stringify({...record,gutenbergSaved:true,copiedShortcode:copied,
        reverseShortcodeRendered:true,editorIframe:!!(await iframe.count()),editorStyles,frontStyles},null,2));
      console.log('CLASSIC_GUTENBERG_SHORTCODE_FULL_ROUNDTRIP=PASS');
      console.log('EDITOR_FRONTEND_COMPUTED_STYLES=PASS iframe='+!!(await iframe.count()));
    }
  } finally { await browser.close(); }
})().catch(error=>{console.error(error.stack);process.exitCode=1;});
