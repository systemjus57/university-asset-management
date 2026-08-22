# -*- coding: utf-8 -*-
"""
Generates the technical architecture & file structure analysis PDF for the
University Asset Management System (Somali National University).

Every claim in this document was produced by reading the actual source
files in this repository (not inferred from file names). Where something
could not be verified from the code, it is explicitly marked as such.

This is a separate, deeper technical companion to
docs/build_pdf.py (the user/admin functional manual) — it targets
developers and technical evaluators, not end users.
"""
import os
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import cm
from reportlab.lib import colors
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_JUSTIFY
from reportlab.platypus import (
    BaseDocTemplate, PageTemplate, Frame, NextPageTemplate, PageBreak,
    Paragraph, Spacer, Table, TableStyle, Image, ListFlowable, ListItem,
    KeepTogether, HRFlowable
)
from reportlab.platypus.tableofcontents import TableOfContents

BASE = os.path.dirname(os.path.abspath(__file__))
LOGO = os.path.join(os.path.dirname(BASE), "static", "images", "logo.webp")
OUT = os.path.join(BASE, "ASM_Technical_Architecture_Analysis.pdf")

# ---------------------------------------------------------------- colours
NAVY = colors.HexColor('#14375e')
NAVY_DARK = colors.HexColor('#0b2038')
GREEN = colors.HexColor('#8bc53f')
GREEN_DARK = colors.HexColor('#6ea52d')
GRAY = colors.HexColor('#5b6472')
LGRAY = colors.HexColor('#eef1f4')
WARN = colors.HexColor('#c9861a')
WARN_BG = colors.HexColor('#fbf0dd')
DANGER = colors.HexColor('#c0392b')
DANGER_BG = colors.HexColor('#fbe9e7')
SUCCESS = colors.HexColor('#4c9a2a')
WHITE = colors.white

# ---------------------------------------------------------------- styles
ss = getSampleStyleSheet()

def style(name, parent, **kw):
    s = ParagraphStyle(name, parent=parent, **kw)
    ss.add(s)
    return s

Body = style('Body2', ss['Normal'], fontName='Helvetica', fontSize=9.4, leading=13.8,
             spaceAfter=6, alignment=TA_JUSTIFY, textColor=colors.HexColor('#232a31'))
Small = style('Small2', Body, fontSize=8.2, leading=11.6, textColor=GRAY, spaceAfter=4)
Mono = style('Mono2', Body, fontName='Courier', fontSize=8.6, textColor=NAVY_DARK)
Caption = style('Caption2', Body, fontSize=8.2, leading=11, textColor=GRAY,
                 alignment=TA_CENTER, spaceAfter=14, spaceBefore=2, fontName='Helvetica-Oblique')

H1 = style('H1b', ss['Heading1'], fontName='Helvetica-Bold', fontSize=18, leading=22,
           textColor=NAVY, spaceBefore=0, spaceAfter=12, keepWithNext=True)
H2 = style('H2b', ss['Heading2'], fontName='Helvetica-Bold', fontSize=13, leading=16,
           textColor=NAVY, spaceBefore=14, spaceAfter=7, keepWithNext=True)
H3 = style('H3b', ss['Heading3'], fontName='Helvetica-Bold', fontSize=10, leading=13,
           textColor=GREEN_DARK, spaceBefore=9, spaceAfter=4, keepWithNext=True)
FileTitle = style('FileTitle', ss['Heading3'], fontName='Courier-Bold', fontSize=9.6,
                   leading=13, textColor=WHITE, spaceBefore=0, spaceAfter=0,
                   backColor=NAVY_DARK, borderPadding=(5, 8, 5, 8), keepWithNext=True)
SectionBand = style('SectionBand', ss['Heading2'], fontName='Helvetica-Bold', fontSize=14,
                     leading=18, textColor=WHITE, spaceBefore=0, spaceAfter=0,
                     backColor=NAVY, borderPadding=(8, 10, 8, 10), keepWithNext=True)
BulletStyle = style('BulletStyle2', Body, leftIndent=14, bulletIndent=2, spaceAfter=3)
FieldLabel = style('FieldLabel', Body, fontName='Helvetica-Bold', fontSize=8.6,
                    textColor=NAVY_DARK, spaceAfter=1, spaceBefore=5)
FieldValue = style('FieldValue', Body, fontSize=8.8, spaceAfter=2)
Diagram = style('Diagram', Body, fontName='Courier', fontSize=8.0, leading=11.5,
                 textColor=NAVY_DARK, alignment=TA_LEFT, spaceAfter=10)

TOCH1 = style('TOCH1b', ss['Normal'], fontName='Helvetica-Bold', fontSize=10.5, leading=15,
              textColor=NAVY, spaceAfter=5, leftIndent=0)
TOCH2 = style('TOCH2b', ss['Normal'], fontName='Helvetica', fontSize=9, leading=13,
              textColor=colors.HexColor('#232a31'), leftIndent=14, spaceAfter=2)

CoverTitle = style('CoverTitle2', ss['Title'], fontName='Helvetica-Bold', fontSize=24,
                    leading=29, textColor=WHITE, alignment=TA_CENTER, spaceAfter=6)
CoverSub = style('CoverSub2', ss['Normal'], fontName='Helvetica', fontSize=12.5,
                  leading=17, textColor=colors.HexColor('#dfe9f0'), alignment=TA_CENTER,
                  spaceAfter=4)
CoverOrg = style('CoverOrg2', ss['Normal'], fontName='Helvetica-Bold', fontSize=14.5,
                  leading=18, textColor=GREEN, alignment=TA_CENTER, spaceAfter=2)
CoverMeta = style('CoverMeta2', ss['Normal'], fontName='Helvetica', fontSize=9,
                   leading=13, textColor=colors.HexColor('#c9d6e0'), alignment=TA_CENTER)

# ---------------------------------------------------------------- helpers
story = []

def h1(text):
    story.append(Paragraph(text, H1))

def h2(text):
    story.append(Spacer(1, 2))
    story.append(Paragraph(text, H2))

def h3(text):
    story.append(Paragraph(text, H3))

def p(text):
    story.append(Paragraph(text, Body))

def small(text):
    story.append(Paragraph(text, Small))

def bullets(items):
    story.append(ListFlowable(
        [ListItem(Paragraph(i, BulletStyle), leftIndent=14, value='square') for i in items],
        bulletType='bullet', start='square', bulletFontSize=5, bulletColor=GREEN_DARK,
        leftIndent=10
    ))
    story.append(Spacer(1, 3))

def rule():
    story.append(Spacer(1, 3))
    story.append(HRFlowable(width="100%", thickness=0.6, color=colors.HexColor('#d8dde3'),
                             spaceBefore=2, spaceAfter=8))

def section_band(title):
    story.append(Spacer(1, 4))
    t = Table([[Paragraph(title, SectionBand)]], colWidths=[16.8 * cm])
    t.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), NAVY),
        ('LEFTPADDING', (0, 0), (-1, -1), 12),
        ('TOPPADDING', (0, 0), (-1, -1), 8),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 8),
    ]))
    story.append(t)
    story.append(Spacer(1, 8))

def data_table(headers, rows, col_widths=None, small_font=True, header_bg=NAVY):
    fs = 7.8 if small_font else 8.6
    data = [[Paragraph(f'<b>{c}</b>', ParagraphStyle('th2', parent=Body, fontSize=fs,
                                                       textColor=WHITE, alignment=TA_LEFT)) for c in headers]]
    for r in rows:
        data.append([Paragraph(str(c), ParagraphStyle('td2', parent=Body, fontSize=fs,
                                                        spaceAfter=0, alignment=TA_LEFT)) for c in r])
    t = Table(data, colWidths=col_widths, repeatRows=1, hAlign='LEFT')
    t.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), header_bg),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [WHITE, LGRAY]),
        ('GRID', (0, 0), (-1, -1), 0.5, colors.HexColor('#c7cfd6')),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('TOPPADDING', (0, 0), (-1, -1), 4),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 4),
        ('LEFTPADDING', (0, 0), (-1, -1), 6),
        ('RIGHTPADDING', (0, 0), (-1, -1), 6),
    ]))
    story.append(t)
    story.append(Spacer(1, 9))

def diagram(text):
    story.append(Table([[Paragraph(text.replace('\n', '<br/>'), Diagram)]], colWidths=[16.8 * cm],
                        style=TableStyle([
                            ('BACKGROUND', (0, 0), (-1, -1), colors.HexColor('#f5f7fa')),
                            ('BOX', (0, 0), (-1, -1), 0.6, colors.HexColor('#c3ccd6')),
                            ('LEFTPADDING', (0, 0), (-1, -1), 10),
                            ('RIGHTPADDING', (0, 0), (-1, -1), 10),
                            ('TOPPADDING', (0, 0), (-1, -1), 8),
                            ('BOTTOMPADDING', (0, 0), (-1, -1), 8),
                        ])))
    story.append(Spacer(1, 10))

def note_box(text, bg=colors.HexColor('#eef7e1'), border=GREEN_DARK):
    story.append(Table([[Paragraph(text, ParagraphStyle('nb', parent=Body, spaceAfter=0))]],
                        colWidths=[16.8 * cm],
                        style=TableStyle([
                            ('BACKGROUND', (0, 0), (-1, -1), bg),
                            ('BOX', (0, 0), (-1, -1), 0.6, border),
                            ('LEFTPADDING', (0, 0), (-1, -1), 10),
                            ('RIGHTPADDING', (0, 0), (-1, -1), 10),
                            ('TOPPADDING', (0, 0), (-1, -1), 8),
                            ('BOTTOMPADDING', (0, 0), (-1, -1), 8),
                        ])))
    story.append(Spacer(1, 10))

def field(label, text):
    story.append(Paragraph(label, FieldLabel))
    if isinstance(text, list):
        story.append(ListFlowable(
            [ListItem(Paragraph(i, ParagraphStyle('fv', parent=FieldValue, leftIndent=0)), leftIndent=12, value='–')
             for i in text],
            bulletType='bullet', start='–', bulletFontSize=7, bulletColor=GRAY, leftIndent=10
        ))
    else:
        story.append(Paragraph(text, FieldValue))

IMPORTANCE_COLOR = {'CRITICAL': DANGER, 'HIGH': WARN, 'MEDIUM': NAVY, 'LOW': GRAY}

def file_entry(path, purpose, role, functions, deps, used_by, roles, workflow, importance, problems):
    story.append(Spacer(1, 6))
    t = Table([[Paragraph(path, FileTitle)]], colWidths=[16.8 * cm])
    t.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), NAVY_DARK),
        ('LEFTPADDING', (0, 0), (-1, -1), 10), ('TOPPADDING', (0, 0), (-1, -1), 5),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 5),
    ]))
    story.append(t)
    field('Purpose', purpose)
    field('System Role', role)
    field('Main Functions / Queries / Forms', functions)
    field('Dependencies', deps)
    field('Used By', used_by)
    field('User Roles', roles)
    field('Workflow Context', workflow)
    imp_color = IMPORTANCE_COLOR.get(importance, GRAY)
    story.append(Paragraph(
        f'<b>Importance:</b> <font color="{imp_color.hexval()}"><b>{importance}</b></font>', FieldValue))
    field('Problems / Risks / Notes', problems)
    story.append(Spacer(1, 4))

# ==================================================================
# COVER PAGE
# ==================================================================
story.append(Spacer(1, 2.2 * cm))
try:
    story.append(Image(LOGO, width=3.2 * cm, height=3.2 * cm, hAlign='CENTER'))
except Exception:
    pass
story.append(Spacer(1, 0.8 * cm))
story.append(Paragraph('University Asset Management System (ASM)', CoverTitle))
story.append(Spacer(1, 0.3 * cm))
story.append(Paragraph('Technical Architecture &amp; File Structure Analysis', CoverSub))
story.append(Spacer(1, 1.4 * cm))
story.append(Paragraph('SOMALI NATIONAL UNIVERSITY', CoverOrg))
story.append(Spacer(1, 2.8 * cm))
story.append(Paragraph('A code-verified technical reference: every file, module, database relationship,<br/>'
                        'workflow, role/permission rule, and security finding documented from the<br/>'
                        'actual source code — prepared for developers and technical evaluators.', CoverMeta))
story.append(Spacer(1, 0.5 * cm))
story.append(Paragraph('Companion document to: University_Asset_Management_System_Documentation.pdf '
                        '(the end-user/admin functional manual)', CoverMeta))
story.append(Spacer(1, 0.5 * cm))
story.append(Paragraph('Document Version 1.0', CoverMeta))
story.append(NextPageTemplate('Normal'))
story.append(PageBreak())

# ==================================================================
# TABLE OF CONTENTS
# ==================================================================
story.append(Paragraph('Table of Contents', H1))
rule()
toc = TableOfContents()
toc.levelStyles = [TOCH1, TOCH2]
story.append(toc)
story.append(PageBreak())

# ==================================================================
# PART 1 — COMPLETE STRUCTURE
# ==================================================================
h1('Part 1 — Complete Structure')
p('This section reconstructs the project structure directly from the files present on disk and explains '
  'the role of each top-level directory. Two notes on the structure as originally supplied for this '
  'analysis versus what actually exists in the repository:')
bullets([
    '<b>Reconciled differences:</b> the module named <font face="Courier">assigned/</font> (Allocations) is '
    'a top-level folder under <font face="Courier">modules/</font>, not nested inside '
    '<font face="Courier">modules/assets/</font>, and it has three files, not two — '
    '<font face="Courier">add.php</font>, <font face="Courier">list.php</font>, and '
    '<font face="Courier">return.php</font> (the return/repair action). '
    '<font face="Courier">includes/forbidden.php</font> actually lives at '
    '<font face="Courier">includes/layout/forbidden.php</font>. There is no '
    '<font face="Courier">static/img/</font> directory (only <font face="Courier">static/images/logo.webp</font>). '
    'A root <font face="Courier">index.php</font> entry point exists and was not listed. The FPDF font file '
    'names in <font face="Courier">vendor/fpdf/font/</font> are '
    '<font face="Courier">times.json / timesb.json / timesbi.json / timesi.json</font> etc. '
    '(the originally supplied tree had typos for three of these).',
    '<b>Pre-existing documentation:</b> <font face="Courier">docs/University_Asset_Management_System_Documentation.pdf</font> '
    'and the Python script that generates it (<font face="Courier">docs/build_pdf.py</font>) already exist in the '
    'repository as a functional, user/admin-facing manual. This document is a separate, deeper technical '
    'companion aimed at developers — it does not replace or duplicate that file.',
])

h2('1.1 Directory-by-Directory Role')
data_table(
    ['Directory', 'Role in the System'],
    [
        ['config/', 'Two files loaded first by every request: database connection parameters '
                     '(database.php) and global application configuration — base URL, session '
                     'hardening, timezone, error-log routing (config.php).'],
        ['database/', 'A single file, schema.sql: the complete MySQL schema (15 tables) plus demo seed '
                       'data. This is the authoritative source of the database structure — there is no '
                       'ORM or ODM; every table here is queried directly with hand-written SQL.'],
        ['docs/', 'Project documentation: the existing user/admin manual PDF and its generator script, '
                  'plus this technical analysis and its generator.'],
        ['includes/', 'Shared backend logic loaded by (almost) every page: session/RBAC guards (auth.php), '
                       'general helper functions (functions.php), the internationalization layer '
                       '(i18n.php / i18n_strings.php), the single bootstrap include point '
                       '(bootstrap.php), and the shared HTML shell (includes/layout/*.php).'],
        ['modules/', 'One folder per feature area. Every PHP file in a module is a self-contained '
                      'controller+view: it checks the caller&rsquo;s role, reads/writes the database with '
                      'PDO, and renders its own HTML in the same file (no separate template engine).'],
        ['static/', 'Presentation-only assets served directly by the web server: the CSS theme, two '
                     'vanilla-JS files, and the bundled default logo. Nothing here executes server-side '
                     'code.'],
        ['storage/', 'Runtime, non-source content: the PHP error log (php-error.log) and a reserved '
                      '(currently empty) backups/ folder. Not meant to be served to browsers as content, '
                      'though no explicit web-server rule in this codebase prevents that (see Part 8).'],
        ['uploads/', 'User-supplied binary content written at runtime by the application: '
                      'uploads/logos (admin-uploaded university logo) and uploads/avatars '
                      '(user profile pictures). Both are populated by move_uploaded_file() calls in '
                      'modules/settings/index.php and modules/profile/index.php respectively.'],
        ['vendor/', 'One third-party library, vendored by hand (no Composer/package manager in this '
                     'project): FPDF, used exclusively by modules/reports/export_pdf.php to generate '
                     'downloadable PDF reports.'],
    ], col_widths=[3.2 * cm, 13.6 * cm]
)

