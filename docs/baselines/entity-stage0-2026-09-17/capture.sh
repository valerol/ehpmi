#!/usr/bin/env bash
set -euo pipefail

# Read-only captures of the public dev site. No WordPress or database changes.
CHROME=/Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome
OUT_DIR="$(cd "$(dirname "$0")" && pwd)"
RAW_DIR=/private/tmp/ehpmi-stage0-raw-2026-09-17
BASE=https://dev.ehpmi.org

if [[ ! -x "$CHROME" ]]; then
  echo "Google Chrome was not found at $CHROME" >&2
  exit 1
fi
if ! command -v cwebp >/dev/null 2>&1; then
  echo "cwebp is required to store compact QA images" >&2
  exit 1
fi
mkdir -p "$RAW_DIR"

capture() {
  local name="$1" path="$2" width="$3" height="$4" suffix="$5"
  local target="$OUT_DIR/$name-$suffix.webp"
  local raw="$RAW_DIR/$name-$suffix.png"
  if [[ -s "$target" ]] && sips -g pixelWidth -g pixelHeight "$target" >/dev/null 2>&1; then
    echo "$name-$suffix.webp (already captured)"
    return
  fi
  if [[ ! -s "$raw" ]]; then
    "$CHROME" --headless=new --disable-gpu --disable-background-networking \
      --disable-extensions --hide-scrollbars --no-first-run --no-default-browser-check \
      --window-size="$width,$height" --screenshot="$raw" \
      "$BASE$path" >/dev/null 2>&1 &
    local chrome_pid=$! elapsed=0 previous_size=0 stable=0 current_size
    while (( elapsed < 60 )); do
      if [[ -s "$raw" ]]; then
        current_size=$(stat -f%z "$raw")
        if [[ "$current_size" == "$previous_size" ]]; then
          (( stable += 1 ))
        else
          stable=0
        fi
        previous_size=$current_size
        if (( stable >= 3 )); then
          break
        fi
      fi
      sleep 1
      (( elapsed += 1 ))
    done
    if kill -0 "$chrome_pid" 2>/dev/null; then
      kill "$chrome_pid" 2>/dev/null || true
    fi
    wait "$chrome_pid" 2>/dev/null || true
  fi
  if [[ ! -s "$raw" ]] || ! sips -g pixelWidth -g pixelHeight "$raw" >/dev/null 2>&1; then
    echo "Capture failed: $name $suffix" >&2
    exit 1
  fi
  cwebp -quiet -q 82 "$raw" -o "$target"
  echo "$name-$suffix.webp"
}

while IFS='|' read -r name path; do
  capture "$name" "$path" 1440 7000 desktop
  capture "$name" "$path" 390 5000 mobile
done <<'ROUTES'
home|/
members|/about/members/
partners|/about/partners/
staff-directory|/about/staff/
staff-single|/about/staff/petr-sharov/
country-office|/offices/georgia/
projects|/projects/
project-single|/projects/detailed-and-rapid-environmental-assessments-of-sites-contaminated-with-obsolete-pesticides-in-tajikistan-2023-2024/
library|/library/
material-type|/library/action-plans/
structural-about|/about/
ROUTES
