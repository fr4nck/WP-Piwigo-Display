'use strict';

// Exécute le vrai script de publication avec un dépôt jetable et un client gh local.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const crypto = require('node:crypto');
const cp = require('node:child_process');
const root = fs.mkdtempSync(path.join(os.tmpdir(), 'wpd-release-'));
const script = path.resolve(__dirname, '../.github/scripts/prepare-release-draft.sh');
const bash = process.platform === 'win32' ? 'C:/Program Files/Git/bin/bash.exe' : 'bash';
const git = (...args) => cp.execFileSync('git', ['-c', 'safe.directory=' + root, ...args], { cwd: root, encoding: 'utf8' }).trim();
try {
    git('init', '--quiet');
    git('config', 'user.name', 'Test Piwigo');
    git('config', 'user.email', 'test@example.invalid');
    git('commit', '--quiet', '--allow-empty', '-m', 'Fixture');
    git('tag', '-a', 'v3.0.0-rc.4', '-m', 'Candidate');
    git('tag', 'v3.0.0-rc.5');
    const zip = 'piwigo-display-3.0.0-rc.4.zip';
    fs.writeFileSync(path.join(root, zip), 'Paquet de test');
    const checksum = crypto.createHash('sha256').update('Paquet de test').digest('hex');
    fs.writeFileSync(path.join(root, 'SHA256SUMS'), checksum + '  ' + zip + '\n');
    fs.writeFileSync(path.join(root, 'PROVENANCE.txt'), 'Fixture locale\n');
    fs.mkdirSync(path.join(root, 'bin'));
    fs.writeFileSync(path.join(root, 'bin/gh'), '#!/usr/bin/env bash\nprintf "%s\\n" "$*" >> "$GH_CALLS"\nif [[ "$1 $2" == "release view" ]]; then exit "${RELEASE_EXISTS:-1}"; fi\n', { mode: 0o755 });
    const log = path.join(root, 'gh-calls.txt');
    const base = { ...process.env, VERSION: '3.0.0-rc.4', ZIP: zip, GITHUB_EVENT_NAME: 'push', GITHUB_REF_TYPE: 'tag', GITHUB_REF_NAME: 'v3.0.0-rc.4', GITHUB_SHA: git('rev-parse', 'HEAD'), GH_CALLS: log };
    function run(changes) {
        fs.writeFileSync(log, '');
        const result = cp.spawnSync(bash, ['--noprofile', '--norc', '-c', 'export PATH="$PWD/bin:$PATH"; bash "$WPD_SCRIPT"'], { cwd: root, env: { ...base, WPD_SCRIPT: script.replace(/\\/g, '/'), ...changes }, encoding: 'utf8' });
        if (result.error) throw result.error;
        return { status: result.status, calls: fs.readFileSync(log, 'utf8'), output: result.stderr };
    }
    for (const changes of [
        { GITHUB_REF_TYPE: 'branch' },
        { GITHUB_EVENT_NAME: 'workflow_dispatch' },
        { GITHUB_REF_NAME: 'v3.0.0-rc.3' },
        { GITHUB_SHA: '0'.repeat(40) },
        { VERSION: '3.0.0-rc.5', GITHUB_REF_NAME: 'v3.0.0-rc.5' }
    ]) {
        const result = run(changes);
        assert.notEqual(result.status, 0, JSON.stringify(changes));
        assert.equal(result.calls, '', 'Un événement non autorisé a appelé GitHub.');
    }
    const existing = run({ RELEASE_EXISTS: '0' });
    assert.notEqual(existing.status, 0);
    assert.ok(!existing.calls.includes('release create'), 'Une release existante a été remplacée.');
    const valid = run({});
    assert.equal(valid.status, 0, valid.output);
    assert.ok(valid.calls.includes('--verify-tag --draft'));
    assert.ok(valid.calls.includes('--prerelease'));
    assert.ok(valid.calls.includes('SHA256SUMS PROVENANCE.txt'));
    fs.appendFileSync(path.join(root, zip), ' altéré');
    const corrupt = run({});
    assert.notEqual(corrupt.status, 0);
    assert.equal(corrupt.calls, '', 'Un ZIP altéré a été envoyé à GitHub.');
    console.log('Publication : 8 scénarios exécutés sans accès réseau : PASS.');
} finally {
    assert.equal(path.dirname(path.resolve(root)), path.resolve(os.tmpdir()));
    assert.ok(path.basename(root).startsWith('wpd-release-'));
    fs.rmSync(root, { recursive: true, force: true });
}