h2('1.2 How the Layers Communicate')
diagram(
"Browser (HTML forms / links, GET and POST)\n"
"        |\n"
"        v\n"
"modules/[area]/[file].php   -- one file = one controller+view\n"
"        |  require_once includes/bootstrap.php  (always first line)\n"
"        v\n"
"includes/bootstrap.php\n"
"   -> config/config.php      (constants, session hardening, session_start())\n"
"   -> config/database.php    (creates $pdo : PDO, or dies with 500 on failure)\n"
"   -> includes/auth.php      (ROLE_* constants, isLoggedIn/requireLogin/requireRole/hasRole)\n"
"   -> includes/functions.php (e, clean, csrf*, flash, logActivity, statusBadge, recomputeAssetStatus, ...)\n"
"   -> includes/i18n.php      (t(), activeLanguage(); reads i18n_strings.php on first call)\n"
"   -> setActiveLanguage(getSetting($pdo,'language'))\n"
"   -> maintenance-mode gate (blocks everyone except Admin if settings.maintenance_mode = '1')\n"
"        |\n"
"        v\n"
"the module file's own PHP logic\n"
"   -> requireLogin() / requireRole([...])          (every protected page, before any output)\n"
"   -> $pdo->prepare(...)->execute([...])            (all queries: bound parameters, never string-built SQL)\n"
"   -> on POST: requireCsrf(), validate, write, logActivity(), flash(), redirect()\n"
"        |\n"
"        v\n"
"includes/layout/header.php  (opens the HTML doc, loads static/css/style.css + Inter font,\n"
"                              includes sidebar.php, renders topbar incl. getPendingAlerts())\n"
"        |\n"
"        v\n"
"the module's own HTML output (uses e(), statusBadge(), formatMoney()/formatDate() from functions.php)\n"
"        |\n"
"        v\n"
"includes/layout/footer.php  (closes shell, loads static/js/main.js)\n"
"        |\n"
"        v\n"
"Browser renders page; static/js/main.js + validation.js add client-side interactivity\n"
"(all of it optional/UX-only — every rule is re-checked server-side on submit)"
)
p('Every module file therefore has exactly the same top: <font face="Courier">require_once bootstrap.php</font>, '
  'a role guard, then its own logic. There is no router, no MVC framework, and no template engine — the '
  '"View" is simply inline HTML in the same PHP file as the "Controller" logic, which is a deliberate, '
  'consistent choice throughout the codebase (confirmed by inspecting all ~50 module files).')
story.append(PageBreak())

# ==================================================================
# PART 2 — FILE-BY-FILE ANALYSIS
# ==================================================================
h1('Part 2 — File-by-File Analysis')
p('Every PHP, SQL, JavaScript, CSS, and Python file that contributes to the running application is '
  'documented below, grouped by directory in the same order as Part 1. Each entry was produced by reading '
  'the file&rsquo;s actual contents. "Cannot be confirmed from the available code" is used explicitly '
  'wherever a question genuinely cannot be answered from what is in this repository.')

h2('2.1 Root')
file_entry('index.php',
    'The single public entry point for the whole application.',
    'Router shim — redirects to the Dashboard if a session already exists, otherwise to the Login page.',
    ['isLoggedIn()', 'redirect()'],
    ['includes/bootstrap.php'],
    ['Loaded whenever a browser requests the site root ("/asm/" or "/asm/index.php")'],
    'All (unauthenticated visitors included)',
    'First page hit for anyone visiting the site with no deep link; hands off immediately to Auth or Dashboard.',
    'HIGH',
    'None identified — it is a two-line, low-risk redirect.')

h2('2.2 config/')
file_entry('config/config.php',
    'Defines global constants and hardens/starts the PHP session before any other code runs.',
    'Application bootstrap configuration — the very first file executed via bootstrap.php.',
    ['Defines APP_NAME, APP_ROOT, APP_URL', 'session.* ini_set() hardening (strict_mode, HttpOnly, SameSite=Lax, Secure-if-HTTPS)',
     'session_start()', 'date_default_timezone_set()', 'error_reporting/display_errors/log_errors to storage/php-error.log'],
    ['None (no includes)'],
    ['includes/bootstrap.php (first require)'],
    'All',
    'Runs on literally every request before anything else — nothing works without it.',
    'CRITICAL',
    ['The session lifetime passed to session_set_cookie_params() is a hardcoded fallback (1440 minutes) '
     'read before the database/settings are available. The admin-configurable '
     '"Session Timeout (minutes)" setting saved in Settings &rarr; General is stored in the database but '
     'this file never re-applies it anywhere later in the request lifecycle — see Part 13.',
     'APP_URL is a hardcoded literal ("/asm") rather than derived from the request, so moving the '
     'deployment to a different sub-path requires editing this file.'])
file_entry('config/database.php',
    'Opens the single shared PDO connection used by every module.',
    'Data-access bootstrap — the only place a database connection is created.',
    ['Defines DB_HOST/DB_NAME/DB_USER/DB_PASS/DB_CHARSET', 'new PDO(...) with ERRMODE_EXCEPTION, FETCH_ASSOC, EMULATE_PREPARES=false'],
    ['None'],
    ['includes/bootstrap.php (second require); every module that touches $pdo depends on this indirectly'],
    'All',
    'Runs on every request immediately after config.php.',
    'CRITICAL',
    ['Credentials are plain-text constants in a version-controlled file (root / empty password). Acceptable '
     'for the local/demo environment this project ships with, but must be replaced with environment-specific, '
     'non-committed credentials before any real deployment.',
     'PDO::ATTR_EMULATE_PREPARES is explicitly disabled, which is good for true server-side prepared '
     'statements — but it also means the same named placeholder cannot be bound twice in one query '
     '(SQLSTATE[HY093] if attempted). This exact mistake was found and fixed in modules/auth/login.php and '
     'modules/assets/list.php during earlier work on this codebase, and is still present and unfixed in '
     'modules/users/list.php&rsquo;s search query — see Part 13.'])

h2('2.3 database/')
file_entry('database/schema.sql',
    'The complete, authoritative MySQL schema (15 tables) plus demo seed data for all of them.',
    'Data layer definition — every table every module reads or writes is defined here.',
    ['CREATE DATABASE / 15x CREATE TABLE with explicit FOREIGN KEY ... ON UPDATE/ON DELETE rules',
     'INSERT seed data: 4 roles, 6 departments, 8 users (bcrypt-hashed demo password), 6 categories, '
     '7 locations, 18 assets, and representative rows in every workflow table'],
    ['None (pure SQL, run once via `mysql -u root -p < database/schema.sql` per README.md)'],
    ['Every module file, indirectly, via the tables it creates'],
    'DBA / Admin (setup-time only)',
    'Executed once to provision a fresh environment; not touched by the running application except '
    'indirectly via modules/settings/backup_download.php (re-generates an equivalent dump at runtime).',
    'CRITICAL',
    ['Comment block at the top documents a deliberate scope decision: the original brief specified 9-10 '
     'tables; this schema adds locations, asset_audits, settings, activity_logs, and login_logs to close '
     'gaps and support the admin Settings module — a documented, reasoned design choice, not scope creep.',
     'assets.status is designed to be derived (see includes/functions.php: recomputeAssetStatus()), but the '
     'schema itself does not enforce that with a trigger or generated column — a direct UPDATE from the '
     'Assets edit form (modules/assets/edit.php) can still set it out of sync with reality; the UI warns '
     'about this but does not prevent it.'])

h2('2.4 includes/')
file_entry('includes/bootstrap.php',
    'The single include point every module entry file requires as its first line.',
    'Composition root — wires config, database, auth, helpers, and i18n together in a fixed order, then '
    'applies the maintenance-mode gate.',
    ['require_once config/config.php, config/database.php, auth.php, functions.php, i18n.php (in that order)',
     'setActiveLanguage(getSetting($pdo, "language", "en"))',
     'Maintenance-mode gate: if settings.maintenance_mode = "1" and the caller is logged in and not Admin, '
     'the request is stopped with an HTTP 503 page'],
    ['config/config.php', 'config/database.php', 'includes/auth.php', 'includes/functions.php', 'includes/i18n.php'],
    ['Every module/**/*.php file in the project (the first line of each)'],
    'All',
    'Runs on every authenticated and unauthenticated request alike; the maintenance-mode gate is the one '
    'piece of cross-cutting request-blocking logic in the whole system.',
    'CRITICAL',
    'None identified in the file itself; its correctness depends entirely on the files it requires.')
file_entry('includes/auth.php',
    'Defines the four role constants and every session/RBAC guard function used throughout the app.',
    'Authorization core.',
    ['ROLE_ADMIN/ROLE_OFFICER/ROLE_HEAD/ROLE_TOPMGMT constants', 'isLoggedIn()', 'currentUser()',
     'requireLogin() — redirects to login.php if not authenticated', 'requireRole(array) — 403s via '
     'includes/layout/forbidden.php if the session role is not in the allowed list', 'hasRole(array)', 'isAdmin()'],
    ['$_SESSION superglobal only'],
    ['Every module file (requireLogin()/requireRole()/hasRole() calls throughout)', 'includes/functions.php '
     '(getPendingAlerts uses hasRole)', 'includes/layout/sidebar.php (role-based menu rendering)'],
    'All (defines the roles themselves)',
    'The gatekeeper for literally every restricted page and every conditional UI element in the system.',
    'CRITICAL',
    ['requireRole() correctly denies with an HTTP 403 page rather than silently redirecting, so a '
     'restricted URL visited directly is visibly blocked rather than confusingly bounced elsewhere — this '
     'is a good, deliberate security pattern, confirmed used consistently in every module file read.'])
file_entry('includes/functions.php',
    'The general-purpose helper library shared by every module — sanitization, CSRF, formatting, '
    'activity logging, the notification-bell data source, and the shared report-query builder.',
    'Cross-cutting utilities layer.',
    ['e() / clean() — output escaping and input trimming', 'redirect(), flash()', 'csrfToken()/csrfField()/'
     'verifyCsrf()/requireCsrf()', 'formatMoney()/formatDate()/formatDateTime()', 'statusBadge()', 'icon() '
     '— inline SVG icon library', 'appLogoUrl()', 'getPendingAlerts() — builds the topbar notification list',
     'getReportRows() — the single shared query builder used by both CSV and PDF report export',
     'logActivity()/logLogin()', 'getAllSettings()/getSetting()/setSetting() — key/value settings cache',
     'recomputeAssetStatus() — derives assets.status from maintenance/disposal state',
     'validateRequired()'],
    ['$pdo (PDO, passed in per-call)', '$_SESSION', 'includes/i18n.php (t(), activeLanguage())'],
    ['Every module file uses several of these helpers', 'modules/reports/export_csv.php and export_pdf.php '
     '(getReportRows())', 'includes/layout/header.php (getPendingAlerts(), appLogoUrl())'],
    'All',
    'The most heavily depended-on file in the project after bootstrap.php itself.',
    'CRITICAL',
    ['recomputeAssetStatus() is the one piece of real "business logic" in the helper layer — it is called '
     'from modules/maintenance/add.php, modules/maintenance/update.php, and modules/disposals/approve.php, '
     'but not from modules/assets/edit.php, which lets an Admin/Officer hand-set a status that this function '
     'would otherwise immediately overwrite on the next maintenance/disposal event — a latent '
     'data-consistency trap flagged in Part 13.',
     'getPendingAlerts() looks back only 7 days for "your request was decided" notifications with no '
     'persisted read/unread state — see Part 13.'])
file_entry('includes/i18n.php',
    'A minimal translation layer: resolves the active language once per request and exposes t()/tWeekday().',
    'Internationalization core.',
    ['setActiveLanguage(string)/activeLanguage()', 't(string $key): string — looks up i18n_strings.php, '
     'falls back to English then to the raw key', 'tWeekday(string $ymd) — localized 3-letter weekday label'],
    ['includes/i18n_strings.php (lazily required on first t() call)'],
    ['includes/bootstrap.php (sets the active language)', 'includes/layout/*.php', 'modules/auth/login.php',
     'modules/dashboard/index.php', 'modules/settings/index.php and _subnav.php', 'includes/functions.php '
     '(getPendingAlerts() alert text)'],
    'All',
    'Applied globally as soon as the site language setting is read in bootstrap.php.',
    'MEDIUM',
    'None in the mechanism itself — its only weakness is coverage (see i18n_strings.php below).')
file_entry('includes/i18n_strings.php',
    'The English/Somali translation dictionary consumed by t().',
    'Internationalization data.',
    ['Returns a flat associative array: key => [\'en\' => ..., \'so\' => ...]'],
    ['None'],
    ['includes/i18n.php only'],
    'All (affects every user once Somali is selected in Settings &rarr; General)',
    'Read once per request (cached in a static variable inside t()) whenever any translated string is rendered.',
    'MEDIUM',
    ['The file&rsquo;s own header comment states coverage honestly: only the app shell (sidebar/topbar/'
     'footer), the login page, the dashboard, and Settings &rarr; General are translated. Every CRUD module '
     '(Assets, Allocations, Transfers, Maintenance, Audits, Disposals, Requisitions, Users, Departments, '
     'Categories, Locations, the other Settings tabs, Reports) remains English-only even with Somali active '
     '— a real, self-documented incomplete workflow (Part 13).'])
file_entry('includes/layout/header.php',
    'Opens the shared HTML document, loads the theme CSS and Google Font, renders the topbar (notification '
    'bell + user menu), and starts the sidebar include.',
    'Shared view shell — the "top half" of every authenticated page.',
    ['Reads $pageTitle/$activeMenu set by the calling module', 'getSetting() for university name and theme',
     'getPendingAlerts() for the bell dropdown', 'includes sidebar.php', 'renders flash() banners at the top '
     'of the &lt;main&gt; content area'],
    ['includes/functions.php', 'includes/i18n.php', 'includes/layout/sidebar.php', 'static/css/style.css'],
    ['Every module file (via `include __DIR__ . \'/../../includes/layout/header.php\'`)'],
    'All',
    'The first visual element rendered on every authenticated page.',
    'CRITICAL',
    'None identified — confirmed cache-busted (`?v=filemtime(...)`) so CSS/JS edits are always picked up '
    'immediately rather than served stale from the browser cache.')
file_entry('includes/layout/sidebar.php',
    'Renders the left navigation menu, role-aware.',
    'Shared view shell — primary navigation.',
    ['navItem() helper', 'Renders Administration section (Users/Departments/Categories/Locations/Settings) '
     'only when $role === ROLE_ADMIN'],
    ['includes/functions.php (icon(), appLogoUrl())', 'includes/i18n.php (t())'],
    ['includes/layout/header.php only'],
    'All (content varies by role)',
    'Rendered as part of every authenticated page\'s shell.',
    'HIGH',
    'None identified — correctly relies on the same $role check pattern as the server-side requireRole() '
    'guards, so the menu and the actual access rules never contradict each other.')
file_entry('includes/layout/footer.php',
    'Closes the shared HTML shell and loads the shared JavaScript file.',
    'Shared view shell — the "bottom half" of every authenticated page.',
    ['Renders the copyright line', 'Loads static/js/main.js with a cache-busting query string'],
    ['includes/i18n.php (t())'],
    ['Every module file (via include at the end of its output)'],
    'All',
    'The last thing rendered on every authenticated page.',
    'HIGH',
    'None identified.')
file_entry('includes/layout/forbidden.php',
    'A minimal standalone HTML page shown when requireRole() denies access.',
    'Shared view shell — the 403 Access Denied page.',
    ['Static HTML with a link back to the Dashboard'],
    ['static/css/style.css'],
    ['includes/auth.php: requireRole()', 'modules/assets/view.php (direct 403 for a Department Head opening '
     'another department\'s asset)'],
    'All (shown to whichever role was denied)',
    'The terminal page for any unauthorized direct-URL access attempt.',
    'MEDIUM',
    'Does not itself go through includes/layout/header.php, so it intentionally does not show the sidebar '
    '— reasonable, since the visitor was just denied access to that context.')

h2('2.5 modules/auth/')
file_entry('modules/auth/login.php',
    'The login form and its POST handler; the only unauthenticated page in the system besides the '
    'redirect shim.',
    'Authentication entry point.',
    ['GET: renders the login form', 'POST: verifies CSRF, looks up `WHERE email = :login1 OR username = '
     ':login2`, password_verify(), checks status = "active", session_regenerate_id(true), populates '
     '$_SESSION, logLogin() + logActivity(), redirects to $_SESSION[\'redirect_after_login\'] or the '
     'Dashboard'],
    ['includes/bootstrap.php', 'includes/functions.php (logLogin, logActivity, csrfField/verifyCsrf)',
     'includes/i18n.php (all user-facing strings)', 'static/js/validation.js'],
    ['index.php and includes/auth.php: requireLogin() (redirect target for anyone not logged in)'],
    'Unauthenticated visitors only (already-logged-in users are redirected away)',
    'The first step of every session; sets every $_SESSION key the rest of the app relies on.',
    'CRITICAL',
    'None identified in the current version — an earlier version of this query had a duplicate `:login` '
    'placeholder bound twice under EMULATE_PREPARES=false, which crashed with SQLSTATE[HY093]; it has since '
    'been fixed to use two distinct placeholders (`:login1`/`:login2`) bound to the same value. No login-'
    'attempt rate limiting or account lockout exists — see Part 8.')
