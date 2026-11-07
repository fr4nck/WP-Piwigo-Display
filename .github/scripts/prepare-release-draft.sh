#!/usr/bin/env bash
# Un tag neuf et annoté peut créer un brouillon ; la publication reste manuelle.
set -euo pipefail
: "${VERSION:?Version absente}" "${ZIP:?Paquet absent}"
[[ "$GITHUB_EVENT_NAME" == push && "$GITHUB_REF_TYPE" == tag ]]
[[ "$GITHUB_REF_NAME" == "v$VERSION" ]]
[[ "$(git cat-file -t "$GITHUB_REF_NAME")" == tag ]]
[[ "$(git rev-list -n 1 "$GITHUB_REF_NAME")" == "$GITHUB_SHA" ]]
sha256sum --check SHA256SUMS
if gh release view "$GITHUB_REF_NAME" >/dev/null 2>&1; then
  echo 'Cette release existe déjà ; remplacement interdit.' >&2
  exit 1
fi
prerelease_args=()
if [[ "$VERSION" == *-* ]]; then prerelease_args+=(--prerelease); fi
gh release create "$GITHUB_REF_NAME" "$ZIP" SHA256SUMS PROVENANCE.txt \
  --verify-tag --draft --title "Piwigo Display $VERSION" \
  --notes-file PROVENANCE.txt "${prerelease_args[@]}"
