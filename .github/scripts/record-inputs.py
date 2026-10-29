"""Bind test evidence to the final source, including unstaged local changes."""
import hashlib
import json
from pathlib import Path
import subprocess
import sys

root = Path(__file__).resolve().parents[2]
runtime = root / '.cache/verbb-tests'
runtime.mkdir(parents=True, exist_ok=True)
if (root / '.git').exists():
    names = subprocess.check_output(['git', '-C', str(root), 'ls-files', '--cached', '--others', '--exclude-standard', '-z']).decode().split('\0')
else:
    # A disposable snapshot carries its original inventory without a Git checkout.
    names = json.loads((root / '.ci-source-files.json').read_text())
files = {name: hashlib.sha256((root / name).read_bytes()).hexdigest() for name in sorted(set(names)) if name and (root / name).is_file()}
path = runtime / 'tested-inputs.json'
if sys.argv[1] == 'before':
    path.write_text(json.dumps({'sha256': files}, indent=2) + '\n')
elif sys.argv[1] == 'after':
    previous = json.loads(path.read_text())['sha256']
    changed = sorted(name for name in files.keys() | previous.keys() if files.get(name) != previous.get(name))
    if changed:
        raise SystemExit('Source changed during test execution: ' + ', '.join(changed))
    print('Tested source remained unchanged throughout the suite.')
else:
    raise SystemExit('Choose before or after.')