file_entry('modules/auth/logout.php',
    'Ends the current session.',
    'Authentication exit point.',
    ['logActivity() if a session exists', 'Clears $_SESSION, expires the session cookie, session_destroy()',
     'Redirects to login.php'],
    ['includes/bootstrap.php', 'includes/functions.php'],
    ['includes/layout/header.php (the Logout link in the user dropdown menu)'],
    'Any logged-in user',
    'Terminates a session cleanly, including on the server side (not just clearing the cookie).',
    'HIGH',
    'None identified.')

h2('2.6 modules/dashboard/')
file_entry('modules/dashboard/index.php',
    'The role-scoped landing page shown immediately after login.',
    'Summary/overview module.',
    ['Six independent COUNT()/GROUP BY queries for the stat cards, status donuts, and 7-day activity chart '
     '(all department-scoped for a Department Head via $isHead)', 'renderDonut() — local helper building a '
     'CSS conic-gradient donut', 'Recent Activity feed (12 most recent activity_logs rows, module-filtered '
     'for a Department Head)'],
    ['includes/bootstrap.php, functions.php, i18n.php', 'Tables: assets, asset_assigned, asset_maintenance, '
     'asset_disposals, requisitions, users, activity_logs'],
    ['includes/auth.php: requireLogin() redirect target', 'modules/auth/login.php (default post-login '
     'redirect)'],
    'All (content scoped per role)',
    'The hub every user returns to; every number shown here is a live query, not a cached snapshot.',
    'HIGH',
    'None identified — all six queries were manually cross-checked against the same role-scoping pattern '
    'used consistently elsewhere in the codebase ($isHead / department_id lock).')

h2('2.7 modules/assets/ — Asset Registry')
file_entry('modules/assets/list.php',
    'Searchable, filterable, paginated list of every asset.',
    'Assets module — list/search view.',
    ['Builds a dynamic WHERE clause from q/category_id/department_id/status/page', 'Department Head is '
     'hard-locked to their own department_id regardless of query string', 'Bound LIMIT/OFFSET pagination'],
    ['includes/bootstrap.php', 'Tables: assets, categories, departments, locations'],
    ['includes/layout/sidebar.php (Assets nav link)', 'Linked to from almost every other module\'s rows '
     '("View asset")'],
    'All (Admin/Officer see + edit; Department Head view-only, own department; Top Management view-only, all)',
    'The default browsing surface for the entire asset register.',
    'CRITICAL',
    'None currently — the search box previously reused the same named placeholder (`:q`) twice in one '
    '`LIKE ... OR ... LIKE` clause, which crashed under EMULATE_PREPARES=false; it has since been fixed to '
    'use `:q1`/`:q2`. Default sort order was previously DESC (newest first); it is now ASC by asset_id '
    '(lowest ID first) as intentionally corrected.')
file_entry('modules/assets/add.php',
    'Registers a brand-new asset.',
    'Assets module — create.',
    ['Validates required fields + numeric cost + status enum', 'INSERT into assets', 'logActivity()',
     'Redirects straight to the new asset\'s view.php'],
    ['includes/bootstrap.php', 'modules/assets/_form.php (shared field partial)', 'Tables: assets, '
     'categories, departments, locations (dropdown sources)'],
    ['includes/layout/sidebar.php', 'modules/assets/list.php ("+ Add Asset" button)'],
    'Admin, Asset Officer',
    'Step one of the asset lifecycle: every other module (allocation, transfer, maintenance, audit, '
    'disposal) attaches its history to the row created here.',
    'CRITICAL',
    'None identified.')
file_entry('modules/assets/edit.php',
    'Edits an existing asset\'s details, including allowing a manual override of its status.',
    'Assets module — update.',
    ['Same validation as add.php', 'UPDATE assets', 'On error, re-merges posted values back into the form'],
    ['includes/bootstrap.php', 'modules/assets/_form.php', 'Tables: assets, categories, departments, locations'],
    ['modules/assets/list.php and view.php ("Edit" links)'],
    'Admin, Asset Officer',
    'Corrections and reclassification within the asset lifecycle.',
    'HIGH',
    'The Status field can be hand-edited here even though the rest of the system treats it as derived '
    '(see recomputeAssetStatus() in includes/functions.php) — the form shows a warning label, but nothing '
    'server-side prevents an inconsistent manual value, which a subsequent maintenance/disposal event will '
    'then silently overwrite.')
file_entry('modules/assets/view.php',
    'The single detail page for one asset: its own fields plus five history tabs.',
    'Assets module — detail/history view; the hub that ties every other module\'s per-asset records together.',
    ['Five separate queries: asset_assigned, asset_transfers, asset_maintenance, asset_audits, '
     'asset_disposals, all `WHERE asset_id = :id`', 'Explicit 403 check: a Department Head whose department '
     'does not match asset.department_id is shown includes/layout/forbidden.php directly'],
    ['includes/bootstrap.php', 'Tables: assets + all five history tables + categories/departments/locations '
     'for the header fields'],
    ['Linked to from modules/assets/list.php and from every other module\'s asset column '
     '(assigned/transfers/maintenance/audits/disposals/requisitions list rows)'],
    'All (department-scoped for Department Head)',
    'The single source of truth for "everything that has ever happened to this asset."',
    'CRITICAL',
    'None identified — this is the one page in the system that most directly demonstrates the relational '
    'design (Part 4) by pulling from all five child tables in one view.')
file_entry('modules/assets/_form.php',
    'The shared HTML form fields reused by both add.php and edit.php.',
    'Assets module — shared view partial (not a standalone page; has no bootstrap.php require of its own).',
    ['Renders Name/Category/Serial/Location/Department/Status/Purchase Date/Cost/Warranty/Description fields'],
    ['Expects $asset/$categories/$departments/$locations/$errors to already be set by the including file'],
    ['modules/assets/add.php', 'modules/assets/edit.php'],
    'Admin, Asset Officer (the only roles that ever reach add.php/edit.php)',
    'Presentation partial for asset create/edit.',
    'MEDIUM',
    'None identified — a clean, intentional DRY pattern (one field partial, two controllers) that is not '
    'reused anywhere else in the codebase (every other module\'s add/edit forms are written out separately, '
    'e.g. assigned/add.php vs. transfers/add.php), so this is the one place the pattern was actually applied.')

h2('2.8 modules/assigned/ — Allocations')
file_entry('modules/assigned/add.php',
    'Issues (assigns) an asset to a department and, optionally, a named custodian.',
    'Allocations module — create.',
    ['Rejects a disposed asset', 'Wraps the insert + the asset.department_id update in a DB transaction '
     '(beginTransaction/commit/rollBack)'],
    ['includes/bootstrap.php', 'Tables: asset_assigned, assets, departments, users'],
    ['modules/assigned/list.php ("+ Assign Asset")'],
    'Admin, Asset Officer',
    'The step that turns "the university owns this" into "this department currently holds this."',
    'HIGH',
    'None identified — the explicit transaction here (and in transfers/add.php) is a deliberately more '
    'careful pattern than most other write operations in the codebase, appropriate since two related tables '
    '(asset_assigned + assets) must stay consistent.')
file_entry('modules/assigned/list.php',
    'Filterable list of every allocation, current and historical, with "Mark Returned"/"Send to Repair" '
    'actions on active rows.',
    'Allocations module — list/action view.',
    ['Filter by department (locked for Department Head)/custodian/status', 'Two POST forms per active row '
     'targeting return.php with a different `outcome` hidden field'],
    ['includes/bootstrap.php', 'Tables: asset_assigned, assets, departments, users'],
    ['includes/layout/sidebar.php'],
    'All (Admin/Officer act; Department Head + Top Management view-only)',
    'The operational view of "who has what right now."',
    'HIGH',
    'None identified.')
file_entry('modules/assigned/return.php',
    'POST-only endpoint that closes an active allocation as either Returned or Repair.',
    'Allocations module — state transition.',
    ['`UPDATE asset_assigned SET return_date = today, status = :outcome WHERE assign_id = :id AND status = '
     '"active"`'],
    ['includes/bootstrap.php'],
    ['modules/assigned/list.php (the two per-row forms)'],
    'Admin, Asset Officer',
    'Closes the loop on an allocation; does not itself touch assets.status (only Maintenance/Disposals do that).',
    'MEDIUM',
    'Redirects to list.php on any non-POST request rather than 404ing, which is a minor but consistent '
    'defensive pattern used by every similar action-only endpoint in the codebase (return.php, deactivate.php).')

h2('2.9 modules/transfers/')
file_entry('modules/transfers/add.php',
    'Moves an asset from its current department to a new one.',
    'Transfers module — create.',
    ['Rejects a disposed asset or a "transfer" to the asset\'s current department', 'Transaction wrapping '
     'the insert + assets.department_id update'],
    ['includes/bootstrap.php', 'Tables: asset_transfers, assets, departments'],
    ['modules/transfers/list.php'],
    'Admin, Asset Officer',
    'Records inter-department movement separately from day-to-day custodian allocation.',
    'HIGH',
    'None identified.')
file_entry('modules/transfers/list.php',
    'Full transfer history, department-scoped (source or destination) for a Department Head.',
    'Transfers module — list view.',
    ['`WHERE from_department_id = :d OR to_department_id = :d` for a Department Head'],
    ['includes/bootstrap.php', 'Tables: asset_transfers, assets, departments, users'],
    ['includes/layout/sidebar.php'],
    'All (Admin/Officer act; Department Head + Top Management view-only)',
    'Read-only audit trail of every inter-department move.',
    'MEDIUM',
    'None identified.')

h2('2.10 modules/maintenance/')
file_entry('modules/maintenance/add.php',
    'Reports a maintenance issue against an asset.',
    'Maintenance module — create (the one create-form open to Department Head as well as Admin/Officer).',
    ['Department Head\'s asset dropdown is pre-filtered to their own department; the server independently '
     're-checks the department match on submit', 'INSERT with status "pending"', 'recomputeAssetStatus() '
     'called immediately after'],
    ['includes/bootstrap.php', 'includes/functions.php (recomputeAssetStatus)', 'Tables: asset_maintenance, assets'],
    ['modules/maintenance/list.php'],
    'Admin, Asset Officer, Department Head',
    'The entry point that (indirectly) flips an asset to "Under Repair."',
    'HIGH',
    'None identified.')
file_entry('modules/maintenance/list.php',
    'Filterable list of maintenance tickets.',
    'Maintenance module — list view.',
    ['Filter by status', 'Department-scoped for Department Head'],
    ['includes/bootstrap.php', 'Tables: asset_maintenance, assets, users'],
    ['includes/layout/sidebar.php'],
    'All (Admin/Officer manage; Department Head reports + views; Top Management view-only)',
    'Operational queue of open and historical repair tickets.',
    'HIGH',
    'None identified.')
file_entry('modules/maintenance/update.php',
    'Progresses a ticket through Pending &rarr; In Progress &rarr; Completed and records cost/technician.',
    'Maintenance module — update/close.',
    ['Requires a Completed Date when status is set to "completed"', 'Validates Cost is numeric',
     'recomputeAssetStatus() called after every save, which is what returns an asset to "Active" once all '
     'its open tickets are closed'],
    ['includes/bootstrap.php', 'includes/functions.php', 'Tables: asset_maintenance, assets'],
    ['modules/maintenance/list.php ("Update" link)'],
    'Admin, Asset Officer',
    'The step that actually closes the maintenance loop and restores the asset&rsquo;s status.',
    'HIGH',
    'None identified.')

h2('2.11 modules/audits/')
file_entry('modules/audits/add.php',
    'Records a physical stock-check result for an asset.',
    'Audits module — create.',
    ['INSERT into asset_audits with result IN (found, missing, damaged)'],
    ['includes/bootstrap.php', 'Tables: asset_audits, assets'],
    ['modules/audits/list.php'],
    'Admin, Asset Officer',
    'A deliberately isolated workflow — recording Missing/Damaged does not itself change assets.status.',
    'MEDIUM',
    'None identified — the asset dropdown here intentionally includes disposed assets (unlike every other '
    'create form), since an audit can legitimately confirm a disposed asset is indeed gone; this is a '
    'correct, deliberate difference, not an oversight.')
file_entry('modules/audits/list.php',
    'Read-only, department-scoped list of every audit record.',
    'Audits module — list view.',
    ['Department-scoped WHERE for Department Head'],
    ['includes/bootstrap.php', 'Tables: asset_audits, assets, users'],
    ['includes/layout/sidebar.php'],
    'All (Admin/Officer create; everyone else view-only)',
    'Permanent physical-verification history; no edit/delete exists for audit rows anywhere in the codebase.',
    'MEDIUM',
    'None identified.')

h2('2.12 modules/disposals/')
file_entry('modules/disposals/add.php',
    'Requests the disposal of an asset (does not dispose it).',
    'Disposals module — create request.',
    ['Rejects an already-disposed asset or one with an existing pending disposal request'],
    ['includes/bootstrap.php', 'Tables: asset_disposals, assets'],
    ['modules/disposals/list.php'],
    'Admin, Asset Officer',
    'Step 1 of the two-party disposal workflow — deliberately cannot approve its own request.',
    'HIGH',
    'None identified.')
file_entry('modules/disposals/approve.php',
    'Approves or rejects a pending disposal request.',
    'Disposals module — the sole point where an asset actually becomes "Disposed."',
    ['Refuses to open an already-reviewed request', 'Transaction wrapping the status update + '
     'recomputeAssetStatus() (only called on approval)'],
    ['includes/bootstrap.php', 'includes/functions.php', 'Tables: asset_disposals, assets'],
    ['modules/disposals/list.php ("Review" link, Admin/Top Management only)'],
    'Admin, Top Management',
    'Step 2 of the disposal workflow — the deliberate separation-of-duties control described in the README.',
    'HIGH',
    'None identified — role gating (Admin/Top Management only, explicitly excluding the Asset Officer who '
    'requested it) is a correctly-enforced separation of duties, confirmed in both the role guard and the '
    'permission matrix in Part 5.')
file_entry('modules/disposals/list.php',
    'Filterable list of disposal requests and their outcomes.',
    'Disposals module — list view.',
    ['Filter by status', 'Department-scoped for Department Head'],
    ['includes/bootstrap.php', 'Tables: asset_disposals, assets, users'],
    ['includes/layout/sidebar.php'],
    'All (Admin requests+approves; Officer requests only; Department Head view-only; Top Management approves)',
    'The full history of every write-off decision made in the system.',
    'HIGH',
    'None identified.')

h2('2.13 modules/requisitions/')
file_entry('modules/requisitions/add.php',
    'Submits a department\'s request for new/replacement assets.',
    'Requisitions module — create.',
    ['Department Head\'s department field is locked to their own session department_id'],
    ['includes/bootstrap.php', 'Tables: requisitions, departments, categories'],
    ['modules/requisitions/list.php'],
    'Admin, Department Head',
    'The starting point of the requisition workflow; does not itself touch the assets table.',
    'HIGH',
    'None identified.')
file_entry('modules/requisitions/list.php',
    'Filterable list of requisitions and their status.',
    'Requisitions module — list view.',
    ['Filter by status', 'Department-scoped for Department Head'],
    ['includes/bootstrap.php', 'Tables: requisitions, departments, categories, users'],
    ['includes/layout/sidebar.php'],
    'All (Admin/Officer review; Department Head submits+views own; Top Management view-only)',
    'The queue Asset Officers/Admin work from when deciding what to approve.',
    'HIGH',
    'The search query joining `u.name LIKE :q OR u.email LIKE :q OR u.username LIKE :q` for a very similar '
    'search box exists on modules/users/list.php, not here — see that entry for the still-open bug.')
file_entry('modules/requisitions/review.php',
    'Moves a requisition through its status workflow: Pending &rarr; Approved/Rejected, Approved &rarr; Issued.',
    'Requisitions module — review/decision.',
    ['$allowedTransitions map strictly enforces which decision is valid for the current status; anything '
     'else is rejected with "Invalid action for the current status of this requisition."'],
    ['includes/bootstrap.php', 'Tables: requisitions'],
    ['modules/requisitions/list.php ("Review" / "Mark Issued" links)'],
    'Admin, Asset Officer',
    'The decision point of the requisition workflow.',
    'HIGH',
    'Unlike Disposals, an Asset Officer can approve/reject/issue a requisition without any Department Head '
    'or Top Management sign-off — a different, less strict governance model than Disposals. This appears '
    'to be an intentional design choice (requisitions are lower-stakes than write-offs) but is worth '
    'confirming against the intended business process — see Part 13.')

