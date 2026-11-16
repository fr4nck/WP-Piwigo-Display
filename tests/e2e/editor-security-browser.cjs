'use strict';
// Vrais composants React/Gutenberg et vraies réponses WordPress/Piwigo.
// Les délais réseau réordonnent des réponses réelles, sans modifier leur contenu.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {execFileSync} = require('node:child_process');
const {chromium, request} = require('playwright');
const lab = process.env.WPD_LAB;
assert.equal(process.env.WPD_EDITOR_SECURITY_LAB, '1');
assert.ok(lab && lab.startsWith(process.env.RUNNER_TEMP + '/'));
const fixture = JSON.parse(fs.readFileSync(path.join(lab, 'fixture.json'), 'utf8'));
const pages = JSON.parse(fs.readFileSync(path.join(lab, 'pages-security.json'), 'utf8'));
const advice = 'La session du sélecteur d’albums a expiré. Sauvegardez votre travail, puis rechargez la page.';
const wpqa = code => execFileSync('php', [
  path.join(lab, 'wp-cli.phar'), '--path=' + path.join(lab, 'wordpress'),
  '--allow-root', 'eval', code
], {encoding:'utf8'});
const setService = enabled => wpqa(
  '$o=get_option(WPD_Service_Account::OPTION_NAME);$o["enabled"]="' +
  (enabled ? 'true' : 'false') + '";update_option(WPD_Service_Account::OPTION_NAME,$o,false);'
);
async function login(page, user, password) {
  await page.goto('http://127.0.0.1:8088/wp-login.php');
  await page.locator('#user_login').fill(user);
  await page.locator('#user_pass').fill(password);
  await Promise.all([page.waitForURL(/wp-admin/), page.locator('#wp-submit').click()]);
}
async function editor(page) {
  await page.goto('http://127.0.0.1:8088/wp-admin/post-new.php?post_type=page');
  await page.waitForFunction(() => window.wp?.blocks?.getBlockType('wp-piwigo-display/gallery'));
  if (await page.locator('.components-guide').count()) {
    await page.locator('.components-guide').getByRole('button',{name:/Close|Fermer/}).click();
  }
}
async function mountInspectors(page) {
  // Le sélecteur de production est extrait du filtre enregistré, sans en
  // réécrire le code. Deux instances utilisent les vrais hooks React, les vrais
  // composants WordPress et les attributs/historique du document Gutenberg.
  // Ce montage évite la règle native d’un seul inspecteur actif à la fois.
  await page.evaluate(() => {
    const {createElement:el, createRoot, Children} = wp.element;
    const filter = wp.hooks.filters['editor.BlockEdit'].handlers.find(
      handler=>handler.namespace==='wp-piwigo-display/gutenberg-parity'
    );
    if (!filter) throw Error('Filtre Gutenberg de production absent');
    const controls = filter.callback(()=>null)({
      name:'wp-piwigo-display/gallery',attributes:{},setAttributes(){}
    });
    function findPicker(node) {
      if (!node || typeof node !== 'object') return null;
      if (typeof node.type === 'function' && node.type.name === 'AlbumPicker') return node.type;
      for (const child of Children.toArray(node.props?.children)) {
        const found = findPicker(child);
        if (found) return found;
      }
      return null;
    }
    const AlbumPicker = findPicker(controls);
    if (!AlbumPicker) throw Error('Composant AlbumPicker de production absent');
    const node = document.createElement('div');
    node.id = 'wpd-ci-inspectors';
    Object.assign(node.style, {
      position:'fixed',inset:'50px 0 0 0',zIndex:100000,background:'#fff',
      overflow:'auto',display:'grid',gridTemplateColumns:'1fr 1fr',gap:'20px',padding:'20px'
    });
    document.body.append(node);
    function InspectorPair() {
      const blocks = wp.data.useSelect(
        select => select('core/block-editor').getBlocks()
          .filter(block => block.name === 'wp-piwigo-display/gallery'), []
      );
      return blocks.map((block, index) => el('section',{
        id:'wpd-ci-' + index,key:block.clientId,'aria-label':'Bloc CI ' + index
      }, el(AlbumPicker,{
        value:block.attributes.albumId,
        onChange: value=>wp.data.dispatch('core/block-editor')
          .updateBlockAttributes(block.clientId,{albumId:value})
      })));
    }
    window.wpdCiRoot = createRoot(node);
    window.wpdCiRoot.render(el(InspectorPair));
  });
  await page.locator('#wpd-ci-0').getByRole('button',{name:'Choisir dans Piwigo',exact:true}).waitFor();
  await page.locator('#wpd-ci-1').getByRole('button',{name:'Choisir dans Piwigo',exact:true}).waitFor();
}
const blockValues = page => page.evaluate(() => wp.data.select('core/block-editor')
  .getBlocks().filter(b=>b.name==='wp-piwigo-display/gallery').map(b=>b.attributes.albumId));
