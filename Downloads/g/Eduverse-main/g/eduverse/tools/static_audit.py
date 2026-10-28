#!/usr/bin/env python3
"""Static cross-reference audit for EduVerse (no PHP needed). Usage: python tools/static_audit.py
Checks: autoload class/file names, Class::method references, routes -> controller methods, view() targets,
links/forms -> routes, view variables vs controller data, SQL INSERT/UPDATE columns vs schema.sql, view array keys vs known columns."""
import re, glob, os, sys
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
def rd(p): return open(p, encoding='utf-8').read()
files = {os.path.relpath(p, ROOT): rd(p) for p in glob.glob(ROOT + '/**/*.php', recursive=True)}
problems = []
def bad(m): problems.append(m)

# 1. classes and methods
classes, methods, consts = {}, {}, {}
for f, s in files.items():
    if f.startswith('views/'): continue
    for m in re.finditer(r'^(?:final |abstract )?(class|interface) (\w+)', s, re.M):
        classes[m.group(2)] = f
    for m in re.finditer(r'^(?:final |abstract )?(?:class|interface) (\w+)(.*?)^\}', s, re.M | re.S):
        body = m.group(2)
        methods[m.group(1)] = set(re.findall(r'function (\w+)\(', body))
        consts[m.group(1)] = set(re.findall(r'const (\w+)', body))
for c, f in classes.items():
    if f.startswith('src/') and os.path.basename(f) != c + '.php' and c not in ('HttpException',):
        bad(f'autoload: class {c} is in {f} (file must be {c}.php)')
    if f.startswith('src/') and os.path.dirname(f) not in ('src', 'src/Services', 'src/Controllers'):
        bad(f'autoload: {f} is outside autoload directories')
BUILTIN = set('PDO PDOException DateTime ZipArchive RuntimeException Throwable Exception PDOStatement self static parent AiProvider'.split()) | {'PDO', 'ZipArchive'}
for f, s in files.items():
    for m in re.finditer(r'\b([A-Z]\w+)::(\w+)', s):
        c, meth = m.groups()
        if c in BUILTIN or c in ('UPLOAD_ERR_OK',): continue
        if c not in classes: bad(f'{f}: references unknown class {c}::{meth}'); continue
        if meth in ('class',): continue
        if meth not in methods.get(c, set()) and meth not in consts.get(c, set()):
            bad(f'{f}: {c}::{meth} is not defined')

# 2. routes
routes = re.findall(r"'(GET|POST) (/[^']*)' => \['(\w+)', '(\w+)'\]", files['src/routes.php'])
route_set = {(m, p) for m, p, _, _ in routes}
for m, p, c, fn in routes:
    if c not in classes: bad(f'route {m} {p}: class {c} missing')
    elif fn not in methods[c]: bad(f'route {m} {p}: {c}::{fn} missing')
    elif not re.search(r'public static function ' + fn + r'\(', files[classes[c]]): bad(f'route {m} {p}: {c}::{fn} not public static')
ctrl_methods = {(c, fn) for _, _, c, fn in routes}
for c, f in classes.items():
    if f.startswith('src/Controllers/'):
        for fn in methods[c]:
            if re.search(r'public static function ' + fn + r'\(', files[f]) and (c, fn) not in ctrl_methods:
                bad(f'dead controller method (no route): {c}::{fn}')

# 3. view() targets and unused views
view_calls = []
for f, s in files.items():
    for m in re.finditer(r"\bview\('([\w/]+)'(?:, (\[.*?\]\)|\$\w+\)|\[.*?\]);)?", s, re.S):
        view_calls.append((f, m.group(1)))
used = {v for _, v in view_calls}
for f, v in view_calls:
    if not os.path.exists(f'{ROOT}/views/{v}.php'): bad(f'{f}: view {v} missing')
for p in glob.glob(ROOT + '/views/**/*.php', recursive=True):
    v = os.path.relpath(p, ROOT + '/views')[:-4].replace(os.sep, '/')
    if v != 'layout' and v not in used: bad(f'unused view: {v}')

# 4. links and forms -> routes
for f, s in files.items():
    if not f.startswith('views/'): continue
    for m in re.finditer(r'(href|action|src)="/', s):
        bad(f'{f}: raw root-relative {m.group(1)}="/..." breaks sub-folder installs; use url()')
    for m in re.finditer(r'(href|action)="<\?= e\(url\(\'(/[^\']*)\'\)\) \?>', s):
        kind, path = m.groups()
        if path.startswith('/assets/'): continue
        if ('GET' if kind == 'href' else 'POST', path) not in route_set: bad(f'{f}: {kind} url("{path}") has no matching route')
js = rd(ROOT + '/public/assets/js/app.js')
if re.search(r"fetch\('/", js): bad('app.js: fetch() with a root-relative path ignores the base path')
for p in re.findall(r"fetch\(base \+ '(/[\w/]+)", js):
    if not any(p == rp for _, rp in route_set): bad(f'app.js fetches {p} which is not a route')