h2('2.14 modules/categories/, modules/departments/, modules/locations/ — Master Data')
file_entry('modules/categories/list.php',
    'Full CRUD for asset categories in a single file, using modal dialogs for create/edit.',
    'Master data module.',
    ['create/update/delete actions dispatched by a hidden `action` POST field', 'Delete wrapped in try/catch '
     'so an FK-constrained row (assets still referencing it) fails gracefully with a friendly flash message '
     'instead of a raw SQL error'],
    ['includes/bootstrap.php', 'Tables: categories, assets (for the per-row asset_count subquery)'],
    ['includes/layout/sidebar.php (Administration section, Admin only)'],
    'Admin only',
    'Feeds the Category dropdown used by Assets and Requisitions.',
    'MEDIUM',
    'None identified.')
file_entry('modules/departments/list.php',
    'Full CRUD for departments, including assigning a Head of Department.',
    'Master data module.',
    ['Same create/update/delete pattern as categories/list.php', 'Head-of-Department dropdown sourced from '
     '`users WHERE role_id IN (SELECT ... WHERE role_name = \'Department Head\')`'],
    ['includes/bootstrap.php', 'Tables: departments, users, assets (asset_count subquery)'],
    ['includes/layout/sidebar.php (Admin only)'],
    'Admin only',
    'Defines the department_id every other module scopes a Department Head\'s view by.',
    'CRITICAL',
    'None identified — though because department_id drives every single "$isHead" scoping check across the '
    'entire application, any data error introduced here has the widest possible blast radius of any master '
    'data module.')
file_entry('modules/locations/list.php',
    'Full CRUD for physical building/room locations.',
    'Master data module.',
    ['Same create/update/delete modal pattern as the other two master-data modules'],
    ['includes/bootstrap.php', 'Tables: locations, assets (asset_count subquery)'],
    ['includes/layout/sidebar.php (Admin only)'],
    'Admin only',
    'Feeds the Location dropdown used by the Assets module.',
    'MEDIUM',
    'None identified.')

h2('2.15 modules/users/ — Account Management')
file_entry('modules/users/add.php',
    'Creates a new user account (the only way new accounts are created — there is no public sign-up).',
    'User administration module.',
    ['Validates name/email/username/role', 'Uniqueness checks on email and username (separate SELECT 1 '
     'queries)', 'password_hash() for the temporary password'],
    ['includes/bootstrap.php', 'Tables: users, roles, departments'],
    ['modules/users/list.php'],
    'Admin only',
    'Provisions every account other than the ones in the seed data.',
    'CRITICAL',
    'None identified.')
file_entry('modules/users/edit.php',
    'Edits a user\'s profile fields, role, department, and optionally resets their password.',
    'User administration module.',
    ['Same validation/uniqueness pattern as add.php', 'Password field is optional — blank leaves the '
     'existing hash untouched'],
    ['includes/bootstrap.php', 'Tables: users, roles, departments'],
    ['modules/users/list.php ("Edit")'],
    'Admin only',
    'The mechanism by which an Admin can promote/demote a role or move someone between departments.',
    'CRITICAL',
    'None identified.')
file_entry('modules/users/list.php',
    'Searchable, filterable list of every user account with Deactivate/Reactivate actions.',
    'User administration module.',
    ['Search across name/email/username', 'Filter by role'],
    ['includes/bootstrap.php', 'Tables: users, roles, departments'],
    ['includes/layout/sidebar.php (Admin only)'],
    'Admin only',
    'The operational hub for account management.',
    'CRITICAL',
    'The search clause `(u.name LIKE :q OR u.email LIKE :q OR u.username LIKE :q)` reuses the same named '
    'placeholder `:q` three times in one query. Under this project\'s PDO configuration '
    '(EMULATE_PREPARES = false, in config/database.php), a repeated named placeholder is invalid and throws '
    '`SQLSTATE[HY093]: Invalid parameter number` — meaning searching the Users list currently crashes with '
    'an HTTP 500 for any non-empty search term. This is the same bug class already found and fixed in '
    'modules/auth/login.php and modules/assets/list.php, but this occurrence is still present. '
    'See Part 13 for the concrete fix.')
file_entry('modules/users/deactivate.php',
    'POST-only endpoint toggling a user between active/inactive.',
    'User administration module — state transition.',
    ['Refuses to let an Admin deactivate their own account'],
    ['includes/bootstrap.php', 'Tables: users'],
    ['modules/users/list.php (per-row Deactivate/Reactivate form)'],
    'Admin only',
    'An inactive account is refused at login (see modules/auth/login.php) without deleting any of its history.',
    'HIGH',
    'None identified.')

h2('2.16 modules/profile/')
file_entry('modules/profile/index.php',
    'Self-service page for any logged-in user: edit their own name/email/username/profile picture, and '
    'change their own password.',
    'Self-service account module.',
    ['Two independent forms distinguished by a `form_type` hidden field (profile vs. password)',
     'Profile-picture upload: extension whitelist (jpg/jpeg/png/gif) + 2MB size cap + random filename via '
     'bin2hex(random_bytes(6)); old file deleted on replace/remove', 'Password change requires the correct '
     'current password'],
    ['includes/bootstrap.php', 'Tables: users'],
    ['includes/layout/header.php (the "My Profile" link in every page\'s user menu)'],
    'All (each user can only edit their own account)',
    'The only place a non-Admin can change any of their own account details.',
    'HIGH',
    'File-upload validation checks extension and size but not actual image content (e.g. via getimagesize() '
    'or finfo mime-type detection); see Part 8 for the associated risk and recommended fix.')

h2('2.17 modules/reports/')
file_entry('modules/reports/index.php',
    'The on-screen Reports UI: five report types, each with a clickable Asset Count that opens the exact '
    'underlying asset records in a modal.',
    'Reporting module — on-screen view.',
    ['Five report branches (by_department/by_category/by_status/maintenance_cost/disposals), each pairing '
     'an aggregate GROUP BY query with a matching per-row detail query scoped identically',
     'renderAssetCountCell()/renderAssetCountTrigger()/renderAssetDetailModal() — local helpers building the '
     'clickable-count modal UI', 'The "Assets by Department" report additionally makes the whole table row '
     'clickable (not just the count), with its modals rendered after the table rather than nested inside '
     'the row it belongs to, specifically to avoid a close-button/re-open interaction bug'],
    ['includes/bootstrap.php', 'Tables: departments, categories, assets, locations, asset_maintenance, '
     'asset_disposals'],
    ['includes/layout/sidebar.php'],
    'All (Admin/Officer full; Department Head department-scoped; Top Management full, view-only)',
    'The system\'s analytics surface; every number here is a live query, and the detail modal is proven (by '
    'construction) to reflect the exact same rows the count was computed from, not a separate "first N" query.',
    'HIGH',
    'None identified in the current version — an earlier iteration nested each modal inside its own '
    'trigger row, which would have made the modal\'s own close button re-trigger the row\'s click handler '
    'and immediately reopen it; this was identified and restructured before shipping.')
file_entry('modules/reports/export_csv.php',
    'Streams any of the five reports as a CSV download.',
    'Reporting module — export.',
    ['Delegates entirely to includes/functions.php: getReportRows()', 'fputcsv() to php://output'],
    ['includes/bootstrap.php', 'includes/functions.php'],
    ['modules/reports/index.php ("Export CSV" button, carries the current filters via the query string)'],
    'Same as modules/reports/index.php',
    'Offline/spreadsheet consumption of report data.',
    'MEDIUM',
    'None identified.')
file_entry('modules/reports/export_pdf.php',
    'Streams any of the five reports as a formatted PDF download.',
    'Reporting module — export.',
    ['Delegates data to includes/functions.php: getReportRows()', 'ReportPDF extends FPDF, with a branded '
     'Header()/Footer() and a fitText() helper that truncates any cell text with an ellipsis so it can never '
     'overflow a fixed-width column', 'Auto-selects Landscape orientation once a report has 4+ columns'],
    ['includes/bootstrap.php', 'includes/functions.php', 'vendor/fpdf/fpdf.php'],
    ['modules/reports/index.php ("Export PDF" button)'],
    'Same as modules/reports/index.php',
    'Printable/archivable report output, without any dependency beyond the vendored FPDF library.',
    'MEDIUM',
    'None identified.')

h2('2.18 modules/settings/ — Admin Configuration')
file_entry('modules/settings/index.php',
    'General system settings: university/system name, academic year, language, timezone, date format, '
    'session timeout, records-per-page, theme, logo upload, and the maintenance-mode switch.',
    'Settings module — General tab.',
    ['Validates + saves each field via setSetting()', 'Logo upload: same extension/size validation pattern '
     'as the profile-picture uploader'],
    ['includes/bootstrap.php', 'includes/i18n.php', 'modules/settings/_subnav.php', 'Table: settings'],
    ['includes/layout/sidebar.php (Admin only)'],
    'Admin only',
    'The single source for every getSetting() call made anywhere else in the app (university name, theme, '
    'language, records-per-page, maintenance mode, logo).',
    'CRITICAL',
    'Two settings saved here are not actually enforced anywhere else in the codebase: '
    '`session_timeout_minutes` (the real session cookie lifetime is fixed in config/config.php, read before '
    'this setting could apply) and, more broadly, changes to it have no observable effect. See Part 13.')
file_entry('modules/settings/smtp.php',
    'Collects SMTP host/port/username/password/encryption for "outgoing notification emails."',
    'Settings module — Email tab.',
    ['Validates port is numeric', 'Saves each field via setSetting(); password only overwritten if a new '
     'one is entered'],
    ['includes/bootstrap.php', 'modules/settings/_subnav.php', 'Table: settings'],
    ['includes/layout/sidebar.php (Admin only)'],
    'Admin only',
    'Intended to back an email-notification feature.',
    'MEDIUM',
    ['A codebase-wide search finds no PHPMailer/SwiftMailer/mail() call or any other consumer of these '
     'settings anywhere in the project — these credentials are saved but never used to send anything. The '
     'page\'s own help text ("Used for outgoing notification emails, e.g. maintenance updates, requisition '
     'decisions") describes a capability that does not exist yet. See Part 13.'])
file_entry('modules/settings/backup.php',
    'Landing page for database backup/restore: shows DB size/table count, and links to the download and '
    'restore actions.',
    'Settings module — Backup &amp; Restore tab.',
    ['`SELECT SUM(data_length+index_length) ... information_schema.TABLES` for the size stat'],
    ['includes/bootstrap.php', 'modules/settings/_subnav.php'],
    ['modules/settings/backup_download.php', 'modules/settings/backup_restore.php'],
    'Admin only',
    'Entry point for the one genuinely destructive workflow in the whole system.',
    'HIGH',
    'None in this file itself — see backup_restore.php for the associated critical-severity risk.')
file_entry('modules/settings/backup_download.php',
    'Streams a complete .sql dump of the live database (schema + all data), generated in pure PHP.',
    'Settings module — Backup &amp; Restore tab, download action.',
    ['SHOW TABLES, then per table: SHOW CREATE TABLE + SELECT * with hand-built INSERT statements '
     '($pdo->quote() used for every value, so this is not raw string concatenation of untrusted input)'],
    ['includes/bootstrap.php'],
    ['modules/settings/backup.php ("Download Backup" button)'],
    'Admin only',
    'The system\'s only backup mechanism (no scheduled/automatic backup job exists anywhere in the codebase).',
    'HIGH',
    'The generated dump includes every user\'s bcrypt password hash and the raw smtp_password setting value '
    '(if one has been set) in plain INSERT statements — reasonable for a database backup, but it means the '
    'downloaded file itself becomes sensitive and must be stored as carefully as the live database.')
file_entry('modules/settings/backup_restore.php',
    'Accepts an uploaded .sql file and executes its contents directly against the live database.',
    'Settings module — Backup &amp; Restore tab, restore action.',
    ['Validates extension is .sql and size &le; 50MB', '`$pdo->exec($sql)`, where $sql is the entire raw '
     'uploaded file content'],
    ['includes/bootstrap.php'],
    ['modules/settings/backup.php (the Restore form)'],
    'Admin only',
    'A full-database overwrite mechanism.',
    'CRITICAL',
    ['This executes arbitrary, attacker-or-mistake-controlled SQL directly against the production database '
     'with no dry-run, no automatic safety backup taken first, and no statement-level filtering — the only '
     'gate is the file extension and an Admin-role check. Any admin session (whether the legitimate admin or '
     'an attacker who has compromised that session, e.g. via a stolen cookie or CSRF if the token were ever '
     'missing) can drop/recreate/repopulate every table in one request. See Part 8 for the full write-up.'])
file_entry('modules/settings/logs.php',
    'Paginated, module-filterable view of the system-wide activity_logs table.',
    'Settings module — Activity Logs tab.',
    ['Bound LIMIT/OFFSET pagination (25/page)'],
    ['includes/bootstrap.php', 'modules/settings/_subnav.php', 'Table: activity_logs'],
    ['includes/layout/sidebar.php (Admin only)'],
    'Admin only',
    'The audit trail for every logActivity() call made anywhere in the system (every module calls it after '
    'a create/update/delete/state-change).',
    'HIGH',
    'None identified.')
file_entry('modules/settings/login_logs.php',
    'Paginated, status-filterable view of every login attempt, successful or failed.',
    'Settings module — Login Logs tab.',
    ['Bound LIMIT/OFFSET pagination (25/page)'],
    ['includes/bootstrap.php', 'modules/settings/_subnav.php', 'Table: login_logs'],
    ['includes/layout/sidebar.php (Admin only)'],
    'Admin only',
    'The forensic trail for investigating unauthorized-access attempts — every logLogin() call from '
    'modules/auth/login.php lands here.',
    'HIGH',
    'None identified — though see Part 8: nothing currently acts on repeated failures recorded here (no '
    'automatic lockout), so it is presently observational only.')
file_entry('modules/settings/system_info.php',
    'Read-only environment snapshot: PHP/MySQL version, server software, OS, disk space.',
    'Settings module — System Info tab.',
    ['PHP_VERSION/PHP_OS constants', "`SELECT VERSION()`", 'disk_free_space()/disk_total_space()',
     'Local humanSize() helper'],
    ['includes/bootstrap.php', 'modules/settings/_subnav.php'],
    ['includes/layout/sidebar.php (Admin only)'],
    'Admin only',
    'A diagnostic page for technical support/evaluation, with no write operations at all.',
    'LOW',
    'None identified.')
file_entry('modules/settings/_subnav.php',
    'The shared sub-navigation tab strip rendered at the top of every Settings page.',
    'Settings module — shared view partial.',
    ['$settingsTabs array driving the tab links'],
    ['includes/i18n.php (t())'],
    ['Every modules/settings/*.php file except backup_download.php/backup_restore.php (which are pure '
     'redirect/stream endpoints with no HTML output)'],
    'Admin only',
    'Keeps the six Settings pages visually and structurally consistent.',
    'LOW',
    'None identified.')

h2('2.19 static/ — Frontend Assets')
file_entry('static/css/style.css',
    'The entire visual theme: layout, components, and both the light and dark color schemes.',
    'Frontend presentation layer.',
    ['CSS custom properties (:root) for the light palette, re-pointed under `body.theme-dark` for the dark '
     'palette — components reference the variables, not hardcoded colors, so both themes are produced by '
     'one shared rule set', 'Sidebar/topbar/cards/tables/forms/badges/modals/tabs/pagination/auth-page/'
     'dashboard-insight/report-modal component styles', 'Two responsive breakpoints (992px, 600px)'],
    ['Google Fonts "Inter" (loaded via a &lt;link&gt; tag in header.php, not bundled locally)'],
    ['includes/layout/header.php and modules/auth/login.php (both load it with a filemtime() cache-busting '
     'query string)'],
    'All',
    'Every page in the system shares this one stylesheet — there is no per-module CSS.',
    'HIGH',
    ['The dark theme is implemented by re-defining the shared CSS custom properties under `body.theme-dark` '
     '(rather than one override rule per component), so any new component automatically supports both themes '
     'without further CSS work — a deliberate architectural choice made after an earlier version of the dark '
     'theme used per-component overrides with stale, pre-redesign colors.'])
file_entry('static/js/main.js',
    'All shared client-side interactivity: sidebar toggle, dropdown menus, generic modal open/close, '
    'destructive-action confirmations, client-side table search/sort, and tab switching.',
    'Frontend behavior layer.',
    ['Entirely attribute-driven (`data-dropdown-toggle`, `data-modal-target`, `data-modal-close`, '
     '`data-confirm`, `data-table-search`, `data-sort`, `.tab-link`) — no per-page custom JS exists anywhere '
     'in the project', 'One generic click-outside-closes-dropdown handler; one generic modal system reused '
     'by every module that has modals (categories/departments/locations CRUD, reports asset-count detail)'],
    ['None (vanilla JS, no build step, no framework)'],
    ['includes/layout/footer.php (loaded on every authenticated page with a cache-busting query string)'],
    'All',
    'Every interactive widget in the authenticated app depends on this one file.',
    'HIGH',
    'None identified — the attribute-driven, single-shared-script approach is consistently used everywhere; '
    'no module was found to define its own bespoke inline &lt;script&gt; interactivity outside of this file.')
