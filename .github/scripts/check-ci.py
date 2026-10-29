"""Reject accidental drift in the versioned shared test setup."""
import hashlib
import json
from pathlib import Path

root = Path(__file__).resolve().parents[2]
manifest = json.loads((root / '.github/ci-manifest.json').read_text())
for name, expected in manifest['sha256'].items():
    path = root / name
    if not path.is_file() or hashlib.sha256(path.read_bytes()).hexdigest() != expected:
        raise SystemExit(f'Shared CI file differs from version {manifest["version"]}: {name}')
print(f'Shared CI version {manifest["version"]}: all files match.')