# 4b. redirect() targets must be GET routes
for f, s in files.items():
    for m in re.finditer(r"redirect\('(/[^'?]*)", s):
        if ('GET', m.group(1)) not in route_set: bad(f'{f}: redirect to {m.group(1)} which is not a GET route')

# 5. view variables vs data provided
def keys_from_calls(s, name):
    out = []
    for m in re.finditer(r"view\('" + re.escape(name) + r"', (.*?)\);\n", s, re.S):
        out.append(set(re.findall(r"'(\w+)' =>", m.group(1))))
    return out
ctrl_src = '\n'.join(s for f, s in files.items() if f.startswith('src/Controllers/'))
GLOBALS_OK = {'this', 'user', 'content', 'title', '_SESSION', '_POST', '_GET', '_FILES', '_SERVER'}
for p in glob.glob(ROOT + '/views/**/*.php', recursive=True):
    v = os.path.relpath(p, ROOT + '/views')[:-4].replace(os.sep, '/')
    if v == 'layout': continue
    s = rd(p)
    provided = set()
    for ks in keys_from_calls(ctrl_src, v) + keys_from_calls(files['public/index.php'], v): provided |= ks
    if v == 'dashboard': provided |= set(re.findall(r"\$d\['(\w+)'\]", ctrl_src)) | set(re.findall(r"\$d = \['(\w+)' =>", ctrl_src))
    assigned = set(re.findall(r'\$(\w+)\s*=[^=>]', s)) | set(re.findall(r'as (?:\$\w+ => )?\$(\w+)', s)) | set(re.findall(r'\$(\w+) =>', s))
    used_vars = set(re.findall(r'\$([a-zA-Z_]\w*)', s)) - GLOBALS_OK
    for u in sorted(used_vars - provided - assigned):
        bad(f'views/{v}.php uses ${u} but no controller supplies it')

# 6. SQL columns vs schema
schema = rd(ROOT + '/database/schema.sql')
tables = {m.group(1): set(re.findall(r'^\s+(\w+) ', m.group(2), re.M)) - {'PRIMARY', 'FOREIGN', 'UNIQUE', 'INDEX'}
          for m in re.finditer(r'CREATE TABLE IF NOT EXISTS (\w+) \((.*?)\n\) ENGINE', schema, re.S)}
for f, s in files.items():
    for m in re.finditer(r'INSERT (?:IGNORE )?INTO (\w+) \(([\w, ]+)\)', s):
        t, cols = m.group(1), [c.strip() for c in m.group(2).split(',')]
        if t not in tables: bad(f'{f}: INSERT into unknown table {t}')
        else:
            for c in cols:
                if c not in tables[t]: bad(f'{f}: INSERT {t}.{c} is not a column')
    for m in re.finditer(r"UPDATE (\w+) SET ([^'\"]*?) WHERE", s):
        t = m.group(1)
        if t in tables:
            for c in re.findall(r'(\w+) = ', m.group(2)):
                if c not in tables[t]: bad(f'{f}: UPDATE {t}.{c} is not a column')
        else: bad(f'{f}: UPDATE unknown table {t}')
    for t in re.findall(r'(?:FROM|JOIN) (\w+)', s):
        if t.islower() and t in ('users','courses','enrollments','lessons','exercises','attempts','attempt_answers','assignments','submissions','plagiarism_reports','class_sessions','threads','posts','chat_messages','ai_interactions','notifications','login_attempts'): pass

# 7. view array keys vs columns/aliases
known = set().union(*tables.values()) | set(re.findall(r'\bAS (\w+)', '\n'.join(files.values()))) | {'t', 'm', 'a', 'b', 'id'}
known |= set(re.findall(r"'(\w+)' =>", '\n'.join(files.values())))
known |= set(re.findall(r"\$\w+\['(\w+)'\] =", '\n'.join(files.values())))
for f, s in files.items():
    if not f.startswith('views/'): continue
    for k in set(re.findall(r"\$\w+\['(\w+)'\]", s)):
        if k not in known: bad(f'{f}: array key ["{k}"] matches no column, alias or controller key')

defined_fns = set()
for f, t in files.items(): defined_fns |= set(re.findall(r'^function (\w+)\(', t, re.M))
for fn in sorted(defined_fns):
    if sum(len(re.findall(r'\b' + fn + r'\(', t)) for t in files.values()) < 2: bad(f'helper function {fn}() is defined but never used')
print('Classes:', len(classes), '| Routes:', len(routes), '| Views:', len(used), '| Tables:', len(tables))
if problems:
    print(f'\n{len(problems)} PROBLEM(S):'); [print(' -', p) for p in problems]; sys.exit(1)
print('\nNo cross-reference problems found.')