file_entry('static/js/validation.js',
    'Client-side form validation: required fields, email format, numeric min/max, minlength.',
    'Frontend behavior layer — UX convenience only.',
    ['validateField()/showError()/clearError()', 'Wires every &lt;form&gt; on the page automatically on submit and '
     'on blur'],
    ['None'],
    ['modules/auth/login.php (the only page observed to load it directly instead of relying on footer.php)'],
    'All',
    'The file\'s own header comment states its role precisely: "This is a UX convenience layer only — every '
    'form is re-validated server-side in PHP before touching the database" — confirmed true by inspecting '
    'every module\'s POST handler, which all re-run validateRequired() and their own checks regardless of '
    'this script.',
    'MEDIUM',
    'None identified.')
file_entry('static/images/logo.webp',
    'The bundled default university crest/logo.',
    'Frontend asset.',
    ['Served as-is; not processed by PHP'],
    ['None'],
    ['includes/functions.php: appLogoUrl() falls back to this file whenever no admin-uploaded logo '
     '(settings.logo_path) is set'],
    'All',
    'Brand identity shown in the sidebar, browser tab icon, and login page whenever no custom logo has been '
    'uploaded.',
    'LOW',
    'None identified.')

h2('2.20 vendor/fpdf/')
file_entry('vendor/fpdf/fpdf.php (+ font/*.json, LICENSE.txt)',
    'A hand-vendored copy of the FPDF library (no Composer/package manager used anywhere in this project).',
    'Third-party dependency — the only one in the codebase.',
    ['Standard FPDF class API (AddPage/Cell/SetFont/SetFillColor/Output/etc.); the 14 core-font metric files '
     'in font/ back Helvetica/Times/Courier/Symbol/ZapfDingbats in regular/bold/italic/bold-italic'],
    ['None (self-contained; no Composer autoloader involved)'],
    ['modules/reports/export_pdf.php (the only consumer in the project)'],
    'Same as modules/reports/export_pdf.php',
    'The PDF-rendering engine behind Reports &rarr; Export PDF.',
    'MEDIUM',
    'None identified — vendoring by hand (rather than via Composer) is consistent with the rest of the '
    'project\'s "no framework, no package manager" approach, and keeps the dependency footprint to exactly '
    'one library used for exactly one feature.')

h2('2.21 docs/')
file_entry('docs/build_pdf.py',
    'Generates the existing end-user/admin functional manual, University_Asset_Management_System_'
    'Documentation.pdf, using reportlab.',
    'Documentation tooling (not part of the running web application).',
    ['Data-driven module_section()/data_table()/module_header() helpers producing a full user manual with a '
     'cover page, table of contents, and one detailed section per module'],
    ['Python: reportlab', 'A hardcoded absolute path to a logo image'],
    ['Run manually/offline by a developer; not invoked by any PHP code'],
    'Developer/documentation maintainer only',
    'Produces the companion functional manual referenced throughout this document.',
    'LOW',
    ['LOGO is a hardcoded absolute Windows path, `C:\\xampp\\htdocs\\asm\\storage\\logo_converted.png`, '
     'which does not currently exist in the repository&rsquo;s storage/ directory (only `.gitkeep` and '
     '`php-error.log` are present there) — re-running this script in the current state of the repository '
     'would fail on the Image() call. This makes the script currently non-portable and, as of this analysis, '
     'not runnable without first restoring or re-pointing that logo file.'])
p('The remaining files — README.md and .gitignore at the project root, and .gitkeep placeholders inside '
  'storage/, uploads/logos/, and uploads/avatars/ — are documentation/repository-hygiene files with no '
  'runtime behavior. README.md is analyzed for accuracy in Part 13; .gitignore correctly excludes '
  'runtime-generated content (error logs, uploaded logos/avatars) while preserving the directories '
  'themselves via .gitkeep, which was verified directly (uploaded files are excluded; the directory '
  'placeholders are tracked).')
story.append(PageBreak())

# ==================================================================
# PART 3 — MODULE ANALYSIS
# ==================================================================
h1('Part 3 — Module Analysis')
p('This part explains what each module contributes to the system as a whole, and traces the real, '
  'code-verified relationships between the files inside it — including where the actual implementation '
  'differs from the "textbook" asset-lifecycle flow.')

h2('3.1 Assets Module')
p('modules/assets/list.php, add.php, edit.php, view.php, and _form.php together form the master registry. '
  'add.php and edit.php both include the same _form.php partial (the one deliberate view-partial reuse in '
  'the codebase), so the create and edit forms cannot drift out of sync with each other. view.php is the '
  'hub: it independently queries all five history tables (asset_assigned, asset_transfers, '
  'asset_maintenance, asset_audits, asset_disposals) filtered to one asset_id, which is how every other '
  'module\'s history becomes visible without those modules needing to know about each other.')
h3('The Real Asset Lifecycle (as implemented, not assumed)')
diagram(
"Create Asset (assets/add.php)\n"
"        |  INSERT INTO assets\n"
"        v\n"
"Asset Record exists, status = 'active' by default\n"
"        |\n"
"        +--> Assign Asset (assigned/add.php)      -- asset_assigned row + assets.department_id updated\n"
"        |         |\n"
"        |         +--> Mark Returned / Send to Repair (assigned/return.php) -- closes the allocation row\n"
"        |               (does NOT change assets.status by itself)\n"
"        |\n"
"        +--> Transfer Asset (transfers/add.php)   -- asset_transfers row + assets.department_id updated\n"
"        |\n"
"        +--> Report Maintenance Issue (maintenance/add.php)\n"
"        |         |  INSERT asset_maintenance (status='pending')\n"
"        |         v\n"
"        |     recomputeAssetStatus() --> assets.status = 'under_repair'\n"
"        |         |\n"
"        |         v\n"
"        |     Update Maintenance (maintenance/update.php) status -> 'completed'\n"
"        |         |\n"
"        |         v\n"
"        |     recomputeAssetStatus() --> assets.status back to 'active' (if no other open tickets\n"
"        |                                and no approved disposal)\n"
"        |\n"
"        +--> Record Audit (audits/add.php)         -- asset_audits row only; never touches assets.status\n"
"        |\n"
"        +--> Request Disposal (disposals/add.php)  -- asset_disposals row, status='pending'\n"
"                  |\n"
"                  v\n"
"              Approve/Reject Disposal (disposals/approve.php) -- Admin / Top Management only\n"
"                  |  on 'approved':\n"
"                  v\n"
"              recomputeAssetStatus() --> assets.status = 'disposed'  (this always wins over\n"
"                                          'under_repair' -- see includes/functions.php)"
)
p('Two points where the real flow differs from the "textbook" '
  'Create&nbsp;&rarr;&nbsp;Assign&nbsp;&rarr;&nbsp;Transfer&nbsp;&rarr;&nbsp;Maintain&nbsp;&rarr;&nbsp;Audit'
  '&nbsp;&rarr;&nbsp;Dispose sequence implied by a typical sidebar ordering: Audits never change '
  'assets.status at all (confirmed by reading modules/audits/add.php — a "damaged"/"missing" result is '
  'purely informational unless a human separately opens a maintenance ticket or disposal request based on '
  'it), and assets.status is not a simple state machine walked in order — it is recomputed from scratch '
  'every time (recomputeAssetStatus() in includes/functions.php), checking "is there an approved disposal?" '
  'first, then "is there an open maintenance ticket?", defaulting to active otherwise. An asset can be '
  'assigned, transferred, and audited any number of times, in any order, without ever affecting its status.')

h2('3.2 Authentication Module')
p('modules/auth/login.php and logout.php, backed entirely by includes/auth.php for the session/role guard '
  'functions used everywhere else. There is no registration page and no self-service password reset '
  'anywhere in the codebase — every account is created by an Admin via modules/users/add.php.')

h2('3.3 Users Module')
p('modules/users/add.php, edit.php, list.php, and deactivate.php form a complete CRUD+state-toggle set, '
  'Admin-only. Deactivating a user (deactivate.php) does not delete anything — it flips users.status to '
  '\'inactive\', which modules/auth/login.php then checks and refuses even with a correct password. This '
  'preserves every historical record (activity_logs, asset_assigned.assigned_by, etc.) that references the '
  'user\'s user_id, which a hard delete would either break or cascade-destroy.')

h2('3.4 Departments, Categories, Locations Modules')
p('modules/departments/list.php, modules/categories/list.php, and modules/locations/list.php are three '
  'structurally identical Admin-only CRUD modules (same create/update/delete-via-modal pattern, same '
  'try/catch around DELETE to turn a foreign-key violation into a friendly flash message instead of a raw '
  'SQL error). departments/list.php is the most consequential of the three: department_id, set here, is the '
  'value every "$isHead" department-scoping check across the entire application compares against.')

h2('3.5 Requisitions Module')
diagram(
"Submit Requisition (requisitions/add.php)  -- Admin or Department Head\n"
"        |  INSERT requisitions (status='pending')\n"
"        v\n"
"Review (requisitions/review.php)  -- Admin or Asset Officer\n"
"        |\n"
"        +--> decision='approved' --------+--> decision='rejected'\n"
"        |         |                              |\n"
"        |         v                              v\n"
"        |   status='approved'              status='rejected'  (dead end --\n"
"        |         |                         'No further action available')\n"
"        |         v\n"
"        |   Mark Issued (requisitions/review.php, same file, decision='issued')\n"
"        |         |\n"
"        |         v\n"
"        |   status='issued'  (dead end)\n"
"\n"
"Enforced by $allowedTransitions in review.php: pending -> {approved, rejected} only;\n"
"approved -> {issued} only. Any other transition is rejected server-side."
)
p('A requisition never creates an assets row automatically — "Issued" only records that the department\'s '
  'need has been fulfilled; if new equipment was purchased to satisfy it, an Asset Officer/Admin registers '
  'it separately via modules/assets/add.php. Confirmed: no INSERT INTO assets exists anywhere in the '
  'requisitions module.')

h2('3.6 Disposals Module')
diagram(
"Request Disposal (disposals/add.php)  -- Admin or Asset Officer\n"
"        |  INSERT asset_disposals (status='pending')\n"
"        v\n"
"Review (disposals/approve.php)  -- Admin or Top Management ONLY (not the requester)\n"
"        |\n"
"        +--> decision='approved'                    +--> decision='rejected'\n"
"        |         |                                          |\n"
"        |         v                                          v\n"
"        |   status='approved', disposal_date=today      status='rejected'\n"
"        |         |\n"
"        |         v\n"
"        |   recomputeAssetStatus() --> assets.status = 'disposed'\n"
"\n"
"Separation of duties, confirmed in code: the Asset Officer role that can submit a disposal\n"
"request is explicitly excluded from requireRole([ROLE_ADMIN, ROLE_TOPMGMT]) in approve.php."
)

h2('3.7 Maintenance Module')
diagram(
"Report Issue (maintenance/add.php)  -- Admin, Officer, or Department Head (own dept only)\n"
"        |  INSERT asset_maintenance (status='pending')\n"
"        v\n"
"        recomputeAssetStatus() --> assets.status usually becomes 'under_repair'\n"
"        v\n"
"Update (maintenance/update.php)  -- Admin or Officer only\n"
"        |  status: pending -> in_progress -> completed\n"
"        |  (completed requires a Completed Date; Cost/Technician optional at any stage)\n"
"        v\n"
"        recomputeAssetStatus() runs again after every save\n"
"        --> once every open ticket for the asset is completed, status returns to 'active'\n"
"            (unless an approved disposal exists, which always takes priority)"
)

h2('3.8 Transfers Module')
p('modules/transfers/add.php and list.php. A transfer is a lighter-weight action than an allocation: it '
  'simply moves assets.department_id and logs the move, with no separate "return" concept. Guarded against '
  'two specific mistakes at the database-query level: transferring an already-disposed asset, and '
  '"transferring" an asset to the department it is already in.')

h2('3.9 Reports Module')
p('modules/reports/index.php (on-screen), export_csv.php, and export_pdf.php. index.php contains its own '
  'query logic for the on-screen view (including the per-row asset-detail queries that back the clickable '
  'Asset Count modals); export_csv.php and export_pdf.php both instead call the single shared '
  'includes/functions.php: getReportRows() — meaning the two export formats are guaranteed to return '
  'identical data for identical filters, since they are, literally, the same function call.')

h2('3.10 Settings Module')
p('Six admin-only pages sharing modules/settings/_subnav.php: General (index.php), Email/SMTP (smtp.php), '
  'Backup & Restore (backup.php + backup_download.php + backup_restore.php), Activity Logs (logs.php), '
  'Login Logs (login_logs.php), and System Info (system_info.php). These are independent of each other '
  'except that every other module in the system reads values that General writes, via getSetting().')

h2('3.11 Dashboard Module')
p('A single file, modules/dashboard/index.php, that queries six independent counts/breakdowns and a '
  '12-row recent-activity feed, all scoped the same way every list page is scoped ($isHead / '
  'department_id). It writes nothing to the database — a pure read/aggregate view.')

h2('3.12 Profile Module')
p('A single file, modules/profile/index.php, that is the only place in the system where a non-Admin user '
  'can change anything about their own account (name/email/username/profile picture/password). It reuses '
  'the exact same email/username uniqueness-check pattern as modules/users/edit.php, applied to the '
  'currently-logged-in user\'s own row instead of an arbitrary one.')
story.append(PageBreak())

# ==================================================================
# PART 4 — DATABASE RELATIONSHIPS
# ==================================================================
h1('Part 4 — Database Relationships')
p('All 15 tables, their primary keys, and every foreign key relationship, read directly from '
  'database/schema.sql.')

data_table(
    ['Table', 'Primary Key', 'Foreign Keys (-&gt; Referenced Table, ON DELETE rule)'],
    [
        ['roles', 'role_id', '(none — referenced by users.role_id)'],
        ['departments', 'department_id', 'head_id -&gt; users (SET NULL) &mdash; added via ALTER TABLE after users exists'],
        ['users', 'user_id', 'role_id -&gt; roles (RESTRICT); department_id -&gt; departments (SET NULL)'],
        ['categories', 'category_id', '(none)'],
        ['locations', 'location_id', '(none)'],
        ['assets', 'asset_id', 'category_id -&gt; categories (RESTRICT); location_id -&gt; locations (SET NULL); '
                                'department_id -&gt; departments (SET NULL)'],
        ['asset_assigned', 'assign_id', 'asset_id -&gt; assets (CASCADE); assigned_to -&gt; departments (RESTRICT); '
                                         'assigned_user_id -&gt; users (SET NULL); assigned_by -&gt; users (RESTRICT)'],
        ['asset_transfers', 'transfer_id', 'asset_id -&gt; assets (CASCADE); from_department_id -&gt; departments '
                                            '(SET NULL); to_department_id -&gt; departments (RESTRICT); '
                                            'handled_by -&gt; users (RESTRICT)'],
        ['asset_maintenance', 'maintenance_id', 'asset_id -&gt; assets (CASCADE); reported_by -&gt; users (RESTRICT)'],
        ['asset_audits', 'audit_id', 'asset_id -&gt; assets (CASCADE); audited_by -&gt; users (RESTRICT)'],
        ['requisitions', 'requisition_id', 'department_id -&gt; departments (CASCADE); requester_id -&gt; users '
                                            '(RESTRICT); category_id -&gt; categories (SET NULL); '
                                            'reviewed_by -&gt; users (SET NULL)'],
        ['asset_disposals', 'disposal_id', 'asset_id -&gt; assets (CASCADE); requested_by -&gt; users (RESTRICT); '
                                            'approved_by -&gt; users (SET NULL)'],
        ['settings', 'setting_key (string)', '(none)'],
        ['activity_logs', 'log_id', 'user_id -&gt; users (SET NULL)'],
        ['login_logs', 'login_log_id', 'user_id -&gt; users (SET NULL)'],
    ], col_widths=[3.2 * cm, 2.6 * cm, 11 * cm]
)
p('Status fields, confirmed by ENUM definition: users.status (active/inactive); assets.status (active/'
  'under_repair/disposed — derived, see Part 3.1); asset_assigned.status (active/returned/repair); '
  'asset_maintenance.status (pending/in_progress/completed); asset_audits.result (found/missing/damaged); '
  'requisitions.status (pending/approved/rejected/issued); asset_disposals.status (pending/approved/'
  'rejected); login_logs.status (success/failed).')