async function main() {
  const browser = await chromium.launch({headless:true,args:['--no-sandbox']});
  const context = await browser.newContext();
  const page = await context.newPage();
  page.on('pageerror',error=>console.error('BROWSER_PAGE_ERROR: ' + error.stack));
  page.on('dialog',dialog=>dialog.accept());
  const anonymous = await browser.newContext();
  const guest = await anonymous.newPage();
  const pwg = await request.newContext({baseURL:'http://127.0.0.1:8090'});
  const api = async (method, extra={}) => {
    const response = await pwg.post('/ws.php?format=json',{form:{method,...extra}});
    assert.equal(response.status(),200);
    const json = await response.json();
    assert.equal(json.stat,'ok',method + ': ' + JSON.stringify(json));
    return json.result;
  };
  const createVisibleAlbum = async name => {
    const album = await api('pwg.categories.add',{name,status:'public'});
    // Piwigo masque les albums vides au lecteur normal : la fixture doit
    // contenir une vraie image, comme un nouvel album utilisable.
    await api('pwg.images.setInfo',{
      image_id:fixture.publicImage,categories:String(album.id),multiple_value_mode:'append'
    });
    return album;
  };
  try {
    // Vraie lecture anonyme WordPress avec service actif, contrôle positif privé.
    for (const kind of ['ambiguous','public','private']) {
      const response = await guest.goto('http://127.0.0.1:8088/?page_id=' + pages[kind]);
      assert.equal(response.status(),200);
      const gallery = guest.locator('.wp-piwigo-display');
      if (kind === 'ambiguous') {
        assert.equal(await guest.locator('.wp-piwigo-display-error').count(),1);
        assert.equal(await gallery.locator('img').count(),0);
        assert.ok(!(await response.text()).includes('CI_private_SENTINEL'));
      } else {
        await gallery.locator('img').first().waitFor();
        const html = await gallery.first().innerHTML();
        assert.ok(html.includes('CI_' + kind + '_SENTINEL'));
        assert.ok(!html.includes('CI_' + (kind === 'public' ? 'private' : 'public') + '_SENTINEL'));
      }
    }
    console.log('REAL_ANONYMOUS_WORDPRESS_HOMONYMS=PASS');

    await api('pwg.session.login',{username:'ci_admin',password:'ci-only-piwigo'});
    await login(page,'ci_admin','ci-only-wordpress');
    await editor(page);
    await page.evaluate(id => {
      wp.data.dispatch('core/block-editor').resetBlocks([
        wp.blocks.createBlock('wp-piwigo-display/gallery',{albumId:String(id)}),
        wp.blocks.createBlock('wp-piwigo-display/gallery',{albumId:String(id)})
      ]);
    }, fixture.public);
    const canvas = await page.locator('iframe[name="editor-canvas"]').count()
      ? page.frameLocator('iframe[name="editor-canvas"]') : page;
    await canvas.locator('[data-type="wp-piwigo-display/gallery"]').nth(1).waitFor();
    await mountInspectors(page);
    const a = page.locator('#wpd-ci-0'), b = page.locator('#wpd-ci-1');
    let albumRequests = 0, gateNext = false;
    const held = [];
    await page.route('**/wp-admin/admin-ajax.php',async route => {
      if (!/(?:^|[\r\n&])(?:name="action"\r?\n\r?\n)?wpd_get_albums|action=wpd_get_albums/.test(route.request().postData() || '')) {
        // FormData sends Content-Disposition followed by a blank line.
        if (!(route.request().postData() || '').includes('wpd_get_albums')) return route.continue();
      }
      albumRequests++;
      const hold = gateNext;
      gateNext = false;
      const response = await route.fetch();
      const body = await response.json();
      console.log('REAL_PICKER_RESPONSE ' + JSON.stringify({
        status:response.status(),albums:body.data?.albums?.map(a=>({id:a.id,name:a.name}))
      }));
      if (hold) {
        await new Promise(resolve => held.push({route,response,resolve}));
      } else {
        await route.fulfill({response});
      }
    });
    const waitHeld = async () => {
      await page.waitForFunction(() => true);
      for (let i=0;i<200 && !held.length;i++) await new Promise(resolve=>setTimeout(resolve,25));
      assert.ok(held.length,'Une vraie réponse HTTP attendue manque.');
      return held.shift();
    };
    const release = async pending => {
      await pending.route.fulfill({response:pending.response});
      pending.resolve();
    };
    const open = region => region.getByRole('button',{name:'Choisir dans Piwigo',exact:true}).click();
    const refresh = region => region.getByRole('button',{name:'Actualiser la liste des albums',exact:true});
    const row = (region,name) => region.getByRole('treeitem').filter({hasText:name});

    gateNext = true;
    await open(a);
    const initial = await waitHeld();
    await open(b);
    assert.equal(albumRequests,1,'Les deux composants doivent partager la requête initiale.');
    await release(initial);
    await a.getByRole('tree').waitFor();
    await b.getByRole('tree').waitFor();

    const first = await createVisibleAlbum('CI Refresh 1');
    await refresh(a).click();
    await row(a,'CI Refresh 1').waitFor();
    await row(b,'CI Refresh 1').waitFor();
    assert.equal(albumRequests,2,'Un seul rafraîchissement réseau pour les deux blocs.');
    assert.deepEqual(await blockValues(page),[String(fixture.public),String(fixture.public)]);

    // La vieille réponse contient réellement un instantané antérieur de Piwigo.
    await createVisibleAlbum('CI Refresh Old');
    gateNext = true;
    await refresh(a).click();
    const old = await waitHeld();
    await createVisibleAlbum('CI Refresh New');
    await refresh(b).click();
    await row(a,'CI Refresh New').waitFor();
    await row(b,'CI Refresh New').waitFor();
    await release(old);
    await row(a,'CI Refresh New').waitFor();
    assert.equal(await row(b,'CI Refresh New').count(),1);

    gateNext = true;
    await refresh(a).click();
    const interrupted = await waitHeld();
    await a.getByRole('button',{name:'Fermer la liste',exact:true}).click();
    await release(interrupted);
    assert.equal(await a.getByRole('tree').count(),0);
    assert.equal(await a.locator('.components-spinner').count(),0);
    await open(a);
    await a.getByRole('tree').waitFor();

    // Valider un bloc, puis utiliser la vraie histoire Gutenberg.
    await row(a,'CI Refresh 1').getByRole('button',{name:'Choisir cet album : CI Refresh 1',exact:true}).click();
    await page.waitForFunction(id => wp.data.select('core/block-editor').getBlocks()[0].attributes.albumId === String(id), first.id);
    assert.deepEqual(await blockValues(page),[String(first.id),String(fixture.public)]);
    await page.evaluate(() => wp.data.dispatch('core/editor').undo());
    await page.waitForFunction(id => wp.data.select('core/block-editor').getBlocks()[0].attributes.albumId === String(id), fixture.public);
    await page.waitForFunction(selected => Array.from(document.querySelectorAll('#wpd-ci-0 [role="treeitem"]'))
      .some(row=>row.textContent.includes('CI Refresh 1') && row.getAttribute('aria-selected')===selected),'false');
    assert.equal(await row(a,'CI Refresh 1').getAttribute('aria-selected'),'false');
    await page.evaluate(() => wp.data.dispatch('core/editor').redo());
    await page.waitForFunction(id => wp.data.select('core/block-editor').getBlocks()[0].attributes.albumId === String(id), first.id);
    await page.waitForFunction(selected => Array.from(document.querySelectorAll('#wpd-ci-0 [role="treeitem"]'))
      .some(row=>row.textContent.includes('CI Refresh 1') && row.getAttribute('aria-selected')===selected),'true');
    assert.equal(await row(a,'CI Refresh 1').getAttribute('aria-selected'),'true');

    const postId = await page.evaluate(async () => {
      wp.data.dispatch('core/editor').editPost({title:'CI two Gutenberg blocks',status:'draft'});
      await wp.data.dispatch('core/editor').savePost();
      if (!wp.data.select('core/editor').didPostSaveRequestSucceed()) throw Error('Échec sauvegarde Gutenberg');
      window.wpdCiRoot.unmount();
      document.getElementById('wpd-ci-inspectors').remove();
      return wp.data.select('core/editor').getCurrentPostId();
    });
    await page.goto('http://127.0.0.1:8088/wp-admin/post.php?post=' + postId + '&action=edit');
    await page.waitForFunction(() => wp.data.select('core/block-editor').getBlocks()
      .filter(b=>b.name==='wp-piwigo-display/gallery').length === 2);
    assert.deepEqual(await blockValues(page),[String(first.id),String(fixture.public)]);
    console.log('REAL_GUTENBERG_MULTIBLOCK_DOM=PASS shared-request refresh stale close undo redo save reopen');

    // Les deux traitements serveur, avec un vrai nonce de session expiré.
    for (const service of [true,false]) {
      setService(service);
      await editor(page);
      const nonce = await page.evaluate(() => WPDAlbumPickerConfig.nonce);
      const expiredResponse = await context.request.post('http://127.0.0.1:8088/wp-admin/admin-ajax.php',{
        form:{action:'wpd_ci_expired_nonce',nonce}
      });
      assert.equal(expiredResponse.status(),200);
      const expired = (await expiredResponse.json()).data.nonce;
      const rejected = await context.request.post('http://127.0.0.1:8088/wp-admin/admin-ajax.php',{
        form:{action:'wpd_get_albums',nonce:expired}
      });
      assert.equal(rejected.status(),403);
      assert.equal(rejected.headers()['x-wpd-ci-piwigo-requests'],'0');
      assert.deepEqual(await rejected.json(),{success:false,data:{code:'wpd_nonce_expired',message:advice}});
      await page.evaluate(({id,expired})=>{
        WPDAlbumPickerConfig.nonce=expired;
        wp.data.dispatch('core/block-editor').resetBlocks([
          wp.blocks.createBlock('wp-piwigo-display/gallery',{albumId:String(id)}),
          wp.blocks.createBlock('wp-piwigo-display/gallery',{albumId:String(id)})
        ]);
      },{id:fixture.public,expired});
      await mountInspectors(page);
      await open(page.locator('#wpd-ci-0'));
      await page.locator('#wpd-ci-0').getByText(advice,{exact:true}).waitFor();
      assert.equal(await page.locator('#wpd-ci-0 .components-spinner').count(),0);
      assert.deepEqual(await blockValues(page),[String(fixture.public),String(fixture.public)]);

      // Vrai jQuery et vrais événements DOM, sur le même endpoint expiré.
      await page.evaluate(id=>{
        const root=document.createElement('div');
        root.id='wpd-ci-jquery';
        root.innerHTML='<input type="text"><button class="wpd-browse-albums">Parcourir CI</button><div class="wpd-album-picker" hidden></div>';
        Object.assign(root.style,{position:'fixed',bottom:'0',left:'0',zIndex:200000,background:'#fff'});
        root.querySelector('input').value=String(id);
        document.body.append(root);
        WPDAlbumPicker.reload();
        WPDAlbumPicker.attach(root,root.querySelector('input'));
      },fixture.public);
      await page.locator('#wpd-ci-jquery .wpd-browse-albums').click();
      await page.locator('#wpd-ci-jquery').getByText(advice,{exact:true}).waitFor();
      assert.equal(await page.locator('#wpd-ci-jquery input').inputValue(),String(fixture.public));
      await page.evaluate(nonce=>{WPDAlbumPickerConfig.nonce=nonce;},nonce);
      await page.locator('#wpd-ci-jquery .wpd-album-refresh').click();
      await page.locator('#wpd-ci-jquery [role="tree"]').waitFor();
      console.log('REAL_EXPIRED_NONCE=PASS service=' + service + ' HTTP403 zero-upstream Gutenberg jQuery recovery');
      await page.evaluate(()=>{
        window.wpdCiRoot.unmount();
        document.getElementById('wpd-ci-inspectors').remove();
        document.getElementById('wpd-ci-jquery').remove();
      });
    }

    // Un contributeur ne reçoit aucun album privé, même avec service actif.
    setService(true);
    const contributor = await browser.newContext();
    const contributorPage = await contributor.newPage();
    await login(contributorPage,'ci_contributor','ci-only-contributor');
    await contributorPage.goto('http://127.0.0.1:8088/wp-admin/post-new.php');
    await contributorPage.waitForFunction(()=>!!window.WPDAlbumPickerConfig);
    const permitted = await contributor.request.post('http://127.0.0.1:8088/wp-admin/admin-ajax.php',{
      form:{action:'wpd_get_albums',nonce:await contributorPage.evaluate(()=>WPDAlbumPickerConfig.nonce)}
    });
    assert.equal(permitted.status(),200);
    const albums = (await permitted.json()).data.albums;
    assert.ok(albums.some(album=>album.id===fixture.public));
    assert.ok(!albums.some(album=>album.id===fixture.private));
    await contributor.close();
    console.log('REAL_CONTRIBUTOR_PICKER_PUBLIC_ONLY=PASS');
  } catch (error) {
    console.error('FOCUSED_DOM_STATE ' + JSON.stringify(
      await page.locator('#wpd-ci-inspectors').allTextContents().catch(()=>[])
    ));
    throw error;
  } finally {
    await pwg.dispose();
    await anonymous.close();
    await context.close();
    await browser.close();
  }
}
main().catch(error=>{console.error(error.stack);process.exitCode=1;});