h3('Relationship Map (as implemented)')
diagram(
"roles ----------< users >------------------ departments >---- head_id (self-referential\n"
"                    |                             |                    back to users)\n"
"                    |                             |\n"
"                    | (author/actor FK on          | (department_id scoping FK on nearly\n"
"                    |  almost every table below)    |  every table below)\n"
"                    v                             v\n"
"     +----------------------------------------------------------------+\n"
"     |                                                                |\n"
"     v                                                                v\n"
"categories >---+                                              locations >---+\n"
"               |                                                            |\n"
"               v                                                            v\n"
"             assets  <-------------------------------------------------------\n"
"               |  ^  ^   ^    ^     ^\n"
"               |  |  |   |    |     |\n"
"      +--------+  |  |   |    |     +----------------+\n"
"      |           |  |   |    |                      |\n"
"      v           |  |   |    v                      v\n"
"asset_assigned     |  |   |  asset_audits      asset_disposals >--- requested_by / approved_by -> users\n"
"  (assigned_to,    |  |   |  (audited_by -> users)         ^\n"
"   assigned_user_id,| |   |                                 |\n"
"   assigned_by      | |   |                          asset_disposals.status='approved'\n"
"   -> users/depts)  | |   |                          is what recomputeAssetStatus() checks\n"
"                    | |   |                          FIRST when deriving assets.status\n"
"          asset_transfers |\n"
"          (from/to_department_id -> departments,      asset_maintenance\n"
"           handled_by -> users)                       (reported_by -> users;\n"
"                                                        open tickets are what\n"
"                                                        recomputeAssetStatus() checks\n"
"                                                        SECOND)\n"
"\n"
"requisitions (department_id -> departments, requester_id / reviewed_by -> users,\n"
"              category_id -> categories) -- NOT linked to assets at all; a requisition\n"
"              is a request, never a direct row-level pointer to an asset record.\n"
"\n"
"settings, activity_logs, login_logs -- system/audit tables; activity_logs and login_logs\n"
"              both point back to users.user_id (SET NULL if the user is later removed)."
)
p('One relationship worth calling out explicitly because it is easy to assume otherwise: '
  '<b>requisitions has no foreign key to assets at all.</b> It only ever points to departments, users, and '
  'categories. The connection between "a requisition was issued" and "an asset now exists" is a manual, '
  'human step (a separate modules/assets/add.php submission), not an enforced database relationship — '
  'confirmed by inspecting both the requisitions table definition and every requisitions/*.php file.')
story.append(PageBreak())

# ==================================================================
# PART 5 — ROLE & PERMISSION ANALYSIS
# ==================================================================
h1('Part 5 — Role &amp; Permission Analysis')
p('Exactly four roles exist, defined as constants in includes/auth.php: <b>Admin</b>, <b>Asset Officer</b>, '
  '<b>Department Head</b>, and <b>Top Management</b>. Every restriction below was read directly from the '
  '`requireRole([...])` / `hasRole([...])` / `$isHead` checks in each module\'s own PHP file — not inferred '
  'from the sidebar.')

h3('5.1 Per-Role Summary')
data_table(
    ['Role', 'Can See', 'Can Create', 'Can Edit', 'Can Approve', 'Can Delete/Deactivate', 'Reports', 'Settings'],
    [
        ['Admin', 'Everything, system-wide', 'Assets, allocations, transfers, maintenance, audits, '
         'requisitions (any dept.), disposals, users, master data', 'Everything', 'Requisitions, disposals',
         'Users (deactivate); categories/departments/locations (delete)', 'Full, all data', 'Full'],
        ['Asset Officer', 'Everything, system-wide', 'Assets, allocations, transfers, maintenance, audits, '
         'disposal requests', 'Assets, maintenance tickets', 'Requisitions (not disposals — cannot approve '
         'their own request)', '&mdash;', 'Full, all data', '&mdash;'],
        ['Department Head', 'Own department only (assets, allocations, transfers as source/destination, '
         'maintenance, audits, requisitions, disposals)', 'Maintenance issue reports; requisitions (own '
         'department)', '&mdash; (view-only outside of reporting issues/submitting requisitions)', '&mdash;',
         '&mdash;', 'Own department scope', '&mdash;'],
        ['Top Management', 'Everything, system-wide, read-only (except disposals)', '&mdash;', '&mdash;',
         'Disposals only', '&mdash;', 'Full, all data, view-only', '&mdash;'],
    ], col_widths=[2.6 * cm, 3.6 * cm, 3.6 * cm, 2.0 * cm, 2.0 * cm, 2.0 * cm]
)

h3('5.2 Permission Matrix')
data_table(
    ['Feature', 'Admin', 'Asset Officer', 'Department Head', 'Top Management'],
    [
        ['Assets', 'Full', 'Full', 'View (own dept.)', 'View (all)'],
        ['Users', 'Full', '&mdash;', '&mdash;', '&mdash;'],
        ['Departments / Categories / Locations', 'Full', '&mdash;', '&mdash;', '&mdash;'],
        ['Allocations', 'Full', 'Full', 'View (own dept.)', 'View (all)'],
        ['Transfers', 'Full', 'Full', 'View (source/dest. = own dept.)', 'View (all)'],
        ['Maintenance', 'Full (incl. update/close)', 'Full (incl. update/close)', 'Report only + view (own dept.)', 'View (all)'],
        ['Audits', 'Full', 'Full', 'View (own dept.)', 'View (all)'],
        ['Requisitions', 'Submit (any dept.) + Review/Approve/Issue', 'Review/Approve/Issue (cannot submit)',
         'Submit (own dept.) only', 'View (all)'],
        ['Disposals', 'Request + Approve/Reject', 'Request only', 'View (own dept.)', 'Approve/Reject only'],
        ['Reports', 'Full, all data + CSV/PDF export', 'Full, all data + CSV/PDF export',
         'Own department scope + CSV/PDF export', 'Full, all data, view-only + CSV/PDF export'],
        ['Settings (all six tabs)', 'Full', '&mdash;', '&mdash;', '&mdash;'],
        ['Own Profile / Password', 'Full', 'Full', 'Full', 'Full'],
    ], col_widths=[4.4 * cm, 3.6 * cm, 3.5 * cm, 2.9 * cm, 2.6 * cm]
)
note_box(
    '<b>Enforcement is server-side, confirmed for every module read.</b> Every restricted page calls '
    'requireRole([...]) as one of its first lines (before any output), and the sidebar\'s own role check '
    '(includes/layout/sidebar.php: `if ($role === ROLE_ADMIN)`) matches the equivalent server-side guard '
    'exactly everywhere it was checked. A user without the right role who opens a restricted URL directly '
    'is shown includes/layout/forbidden.php with an HTTP 403 status, not silently redirected — this was '
    'verified in modules/users/*.php, modules/settings/*.php, and modules/disposals/approve.php.')
story.append(PageBreak())

# ==================================================================
# PART 6 — COMPLETE SYSTEM WORKFLOWS
# ==================================================================
h1('Part 6 — Complete System Workflows')

h2('6.1 Authentication')
diagram(
"Login form submitted (modules/auth/login.php, POST)\n"
"  -> verifyCsrf()                      fail -> 'Your session expired. Please try again.'\n"
"  -> loginValue/password both present?  no  -> 'Username/email and password are both required.'\n"
"  -> SELECT ... WHERE email = :login1 OR username = :login2\n"
"  -> password_verify($password, $user['password'])   fail -> 'Invalid username/email or password.'\n"
"                                                               + logLogin(..., 'failed')\n"
"  -> $user['status'] === 'active'?      no  -> 'Your account has been deactivated...'\n"
"                                                               + logLogin(..., 'failed')\n"
"  -> session_regenerate_id(true)\n"
"  -> populate $_SESSION (user_id, name, email, username, profile_picture, role_id, role_name,\n"
"                          department_id, department_name)\n"
"  -> logLogin(..., 'success')  +  logActivity('Login')\n"
"  -> redirect to $_SESSION['redirect_after_login'] or modules/dashboard/index.php"
)

h2('6.2 Asset Management')
p('See the full lifecycle diagram in Part 3.1 — reproduced there because it is the module every other '
  'workflow ultimately reports back to.')

h2('6.3 Requisition')
p('See Part 3.5 for the exact, code-verified status transition diagram (pending &rarr; approved/rejected; '
  'approved &rarr; issued; both server-enforced).')

h2('6.4 Disposal')
p('See Part 3.6. The one workflow in the system with an explicit, code-enforced separation of duties: the '
  'role that can request a disposal (Admin/Asset Officer) never overlaps with the role that approves it '
  'when the requester is an Asset Officer (Admin/Top Management only).')

h2('6.5 Maintenance')
p('See Part 3.7. The one workflow that automatically changes assets.status in both directions '
  '(active &rarr; under_repair on report, under_repair &rarr; active on completion), via '
  'recomputeAssetStatus().')

h2('6.6 Transfer')
p('See Part 3.8 — a single-step action (no review/approval stage), gated to Admin/Asset Officer, with two '
  'validation guards (not a disposed asset; not already in the destination department).')

h2('6.7 Reports — Generation and Export')
diagram(
"modules/reports/index.php (on-screen)\n"
"  -> $report = by_department | by_category | by_status | maintenance_cost | disposals\n"
"  -> aggregate GROUP BY query for the table  +  matching per-row detail query (same scoping)\n"
"  -> renderAssetCountCell()/renderAssetDetailModal() build the clickable Asset Count UI\n"
"\n"
"CSV Export (modules/reports/export_csv.php)          PDF Export (modules/reports/export_pdf.php)\n"
"  -> includes/functions.php: getReportRows()            -> includes/functions.php: getReportRows()\n"
"     (the SAME function index.php's own queries             (the SAME function again)\n"
"      independently re-derive equivalent data from)      -> vendor/fpdf/fpdf.php: ReportPDF (custom\n"
"  -> fputcsv() to php://output                              Header/Footer, auto Portrait/Landscape,\n"
"  -> Content-Disposition: attachment; .csv                   fitText() truncation)\n"
"                                                          -> $pdf->Output('D', ...); .pdf download"
)

h2('6.8 Backup')
diagram(
"Settings -> Backup & Restore (modules/settings/backup.php)\n"
"  -> shows DB size + table count (information_schema query)\n"
"\n"
"Download Backup (modules/settings/backup_download.php)\n"
"  -> SHOW TABLES  ->  for each table: SHOW CREATE TABLE + SELECT * -> hand-built INSERT statements\n"
"  -> streamed directly as a .sql file download (no temp file written to disk)\n"
"\n"
"Restore Database (modules/settings/backup_restore.php)\n"
"  -> validates .sql extension + <= 50MB\n"
"  -> $pdo->exec($sql)  <-- the ENTIRE uploaded file content, executed as-is against the live DB\n"
"  -> success/failure flashed back to modules/settings/backup.php\n"
"\n"
"No automatic pre-restore backup is taken. No staging/dry-run step exists. See Part 8, Finding #1."
)
story.append(PageBreak())

# ==================================================================
# PART 7 — FRONTEND
# ==================================================================
h1('Part 7 — Frontend')
h3('UI Structure')
p('Every authenticated page shares one shell, assembled by includes/layout/header.php + sidebar.php + '
  'footer.php: a fixed-width, always-dark-navy left sidebar; a sticky white topbar; and a content area in '
  'between. There is no client-side routing or single-page-app behavior anywhere — every navigation is a '
  'normal full-page HTTP request.')
h3('CSS Loading')
p('Exactly one stylesheet, static/css/style.css, linked from includes/layout/header.php and separately '
  'from modules/auth/login.php (which does not go through header.php, since it renders before a user is '
  'authenticated). Both references append `?v=&lt;filemtime()&gt;` so a browser never serves a stale cached '
  'copy after a CSS edit. The Google Font "Inter" is loaded from Google Fonts via a preconnect + stylesheet '
  'link, with a system-font fallback stack defined in the CSS itself.')
h3('JavaScript Loading')
p('static/js/main.js is loaded once, from includes/layout/footer.php (also cache-busted), and covers every '
  'page. static/js/validation.js is loaded separately, only by modules/auth/login.php. No other module '
  'loads any additional script — all interactivity elsewhere is handled by main.js\'s generic, '
  'attribute-driven handlers.')
h3('Navigation / Sidebar')
p('includes/layout/sidebar.php renders a fixed list of links via a local navItem() helper, wrapping each in '
  'an &lt;a&gt; with an "active" class match against $activeMenu (set by the including module). The '
  '"Administration" section (Users/Departments/Categories/Locations/Settings) is wrapped in a single '
  '`if ($role === ROLE_ADMIN)` check — the only role-conditional rendering block in the sidebar.')
h3('Forms')
p('Every form in the system follows the same pattern: a &lt;?= csrfField() ?&gt; hidden input, `method="post"`, '
  'and server-side validation via includes/functions.php: validateRequired() plus each module\'s own extra '
  'checks (numeric, email format, enum membership, uniqueness). No form was found that skips the CSRF field.')
h3('Validation')
p('Two layers, confirmed independently: static/js/validation.js runs first in the browser (required/email/'
  'number-range/minlength checks, purely for immediate feedback), and every POST handler re-validates from '
  'scratch server-side regardless of what the client already checked — the client-side layer could be '
  'disabled entirely and no invalid data could reach the database as a result.')
h3('Responsive Behavior')
p('Two CSS breakpoints in static/css/style.css: at 992px the sidebar becomes an off-canvas panel toggled by '
  'a hamburger button (#sidebarToggle, wired in main.js), and at 600px content padding and heading sizes '
  'shrink further. No separate mobile stylesheet or JS exists.')
h3('Which Modules Use Which Frontend Resources')
data_table(
    ['Resource', 'Used By'],
    [
        ['static/css/style.css', 'Every page in the system, including the pre-auth login and 403 pages'],
        ['static/js/main.js', 'Every authenticated page (via footer.php)'],
        ['static/js/validation.js', 'modules/auth/login.php only'],
        ['static/images/logo.webp', 'includes/layout/sidebar.php + header.php + modules/auth/login.php, '
         'as the fallback whenever no admin-uploaded logo is set'],
        ['Google Fonts "Inter"', 'Every authenticated page + the login page (both load it independently)'],
    ], col_widths=[4.5 * cm, 12.3 * cm]
)
story.append(PageBreak())

# ==================================================================
# PART 8 — AUTHENTICATION & SECURITY
# ==================================================================
h1('Part 8 — Authentication &amp; Security')
p('Every finding below was produced by reading the actual implementation, not by assumption. Nothing in '
  'this section has been changed — this is analysis only, as requested.')

def finding(num, title, location, problem, risk, fix, severity):
    story.append(Spacer(1, 6))
    sev_color = IMPORTANCE_COLOR.get(severity, GRAY)
    t = Table([[Paragraph(f'Finding #{num}: {title}', ParagraphStyle('ft', parent=Body, fontName='Helvetica-Bold',
                                                                       fontSize=9.6, textColor=WHITE, spaceAfter=0)),
                Paragraph(f'<b>{severity}</b>', ParagraphStyle('fs', parent=Body, fontSize=9, textColor=WHITE,
                                                                alignment=TA_CENTER, spaceAfter=0))]],
               colWidths=[13.6 * cm, 3.2 * cm])
    t.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (0, 0), NAVY_DARK), ('BACKGROUND', (1, 0), (1, 0), sev_color),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('LEFTPADDING', (0, 0), (-1, -1), 10), ('TOPPADDING', (0, 0), (-1, -1), 6),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 6),
    ]))
    story.append(t)
    field('Location', location)
    field('Problem', problem)
    field('Risk', risk)
    field('Recommended Fix (not applied — analysis only)', fix)
    story.append(Spacer(1, 4))

h2('8.1 What Is Implemented Correctly (confirmed by reading the code)')
data_table(
    ['Control', 'Verified Implementation'],
    [
        ['Password hashing', 'password_hash()/password_verify() (bcrypt) — no plain-text or reversible '
         'password storage found anywhere.'],
        ['SQL injection', 'PDO prepared statements with bound parameters used in every query across all '
         '~50 module files inspected; PDO::ATTR_EMULATE_PREPARES is explicitly false (real server-side '
         'prepares). The one exception is modules/settings/backup_restore.php, which is a deliberate raw-SQL '
         'feature, not an injection bug — see Finding #1.'],
        ['XSS', 'includes/functions.php: e() (htmlspecialchars, ENT_QUOTES) wraps essentially all '
         'user-influenced output in every module\'s HTML; no raw-echoed user input was found.'],
        ['CSRF', 'includes/functions.php: csrfField()/verifyCsrf()/requireCsrf(), a random 32-byte token '
         'compared with hash_equals(); present on every state-changing form found in the codebase.'],
        ['Session hardening', 'config/config.php: session.use_strict_mode, HttpOnly, SameSite=Lax, and '
         'Secure-when-HTTPS cookie flags; session_regenerate_id(true) on every successful login (session '
         'fixation protection).'],
        ['Role enforcement', 'Server-side requireRole()/hasRole() on every restricted page — confirmed '
         'consistent with the sidebar\'s own role checks (see Part 5).'],
        ['Audit trail', 'logActivity()/logLogin() called from every state-changing action reviewed, feeding '
         'the Settings &rarr; Activity/Login Logs pages.'],
    ], col_widths=[3.6 * cm, 13.2 * cm]
)

h2('8.2 Findings')
finding(1, 'Unrestricted SQL execution via database restore',
    'modules/settings/backup_restore.php',
    '`$pdo->exec($sql)` executes the entire contents of an uploaded file directly against the live '
    'database. The only checks are the file extension (.sql) and a 50MB size cap — the SQL itself is not '
    'parsed, filtered, or limited to safe statement types in any way.',
    'Any Admin session — the legitimate administrator, a mistakenly-uploaded wrong file, or an attacker who '
    'has obtained an Admin session (e.g. via a stolen session cookie, or a phished credential, since no '
    'second factor exists) — can drop, recreate, or arbitrarily repopulate every table in one request, with '
    'no automatic safety net taken beforehand.',
    'Take an automatic on-disk backup immediately before every restore; parse and constrain the uploaded '
    'file to expected DDL/DML patterns (or require it to have been produced by this system\'s own '
    'backup_download.php, e.g. via a signed header); consider requiring a second confirmation step showing '
    'a diff/summary of what will change before executing.',
    'CRITICAL')
finding(2, 'No login rate limiting or account lockout',
    'modules/auth/login.php',
    'Every failed attempt is logged (login_logs), but nothing reads that history to slow down or block '
    'repeated attempts against the same account or from the same IP address.',
    'A password can be brute-forced or credential-stuffed at whatever rate the network allows, with the '
    'only trace being a login_logs table that no part of the running application ever inspects '
    'automatically.',
    'Track recent failed attempts per account/IP (the login_logs table already has everything needed) and '
    'add a short lockout or increasing delay after a threshold; optionally surface repeated-failure alerts '
    'to the Admin.',
    'MEDIUM')
finding(3, 'Session timeout setting is not enforced',
    'config/config.php + modules/settings/index.php',
    'The session cookie lifetime is set once, in config/config.php, from a hardcoded fallback (1440 '
    'minutes) before the database — and therefore the settings table — is even reachable. The '
    '"Session Timeout (minutes)" field in Settings &rarr; General is validated and saved to the settings '
    'table, but nothing in the codebase re-applies it to session_set_cookie_params() or an idle-timeout '
    'check on subsequent requests.',
    'Not a vulnerability by itself (the fallback is a fixed, reasonable 24 hours), but it is a '
    'silently-non-functional admin control: changing this setting has zero observable effect, which could '
    'give an administrator false confidence that sessions expire sooner than they actually do.',
    'Either enforce it (e.g. store the configured minutes in the session and check elapsed idle time on '
    'each request in bootstrap.php) or remove the setting from the UI until it is wired up.',
    'MEDIUM')
finding(4, 'Uploaded files are validated by extension only, not content',
    'modules/profile/index.php (avatars) and modules/settings/index.php (logos)',
    'Both uploaders check the file extension against a whitelist (jpg/jpeg/png/gif) and a size cap, but '
    'neither verifies the file is actually an image (e.g. via getimagesize() or a MIME-type/magic-byte '
    'check). No .htaccess or web-server rule was found anywhere in the repository restricting script '
    'execution inside uploads/avatars/ or uploads/logos/.',
    'If the web server were ever configured to execute PHP from the uploads directory (not the case in a '
    'default XAMPP docroot, but a realistic misconfiguration on other hosts), a file with a spoofed '
    '.jpg/.png extension but PHP content could be uploaded and, if directly requested, executed.',
    'Validate actual image content (getimagesize() or finfo_file() MIME detection) in addition to the '
    'extension check, and add an uploads/.htaccess (or equivalent web-server config) explicitly denying '
    'script execution in that directory as defense-in-depth.',
    'MEDIUM')
finding(5, 'Database credentials committed in plain text',
    'config/database.php',
    'DB_HOST/DB_NAME/DB_USER/DB_PASS are literal constants in a version-controlled file (root user, empty '
    'password in this project\'s local/demo configuration).',
    'Low risk for the local/demo environment this project ships with, but if the same file or pattern were '
    'carried into a real deployment with a non-empty, meaningful password, that password would be exposed '
    'to anyone with repository access.',
    'Externalize credentials (environment variables or a git-ignored local config file) before any '
    'non-local deployment; keep config/database.php as a checked-in template with placeholder values only.',
    'LOW (in the current local/demo context) / HIGH if unchanged before a real deployment')
finding(6, 'Duplicate named PDO placeholder crashes the Users search box',
    'modules/users/list.php',
    'The search query — `(u.name LIKE :q OR u.email LIKE :q OR u.username LIKE :q)` — binds the same named '
    'placeholder `:q` three times in one prepared statement. Because config/database.php sets '
    'PDO::ATTR_EMULATE_PREPARES to false, MySQL\'s native prepared-statement protocol rejects a repeated '
    'named placeholder, throwing `SQLSTATE[HY093]: Invalid parameter number`.',
    'Any Admin who types a search term into Users &rarr; Search currently receives an HTTP 500 error '
    'instead of filtered results — a functional availability bug, not a data-exposure risk, but it is a '
    'currently-reproducible defect in an Admin-only screen. (The identical bug class was previously found '
    'and fixed in modules/auth/login.php and modules/assets/list.php, using distinct placeholder names for '
    'each repeated occurrence — that fix pattern was not carried over to this file.)',
    'Bind three distinct placeholders (e.g. :q1/:q2/:q3) to the same search value, exactly as was already '
    'done for the equivalent fix in modules/assets/list.php.',
    'MEDIUM')
finding(7, 'Backup downloads contain every user\'s password hash and the raw SMTP password',
    'modules/settings/backup_download.php',
    'The generated .sql dump includes full INSERT statements for the users table (bcrypt hashes) and the '
    'settings table (which can contain a plain-text smtp_password value if one has been configured).',
    'This is expected/necessary for a functional database backup, but it means the downloaded file itself '
    'is exactly as sensitive as the live database and must be stored/transmitted with equivalent care — a '
    'risk of the feature\'s nature rather than a coding mistake.',
    'Document this clearly for administrators; consider encrypting the download or requiring a fresh '
    're-authentication step immediately before it is generated.',
    'LOW')

h2('8.3 Not Applicable / Not Found In This Codebase')
p('The system has no file-execution-from-upload path outside of the two upload directories already '
  'covered in Finding #4 (no arbitrary file-write endpoint elsewhere was found). No client-side secrets '
  '(API keys, tokens) are embedded in static/js/*.js. No third-party JavaScript beyond the Google Fonts '
  'stylesheet link is loaded. No cookies other than the PHP session cookie are set anywhere in the codebase.')
story.append(PageBreak())

# ==================================================================
# PART 9 — REPORTS & PDF
# ==================================================================
h1('Part 9 — Reports &amp; PDF')
p('Covers modules/reports/*, vendor/fpdf/*, and docs/build_pdf.py, read in full.')
h3('How Reports Are Generated (on screen)')
p('modules/reports/index.php runs one of five report branches based on a `report` query-string value, each '
  'pairing a GROUP BY aggregate query with a per-row detail query using identical WHERE scoping — this is '
  'what guarantees the clickable Asset Count modal always shows the exact records the count was computed '
  'from (verified programmatically during development: the modal row count and the on-screen number match '
  'exactly for every department/category/status, and the sum of each modal\'s per-asset Value column '
  'reconciles to the row\'s displayed Total Value).')
h3('How CSV Export Works')
p('modules/reports/export_csv.php calls includes/functions.php: getReportRows($pdo, $report, $isHead, '
  '$deptId, $dateFrom, $dateTo) — the same shared function PDF export also calls — then streams the result '
  'through PHP\'s built-in fputcsv() directly to php://output with a Content-Disposition: attachment header. '
  'No temporary file is written to disk.')
h3('How PDF Export Works')
p('modules/reports/export_pdf.php also calls getReportRows(), then builds the document with a small '
  'ReportPDF class extending vendor/fpdf/fpdf.php\'s FPDF. It overrides Header() (university name + report '
  'title + generation timestamp, centered) and Footer() (page N/total, using FPDF\'s AliasNbPages()), and '
  'adds one local helper, fitText(), that measures each cell\'s rendered width with GetStringWidth() and '
  'truncates with an ellipsis rather than letting any cell overflow into its neighbor. Orientation is '
  'chosen automatically: Landscape once a report has four or more columns (Maintenance Cost, Disposals), '
  'Portrait otherwise.')
h3('Which Data Is Used')
p('Exactly the same five report definitions in both export formats and the on-screen view — by '
  'construction, since export_csv.php and export_pdf.php both delegate to getReportRows() rather than '
  'maintaining any query logic of their own.')
h3('Which Library Is Used, and How It Is Connected')
p('FPDF (vendored by hand at vendor/fpdf/fpdf.php — no Composer, consistent with the rest of the project). '
  'It is required directly with `require_once APP_ROOT . \'/vendor/fpdf/fpdf.php\'` at the top of '
  'export_pdf.php; there is no autoloader, and no other file in the project references FPDF at all.')
h3('docs/build_pdf.py — A Separate, Offline Tool')
p('Not part of the running web application and not connected to modules/reports/ in any way. It is a '
  'standalone Python/reportlab script, run manually by a developer, that produces the existing '
  'end-user/admin manual PDF already present in docs/. See Part 2.21 for the specific portability issue '
  'found in this script (a hardcoded, currently-missing logo path).')
story.append(PageBreak())

# ==================================================================
# PART 10 — SETTINGS
# ==================================================================
h1('Part 10 — Settings')
p('modules/settings/ contains six admin-only pages sharing modules/settings/_subnav.php for consistent '
  'tab navigation.')
data_table(
    ['Tab (file)', 'Purpose', 'Relationship to the Rest of the System'],
    [
        ['General (index.php)', 'University/system name, academic year, language, timezone, date format, '
         'session timeout, records-per-page, theme, logo upload, maintenance-mode switch.',
         'The single source for every includes/functions.php: getSetting() call made anywhere else in the '
         'app — university name in every page title/footer, theme applied to every &lt;body&gt; class, '
         'language applied globally via includes/i18n.php, records-per-page used by every paginated list.'],
        ['Email / SMTP (smtp.php)', 'SMTP host/port/username/password/encryption for outgoing email.',
         'Currently a dead end — no code anywhere in the project reads these settings to actually send an '
         'email (see Part 13).'],
        ['Backup & Restore (backup.php, backup_download.php, backup_restore.php)', 'Full database dump '
         'download and raw-SQL restore-from-upload.', 'Self-contained; does not feed any other module. The '
         'system\'s only backup mechanism — see Part 8, Finding #1 for the associated risk.'],
        ['Activity Logs (logs.php)', 'Paginated view of activity_logs, filterable by module.',
         'Every logActivity() call made by any module (create/update/delete/state-change actions) lands '
         'here.'],
        ['Login Logs (login_logs.php)', 'Paginated view of login_logs, filterable by success/failed.',
         'Every logLogin() call from modules/auth/login.php lands here — currently observational only, not '
         'acted on automatically (see Part 8, Finding #2).'],
        ['System Info (system_info.php)', 'Read-only PHP/MySQL/server/disk snapshot.', 'Purely diagnostic; '
         'writes nothing, reads nothing any other module depends on.'],
    ], col_widths=[3.6 * cm, 6.4 * cm, 6.8 * cm]
)
p('Role access, confirmed identically across all six files: every one of them calls '
  '`requireRole([ROLE_ADMIN])` as one of its first lines. No other role can reach any Settings page, '
  'including by direct URL — each would receive the 403 Access Denied page.')
story.append(PageBreak())

# ==================================================================
# PART 11 — DEPENDENCY MAP
# ==================================================================
h1('Part 11 — Dependency Map')
p('The include/require chain and the shared-function fan-out, as they actually exist in the code (not an '
  'idealized version).')
diagram(
"EVERY modules/**/*.php file starts with:\n"
"    require_once __DIR__ . '/../../includes/bootstrap.php';\n"
"\n"
"includes/bootstrap.php\n"
"  |-- require_once config/config.php        (constants, session hardening + session_start())\n"
"  |-- require_once config/database.php      (creates $pdo, or dies with HTTP 500)\n"
"  |-- require_once includes/auth.php        (ROLE_* constants, requireLogin/requireRole/hasRole)\n"
"  |-- require_once includes/functions.php   (e/clean/csrf*/flash/format*/statusBadge/icon/\n"
"  |                                           getPendingAlerts/getReportRows/logActivity/logLogin/\n"
"  |                                           getSetting family/recomputeAssetStatus/validateRequired)\n"
"  |-- require_once includes/i18n.php        (lazily requires includes/i18n_strings.php on first t() call)\n"
"  |-- setActiveLanguage(getSetting($pdo, 'language'))\n"
"  '-- maintenance-mode gate (reads getSetting($pdo, 'maintenance_mode'))\n"
"\n"
"then the module file's own logic runs, typically:\n"
"  |-- requireLogin() / requireRole([...])            (includes/auth.php)\n"
"  |-- $pdo->prepare(...)->execute([...])              (config/database.php's $pdo)\n"
"  |-- on POST: requireCsrf() -> validateRequired()/custom checks -> INSERT/UPDATE/DELETE\n"
"  |             -> logActivity()  -> flash()  -> redirect()\n"
"  '-- on GET: builds $pageTitle/$activeMenu, then:\n"
"        include includes/layout/header.php\n"
"          |-- getSetting() (university name, theme)\n"
"          |-- getPendingAlerts()             (includes/functions.php)\n"
"          '-- include includes/layout/sidebar.php\n"
"                '-- icon()/appLogoUrl()/t()   (includes/functions.php, includes/i18n.php)\n"
"        ... the module's own HTML, using e()/statusBadge()/formatMoney()/formatDate() ...\n"
"        include includes/layout/footer.php\n"
"          '-- loads static/js/main.js\n"
"\n"
"modules/reports/export_csv.php --------+\n"
"                                        +--> includes/functions.php: getReportRows()  (single shared query)\n"
"modules/reports/export_pdf.php --------+\n"
"                                        '--> vendor/fpdf/fpdf.php   (PDF rendering only)\n"
"\n"
"modules/assets/add.php  --+\n"
"modules/assets/edit.php --+--> modules/assets/_form.php   (the one shared view partial in the project)\n"
"\n"
"modules/settings/index.php, smtp.php, backup.php, logs.php, login_logs.php, system_info.php\n"
"        each --> modules/settings/_subnav.php   (shared tab strip)\n"
"\n"
"includes/functions.php: recomputeAssetStatus()  <-- called from:\n"
"        modules/maintenance/add.php, modules/maintenance/update.php, modules/disposals/approve.php\n"
"        (NOT called from modules/assets/edit.php -- see Part 2.7 / Part 13)"
)
story.append(PageBreak())

# ==================================================================
# PART 12 — WHAT EACH FILE CONTRIBUTES TO THE SYSTEM
# ==================================================================
h1('Part 12 — What Each File Contributes to the System')
p('A compact, single-row-per-file summary for quick reference. Full detail for every file is in Part 2.')

def contrib_table(rows):
    data_table(['File', 'What It Does', 'What It Contributes to ASM'], rows,
               col_widths=[4.6 * cm, 6.1 * cm, 6.1 * cm], small_font=True)

contrib_table([
    ['index.php', 'Redirects to Dashboard or Login based on session state.', 'The single public entry point.'],
    ['config/config.php', 'Constants + session hardening + session_start().', 'Makes every other file able to run safely.'],
    ['config/database.php', 'Opens the shared PDO connection.', 'Makes every database query possible.'],
    ['database/schema.sql', '15-table schema + seed data.', 'Defines the entire data model.'],
    ['includes/bootstrap.php', 'Wires config/db/auth/functions/i18n together; maintenance-mode gate.', 'The composition root every page starts from.'],
    ['includes/auth.php', 'Role constants + login/role guard functions.', 'Enforces every access rule in the system.'],
    ['includes/functions.php', 'Sanitization, CSRF, formatting, logging, notifications, shared report query.', 'The most-reused utility layer in the codebase.'],
    ['includes/i18n.php / i18n_strings.php', 'Translation lookup + English/Somali dictionary.', 'Lets the app shell + dashboard + login run in two languages.'],
    ['includes/layout/header.php', 'Opens the HTML shell, topbar, notification bell.', 'Consistent chrome on every page.'],
    ['includes/layout/sidebar.php', 'Role-aware navigation menu.', 'Primary means of moving between modules.'],
    ['includes/layout/footer.php', 'Closes the shell, loads main.js.', 'Consistent chrome + shared interactivity.'],
    ['includes/layout/forbidden.php', '403 Access Denied page.', 'Makes denied access visible rather than silently hidden.'],
    ['modules/auth/login.php', 'Authenticates and starts a session.', 'The front door of the whole system.'],
    ['modules/auth/logout.php', 'Destroys the session.', 'Clean session termination.'],
    ['modules/dashboard/index.php', 'Role-scoped summary stats + activity feed.', 'At-a-glance system state on login.'],
    ['modules/assets/list.php', 'Searchable, paginated asset list.', 'Primary browsing surface for the asset register.'],
    ['modules/assets/add.php', 'Registers a new asset.', 'Creates the master record everything else attaches to.'],
    ['modules/assets/edit.php', 'Edits an asset.', 'Corrections/reclassification.'],
    ['modules/assets/view.php', 'One asset + its full 5-table history.', 'Single source of truth per asset.'],
    ['modules/assets/_form.php', 'Shared add/edit form fields.', 'Keeps create and edit forms in sync.'],
    ['modules/assigned/add.php', 'Issues an asset to a department/custodian.', 'Starts an allocation.'],
    ['modules/assigned/list.php', 'Lists allocations + Return/Repair actions.', 'Operational "who has what" view.'],
    ['modules/assigned/return.php', 'Closes an active allocation.', 'Ends an allocation cleanly.'],
    ['modules/transfers/add.php', 'Moves an asset between departments.', 'Inter-department movement record.'],
    ['modules/transfers/list.php', 'Transfer history.', 'Read-only audit trail of movement.'],
    ['modules/maintenance/add.php', 'Reports a repair issue.', 'Starts a maintenance ticket; flips status to Under Repair.'],
    ['modules/maintenance/list.php', 'Maintenance ticket queue.', 'Operational repair-tracking view.'],
    ['modules/maintenance/update.php', 'Progresses/closes a ticket.', 'Closes the repair loop; restores Active status.'],
    ['modules/audits/add.php', 'Records a physical stock-check result.', 'Physical verification evidence.'],
    ['modules/audits/list.php', 'Audit history.', 'Read-only verification trail.'],
    ['modules/disposals/add.php', 'Requests an asset write-off.', 'Starts the disposal workflow.'],
    ['modules/disposals/approve.php', 'Approves/rejects a disposal.', 'The only place an asset becomes Disposed.'],
    ['modules/disposals/list.php', 'Disposal request history.', 'Full write-off decision trail.'],
    ['modules/requisitions/add.php', 'Submits a department request.', 'Starts the requisition workflow.'],
    ['modules/requisitions/list.php', 'Requisition queue.', 'Review/tracking surface.'],
    ['modules/requisitions/review.php', 'Approves/rejects/issues a requisition.', 'Enforces the requisition status workflow.'],
    ['modules/categories/list.php', 'Category CRUD.', 'Feeds Assets/Requisitions category dropdowns.'],
    ['modules/departments/list.php', 'Department CRUD + Head assignment.', 'Defines the scope every dept.-locked view uses.'],
    ['modules/locations/list.php', 'Location CRUD.', 'Feeds the Assets location dropdown.'],
    ['modules/users/add.php', 'Creates a user account.', 'The only way new accounts are provisioned.'],
    ['modules/users/edit.php', 'Edits a user, role, department, password.', 'Account/role administration.'],
    ['modules/users/list.php', 'Searchable user list + Deactivate.', 'Account administration hub.'],
    ['modules/users/deactivate.php', 'Toggles active/inactive.', 'Revokes access without deleting history.'],
    ['modules/profile/index.php', 'Self-service profile + password.', 'Lets any user maintain their own account.'],
    ['modules/reports/index.php', 'Five on-screen reports + drill-down modals.', 'The system\'s analytics surface.'],
    ['modules/reports/export_csv.php', 'Streams a report as CSV.', 'Spreadsheet-compatible export.'],
    ['modules/reports/export_pdf.php', 'Streams a report as PDF.', 'Printable/archivable export.'],
    ['modules/settings/index.php', 'General system configuration.', 'The source of every global setting used app-wide.'],
    ['modules/settings/smtp.php', 'Saves SMTP credentials.', 'Currently unused by any sending code (see Part 13).'],
    ['modules/settings/backup.php', 'Backup/restore landing page.', 'Entry point for the system\'s only backup mechanism.'],
    ['modules/settings/backup_download.php', 'Streams a full .sql dump.', 'The system\'s only backup output.'],
    ['modules/settings/backup_restore.php', 'Executes an uploaded .sql file.', 'The system\'s only restore mechanism (see Part 8).'],
    ['modules/settings/logs.php', 'Activity log viewer.', 'System-wide audit trail UI.'],
    ['modules/settings/login_logs.php', 'Login attempt viewer.', 'Authentication forensics UI.'],
    ['modules/settings/system_info.php', 'Environment snapshot.', 'Diagnostic reference for support/evaluation.'],
    ['modules/settings/_subnav.php', 'Shared Settings tab strip.', 'Visual/structural consistency across Settings.'],
    ['static/css/style.css', 'The entire visual theme (light + dark).', 'Consistent look across every page.'],
    ['static/js/main.js', 'Sidebar/dropdowns/modals/confirm/search/sort/tabs.', 'All shared client-side interactivity.'],
    ['static/js/validation.js', 'Client-side form validation.', 'UX convenience layer on the login form.'],
    ['static/images/logo.webp', 'Default university logo.', 'Brand identity when no custom logo is uploaded.'],
    ['vendor/fpdf/fpdf.php', 'Third-party PDF rendering engine.', 'Powers Reports &rarr; Export PDF.'],
    ['docs/build_pdf.py', 'Generates the end-user/admin manual PDF.', 'Project documentation tooling (offline, not part of the app).'],
])
story.append(PageBreak())

# ==================================================================
# PART 13 — MISSING OR WEAK AREAS
# ==================================================================
h1('Part 13 — Missing or Weak Areas')
p('Documented only — nothing below has been changed.')

h3('Confirmed Bugs')
bullets([
    '<b>Users search crashes (HTTP 500).</b> modules/users/list.php reuses the named placeholder `:q` three '
    'times in one query under a PDO configuration that forbids it. See Part 8, Finding #6.',
])

h3('Incomplete Workflows')
bullets([
    '<b>SMTP settings are saved but never used.</b> modules/settings/smtp.php collects and persists full '
    'email-server credentials, and its own help text promises "outgoing notification emails (e.g. '
    'maintenance updates, requisition decisions)" — but no code anywhere in the repository (no mail(), no '
    'PHPMailer, no other mailer) ever sends an email. This is a configured-but-nonfunctional feature.',
    '<b>No self-service password reset.</b> Only an Admin can reset another user\'s password (via '
    'modules/users/edit.php). There is no "forgot password" flow, which would naturally depend on the '
    'still-unused SMTP settings to deliver a reset link/code.',
    '<b>Session timeout setting has no effect.</b> See Part 8, Finding #3.',
    '<b>Internationalization covers only the app shell.</b> i18n_strings.php\'s own header comment states '
    'this: only the sidebar/topbar/footer, login page, dashboard, and Settings &rarr; General are '
    'translated. All ~40 CRUD module pages (Assets, Allocations, Transfers, Maintenance, Audits, Disposals, '
    'Requisitions, Users, Departments, Categories, Locations, the other five Settings tabs, Reports) remain '
    'English-only regardless of the selected language.',
    '<b>Notification bell has no persisted read/unread state.</b> includes/functions.php: getPendingAlerts() '
    'recomputes "your request was decided" items live from a 7-day lookback window on every page load; '
    'there is no notifications table, so an item silently stops appearing after 7 days whether or not the '
    'user ever saw it, and there is no dedicated "notification history" page.',
])

h3('Data-Consistency Risk')
bullets([
    '<b>Manually-edited asset status can drift.</b> modules/assets/edit.php allows a direct, unrestricted '
    'edit of assets.status, while the rest of the system treats that field as derived '
    '(recomputeAssetStatus() in includes/functions.php, called only from the maintenance and disposal '
    'modules). A hand-set status persists only until the next maintenance/disposal event silently '
    'overwrites it — the edit form warns about this in a caption but does not prevent it.',
])

h3('Workflow Design Asymmetry (worth confirming against the intended business process, not necessarily a bug)')
bullets([
    'Disposals require Top Management (or Admin) approval — a strict separation of duties. Requisitions, by '
    'contrast, can be fully approved/rejected/issued by an Asset Officer alone, with no Department Head or '
    'Top Management sign-off, even though both are department-driven requests with financial impact. This '
    'may be intentional (requisitions are lower-stakes than write-offs) but is a real, code-confirmed '
    'asymmetry in how the two workflows are governed.',
])

h3('Missing Functionality / Coverage Gaps')
bullets([
    'No pagination on the Allocations, Transfers, Maintenance, Audits, Disposals, Requisitions, Categories, '
    'Departments, or Locations lists — only Assets and the two Settings log viewers paginate. Every other '
    'list loads its full result set in one query, which will degrade as those tables grow.',
    'No automated tests exist anywhere in the repository (no /tests directory, no PHPUnit/other test '
    'framework configuration).',
    'No API/JSON layer — the system is entirely server-rendered HTML; integrating a mobile app or external '
    'system would require building a new interface from scratch.',
    'No explicit web-server rule (e.g. .htaccess) was found restricting access to storage/php-error.log, '
    'which could reveal internal file paths and error details if the web server is configured to serve '
    'that directory\'s contents directly.',
])

h3('Duplicate / Poorly Organized Code')
bullets([
    'modules/reports/index.php independently re-implements query logic that is very close to (but not the '
    'same call as) includes/functions.php: getReportRows() — the on-screen view and the two export formats '
    'compute equivalent data through two separate code paths rather than one, which is a minor duplication '
    'risk (a future change to one query shape would need to be mirrored in the other by hand).',
    'docs/build_pdf.py hardcodes an absolute Windows path to a logo file that does not currently exist in '
    'the repository, making the script non-runnable as-is (see Part 2.21).',
])

h3('Not Found / Confirmed Absent (stated explicitly, per the request not to guess)')
bullets([
    'No evidence of a scheduled/automatic backup job (e.g. cron) anywhere in the codebase — backups are '
    'always a manual, on-demand action via Settings.',
    'No rate limiting, WAF, or similar infrastructure-level protection is configured within the application '
    'code itself — cannot be confirmed from the available code whether such protection exists at the '
    'hosting/infrastructure layer outside this repository.',
    'Cannot be confirmed from the available code whether the production deployment (if any exists beyond '
    'this local/demo environment) uses different, non-default database credentials or HTTPS — nothing in '
    'this repository specifies a production hosting configuration.',
])
story.append(PageBreak())

# ==================================================================
# PART 14 — FINAL ARCHITECTURE DIAGRAM
# ==================================================================
h1('Part 14 — Final Architecture Diagram')
diagram(
"                         UNIVERSITY ASSET MANAGEMENT SYSTEM (ASM)\n"
"                                         |\n"
"        +--------------------------------+---------------------------------+\n"
"        |                                |                                 |\n"
"     FRONTEND                    APPLICATION LOGIC                    DATA LAYER\n"
"   (static/, layout)                (includes/, modules/)          (database/schema.sql)\n"
"        |                                |                                 |\n"
"  static/css/style.css        includes/bootstrap.php  <--- composition root\n"
"  static/js/main.js                  |                                 |\n"
"  static/js/validation.js            +-- config/config.php             |\n"
"  static/images/logo.webp            +-- config/database.php ----------+---> PDO -> MySQL/MariaDB\n"
"  includes/layout/*.php              +-- includes/auth.php             |      (15 InnoDB tables,\n"
"  (shared HTML shell,                +-- includes/functions.php        |       utf8mb4, FK-enforced)\n"
"   role-aware sidebar,               +-- includes/i18n.php / strings   |\n"
"   notification bell)                |                                 |\n"
"                                      v                                 |\n"
"                          modules/<area>/<file>.php                    |\n"
"                          (one file = controller + view,               |\n"
"                           14 feature modules: auth, dashboard,        |\n"
"                           assets, assigned, transfers, maintenance,   |\n"
"                           audits, disposals, requisitions, users,     |\n"
"                           departments, categories, locations,        |\n"
"                           reports, settings, profile)   -------------> all reads/writes go\n"
"                                      |                                 through PDO prepared\n"
"                                      v                                 statements only\n"
"                          Rendered HTML response\n"
"                          (same file, no template engine)\n"
"\n"
"                              THIRD-PARTY DEPENDENCY\n"
"                          vendor/fpdf/fpdf.php  <-- used only by modules/reports/export_pdf.php\n"
"\n"
"                              RUNTIME / GENERATED CONTENT\n"
"                  uploads/logos, uploads/avatars, storage/ (error log + reserved backups/)\n"
"\n"
"Request flow: Browser -> module file -> bootstrap.php (config+db+auth+functions+i18n+maintenance gate)\n"
"           -> role guard -> business logic (PDO) -> shared header/sidebar/footer -> HTML response."
)
story.append(PageBreak())

# ==================================================================
# FINAL RECOMMENDATIONS & SUMMARY
# ==================================================================
h1('Final Recommendations')
h3('Priority Order')
bullets([
    '<b>1 (Critical).</b> Add a pre-restore automatic backup and tighten what modules/settings/'
    'backup_restore.php will execute (Finding #1).',
    '<b>2 (High).</b> Fix the Users search crash by using distinct bound placeholders, the same pattern '
    'already applied to the login and Assets fixes (Finding #6).',
    '<b>3 (Medium).</b> Decide, and then either build or remove: email notifications (the SMTP settings '
    'already exist and are simply unconnected) and self-service password reset that would naturally build '
    'on it.',
    '<b>4 (Medium).</b> Either enforce the Session Timeout setting or remove it from the Settings UI so it '
    'stops implying a control that does not exist (Finding #3).',
    '<b>5 (Medium).</b> Add real image-content validation and an uploads/ execution-lockdown rule as '
    'defense-in-depth around the two file-upload features (Finding #4).',
    '<b>6 (Low/ongoing).</b> Extend i18n_strings.php coverage to the remaining CRUD modules if full Somali '
    'coverage is a goal; add pagination to the currently-unpaginated list pages before they grow large '
    'enough to matter; consider whether the Requisitions approval workflow should require the same '
    'higher-level sign-off Disposals already has.',
])

h1('Complete Summary')
p('The University Asset Management System is a deliberately framework-free PHP/PDO/MySQL application with '
  'a consistent, easy-to-follow structure: one bootstrap file every page loads first, one shared helper '
  'library, one shared HTML shell, one shared CSS file, one shared JS file, and one module folder per '
  'feature area, each file acting as its own controller and view. Role-based access control is enforced '
  'server-side and consistently, in every module checked. The core asset lifecycle — registration, '
  'allocation, transfer, maintenance, audit, requisition, and disposal — is fully implemented and its '
  'status-derivation logic (recomputeAssetStatus()) is centralized in one function. Security fundamentals '
  '(prepared statements, output escaping, CSRF, password hashing, session hardening) are applied '
  'consistently across the codebase that was read for this analysis, with one clearly-scoped critical risk '
  '(the unrestricted SQL restore feature) and a handful of medium/low findings, all documented above with '
  'concrete locations and fixes. The system\'s weakest areas are less about incorrect code and more about '
  'incomplete features that are visibly promised but not wired up — SMTP-backed notifications chief among '
  'them — plus one currently-reproducible bug in the Users search box that mirrors an already-fixed pattern '
  'elsewhere in the same codebase.')

# ==================================================================
# BUILD
# ==================================================================
PAGE_W, PAGE_H = A4

def cover_bg(canvas, doc_):
    canvas.saveState()
    canvas.setFillColor(NAVY_DARK)
    canvas.rect(0, 0, PAGE_W, PAGE_H, fill=1, stroke=0)
    canvas.setFillColor(NAVY)
    canvas.rect(0, PAGE_H * 0.62, PAGE_W, PAGE_H * 0.38, fill=1, stroke=0)
    canvas.restoreState()

def normal_page(canvas, doc_):
    canvas.saveState()
    canvas.setFont('Helvetica', 8)
    canvas.setFillColor(GRAY)
    canvas.drawString(2 * cm, 1.3 * cm,
                       'University Asset Management System — Technical Architecture & File Structure Analysis')
    canvas.drawRightString(PAGE_W - 2 * cm, 1.3 * cm, f'Page {doc_.page}')
    canvas.setStrokeColor(colors.HexColor('#d8dde3'))
    canvas.line(2 * cm, 1.6 * cm, PAGE_W - 2 * cm, 1.6 * cm)
    canvas.restoreState()

class NumberedDocTemplate(BaseDocTemplate):
    def afterFlowable(self, flowable):
        if isinstance(flowable, Paragraph):
            style_name = flowable.style.name
            text = flowable.getPlainText()
            if style_name == 'H1b':
                self.notify('TOCEntry', (0, text, self.page))
            elif style_name == 'H2b':
                self.notify('TOCEntry', (1, text, self.page))

doc = NumberedDocTemplate(OUT, pagesize=A4,
                           leftMargin=1.6 * cm, rightMargin=1.6 * cm,
                           topMargin=1.6 * cm, bottomMargin=2.0 * cm,
                           title='ASM Technical Architecture Analysis',
                           author='University Asset Management System')

cover_frame = Frame(0, 0, PAGE_W, PAGE_H, id='cover', leftPadding=2.4*cm, rightPadding=2.4*cm,
                     topPadding=1*cm, bottomPadding=1*cm)
normal_frame = Frame(1.6 * cm, 2.0 * cm, PAGE_W - 3.2 * cm, PAGE_H - 4.2 * cm, id='normal')

doc.addPageTemplates([
    PageTemplate(id='Cover', frames=[cover_frame], onPage=cover_bg),
    PageTemplate(id='Normal', frames=[normal_frame], onPage=normal_page),
])

doc.multiBuild(story)
print('PDF written to', OUT)
