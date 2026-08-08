# -*- coding: utf-8 -*-
"""
Generates the professional system documentation PDF for the
University Asset Management System (Somali National University).
All content reflects the actual, verified codebase at C:\\xampp\\htdocs\\asm.
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
from reportlab.pdfgen import canvas as canvas_mod

BASE = os.path.dirname(os.path.abspath(__file__))
LOGO = r"C:\xampp\htdocs\asm\storage\logo_converted.png"
OUT = os.path.join(BASE, "University_Asset_Management_System_Documentation.pdf")

# ---------------------------------------------------------------- colours
NAVY = colors.HexColor('#14375e')
NAVY_DARK = colors.HexColor('#0b2038')
GREEN = colors.HexColor('#8bc53f')
GREEN_DARK = colors.HexColor('#6ea52d')
GRAY = colors.HexColor('#5b6472')
LGRAY = colors.HexColor('#eef1f4')
WARN = colors.HexColor('#c9861a')
DANGER = colors.HexColor('#c0392b')
SUCCESS = colors.HexColor('#4c9a2a')
WHITE = colors.white

# ---------------------------------------------------------------- styles
ss = getSampleStyleSheet()

def style(name, parent, **kw):
    s = ParagraphStyle(name, parent=parent, **kw)
    ss.add(s)
    return s

Body = style('Body', ss['Normal'], fontName='Helvetica', fontSize=9.6, leading=14.5,
             spaceAfter=7, alignment=TA_JUSTIFY, textColor=colors.HexColor('#232a31'))
BodyBold = style('BodyBold', Body, fontName='Helvetica-Bold')
Small = style('Small', Body, fontSize=8.4, leading=12, textColor=GRAY, spaceAfter=4)
Caption = style('Caption', Body, fontSize=8.3, leading=11, textColor=GRAY,
                 alignment=TA_CENTER, spaceAfter=14, spaceBefore=2, fontName='Helvetica-Oblique')

H1 = style('H1', ss['Heading1'], fontName='Helvetica-Bold', fontSize=19, leading=23,
           textColor=NAVY, spaceBefore=0, spaceAfter=14, keepWithNext=True)
H2 = style('H2', ss['Heading2'], fontName='Helvetica-Bold', fontSize=13.5, leading=17,
           textColor=NAVY, spaceBefore=16, spaceAfter=8, keepWithNext=True,
           borderPadding=0)
H3 = style('H3', ss['Heading3'], fontName='Helvetica-Bold', fontSize=10.3, leading=13,
           textColor=GREEN_DARK, spaceBefore=10, spaceAfter=4, keepWithNext=True)
ModuleTitle = style('ModuleTitle', ss['Heading2'], fontName='Helvetica-Bold', fontSize=15,
                     leading=19, textColor=WHITE, spaceBefore=0, spaceAfter=0,
                     backColor=NAVY, borderPadding=(8, 10, 8, 10), keepWithNext=True)
BulletStyle = style('BulletStyle', Body, leftIndent=14, bulletIndent=2, spaceAfter=4)
StepItem = style('StepItem', Body, leftIndent=14, bulletIndent=2, spaceAfter=5)

TOCH1 = style('TOCH1', ss['Normal'], fontName='Helvetica-Bold', fontSize=11, leading=16,
              textColor=NAVY, spaceAfter=6, leftIndent=0)
TOCH2 = style('TOCH2', ss['Normal'], fontName='Helvetica', fontSize=9.6, leading=13.5,
              textColor=colors.HexColor('#232a31'), leftIndent=14, spaceAfter=3)

CoverTitle = style('CoverTitle', ss['Title'], fontName='Helvetica-Bold', fontSize=27,
                    leading=32, textColor=WHITE, alignment=TA_CENTER, spaceAfter=6)
CoverSub = style('CoverSub', ss['Normal'], fontName='Helvetica', fontSize=13.5,
                  leading=18, textColor=colors.HexColor('#dfe9f0'), alignment=TA_CENTER,
                  spaceAfter=4)
CoverOrg = style('CoverOrg', ss['Normal'], fontName='Helvetica-Bold', fontSize=16,
                  leading=20, textColor=GREEN, alignment=TA_CENTER, spaceAfter=2)
CoverTag = style('CoverTag', ss['Normal'], fontName='Helvetica-Oblique', fontSize=9.5,
                  leading=12, textColor=colors.HexColor('#c9d6e0'), alignment=TA_CENTER)
CoverMeta = style('CoverMeta', ss['Normal'], fontName='Helvetica', fontSize=9.5,
                   leading=13, textColor=colors.HexColor('#c9d6e0'), alignment=TA_CENTER)

# ---------------------------------------------------------------- helpers
story = []
h1_counter = [0]
h2_counter = [0]

def h1(text):
    story.append(Paragraph(text, H1))

def h2(text):
    story.append(Spacer(1, 2))
    story.append(Paragraph(text, H2))

def h3(text):
    story.append(Paragraph(text, H3))

def p(text):
    story.append(Paragraph(text, Body))

def bullets(items):
    story.append(ListFlowable(
        [ListItem(Paragraph(i, BulletStyle), leftIndent=14, value='square') for i in items],
        bulletType='bullet', start='square', bulletFontSize=5, bulletColor=GREEN_DARK,
        leftIndent=10
    ))
    story.append(Spacer(1, 4))

def steps(items):
    story.append(ListFlowable(
        [ListItem(Paragraph(i, StepItem), leftIndent=16) for i in items],
        bulletType='1', start=1, leftIndent=10
    ))
    story.append(Spacer(1, 4))

def rule():
    story.append(Spacer(1, 4))
    story.append(HRFlowable(width="100%", thickness=0.6, color=colors.HexColor('#d8dde3'),
                             spaceBefore=2, spaceAfter=10))

def data_table(headers, rows, col_widths=None, small=False):
    fs = 8.0 if small else 8.6
    data = [[Paragraph(f'<b>{c}</b>', ParagraphStyle('th', parent=Body, fontSize=fs,
                                                       textColor=WHITE, alignment=TA_LEFT)) for c in headers]]
    for r in rows:
        data.append([Paragraph(str(c), ParagraphStyle('td', parent=Body, fontSize=fs,
                                                        spaceAfter=0, alignment=TA_LEFT)) for c in r])
    t = Table(data, colWidths=col_widths, repeatRows=1, hAlign='LEFT')
    t.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), NAVY),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [WHITE, LGRAY]),
        ('GRID', (0, 0), (-1, -1), 0.5, colors.HexColor('#c7cfd6')),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('TOPPADDING', (0, 0), (-1, -1), 4),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 4),
        ('LEFTPADDING', (0, 0), (-1, -1), 6),
        ('RIGHTPADDING', (0, 0), (-1, -1), 6),
    ]))
    story.append(t)
    story.append(Spacer(1, 10))

def module_header(title):
    story.append(Spacer(1, 4))
    t = Table([[Paragraph(title, ModuleTitle)]], colWidths=[16.6 * cm])
    t.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), NAVY),
        ('LEFTPADDING', (0, 0), (-1, -1), 12),
        ('TOPPADDING', (0, 0), (-1, -1), 8),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 8),
    ]))
    story.append(t)
    story.append(Spacer(1, 8))

def field_label(label, text):
    story.append(Paragraph(f'<font color="#6ea52d"><b>{label}</b></font> {text}', Body))

def module_section(name, what_it_is, purpose, what_it_does, who_uses_rows, data_managed,
                    features, how_to_use, on_action, permissions_rows, notes, example):
    module_header(name)
    h3('What It Is')
    p(what_it_is)
    h3('Purpose — Why It Is Included')
    p(purpose)
    h3('What It Does')
    if isinstance(what_it_does, list):
        bullets(what_it_does)
    else:
        p(what_it_does)
    h3('Who Uses It')
    data_table(['Role', 'Access Level'], who_uses_rows, col_widths=[4.5 * cm, 12.1 * cm])
    h3('Data It Manages')
    if isinstance(data_managed, list):
        bullets(data_managed)
    else:
        p(data_managed)
    h3('Main Features')
    bullets(features)
    h3('How To Use It — Step by Step')
    steps(how_to_use)
    h3('What Happens When You Perform an Action')
    if isinstance(on_action, list):
        bullets(on_action)
    else:
        p(on_action)
    h3('Permissions and Restrictions')
    data_table(['Role', 'Can Do'], permissions_rows, col_widths=[4.5 * cm, 12.1 * cm])
    h3('Important Notes')
    if isinstance(notes, list):
        bullets(notes)
    else:
        p(notes)
    h3('Real-World Example')
    story.append(Table([[Paragraph(example, ParagraphStyle('ex', parent=Body, spaceAfter=0))]],
                        colWidths=[16.6 * cm],
                        style=TableStyle([
                            ('BACKGROUND', (0, 0), (-1, -1), colors.HexColor('#eef7e1')),
                            ('BOX', (0, 0), (-1, -1), 0.6, GREEN_DARK),
                            ('LEFTPADDING', (0, 0), (-1, -1), 10),
                            ('RIGHTPADDING', (0, 0), (-1, -1), 10),
                            ('TOPPADDING', (0, 0), (-1, -1), 8),
                            ('BOTTOMPADDING', (0, 0), (-1, -1), 8),
                        ])))
    story.append(Spacer(1, 14))

# ==================================================================
# COVER PAGE
# ==================================================================
story.append(Spacer(1, 2.6 * cm))
story.append(Image(LOGO, width=3.4 * cm, height=3.4 * cm, hAlign='CENTER'))
story.append(Spacer(1, 0.9 * cm))
story.append(Paragraph('University Asset Management System', CoverTitle))
story.append(Spacer(1, 0.35 * cm))
story.append(Paragraph('System Documentation', CoverSub))
story.append(Paragraph('User, Administrator &amp; Technical Reference Guide', CoverSub))
story.append(Spacer(1, 1.6 * cm))
story.append(Paragraph('SOMALI NATIONAL UNIVERSITY', CoverOrg))
story.append(Paragraph('Jaamacadda Ummadda Soomaaliyeed', CoverTag))
story.append(Paragraph('Aqoon &nbsp;|&nbsp; Horumar &nbsp;|&nbsp; Horumarin Bulsho', CoverTag))
story.append(Spacer(1, 3.3 * cm))
story.append(Paragraph('Prepared for administrators, asset officers, department heads,<br/>'
                        'top management, and technical evaluators of the system', CoverMeta))
story.append(Spacer(1, 0.5 * cm))
story.append(Paragraph('Document Version 1.0 &nbsp;&middot;&nbsp; August 2026', CoverMeta))
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
# 1. SYSTEM OVERVIEW
# ==================================================================
h1('1. System Overview')
p('The <b>University Asset Management System</b> is a web-based application built for '
  '<b>Somali National University</b> to track every physical asset the university owns '
  'through its complete lifecycle &mdash; from the day it is purchased and registered, through '
  'allocation, transfer between departments, maintenance, physical audit, and finally disposal.')
p('The system replaces manual, paper-based or spreadsheet-based asset registers with a single, '
  'centralized, role-based application that every relevant member of staff can log into from a web '
  'browser, see only the information relevant to their role, and act on it with a full audit trail '
  'of who did what and when.')
h3('Technology Stack')
data_table(
    ['Layer', 'Technology'],
    [
        ['Backend language', 'PHP, written directly against PDO (no framework such as Laravel is used)'],
        ['Database', 'MySQL / MariaDB (InnoDB storage engine, utf8mb4 character set)'],
        ['Frontend', 'Server-rendered HTML, hand-written CSS, and vanilla JavaScript (no React/Vue)'],
        ['Data access', 'PDO prepared statements with bound parameters throughout (no raw SQL concatenation)'],
        ['Session handling', 'Native PHP sessions, hardened with HttpOnly, SameSite=Lax, and strict mode cookies'],
    ], col_widths=[4.5 * cm, 12.1 * cm]
)
h3('Architecture — How the Code Is Organized')
p('The system follows an MVC-like separation of concerns without using a formal framework:')
bullets([
    '<b>/config</b> &mdash; database connection settings (<font face="Courier">config/database.php</font>) '
    'and global application configuration (<font face="Courier">config/config.php</font>): application name, '
    'base URL, timezone, and session hardening.',
    '<b>/includes</b> &mdash; shared logic used by every page: <font face="Courier">auth.php</font> '
    '(login/role checks), <font face="Courier">functions.php</font> (sanitization, CSRF protection, flash '
    'messages, activity logging, status badges, the asset-status recalculation engine), '
    '<font face="Courier">bootstrap.php</font> (the single include point every page loads first), and '
    '<font face="Courier">/includes/layout</font> (the shared header, sidebar, footer, and 403 &ldquo;Access '
    'Denied&rdquo; page).',
    '<b>/modules</b> &mdash; one folder per feature area (assets, assigned, transfers, maintenance, audits, '
    'requisitions, disposals, users, departments, categories, locations, reports, profile, settings, auth, '
    'dashboard). Each PHP file in a module acts as a controller: it checks the user&rsquo;s role, reads or '
    'writes the database, and then renders its own HTML output.',
    '<b>/static</b> &mdash; the visual theme (<font face="Courier">css/style.css</font>: a dark navy-and-lime-green '
    'theme) and the two JavaScript files that add interactivity on top of the server-rendered pages '
    '(<font face="Courier">js/main.js</font> and <font face="Courier">js/validation.js</font>).',
    '<b>/uploads/logos</b> &mdash; stores an administrator-uploaded university logo, if one has been set.',
    '<b>/database/schema.sql</b> &mdash; the complete database schema plus demonstration seed data, used to '
    'set the system up from scratch.',
])
h3('Database at a Glance')
p('The system is backed by a 15-table relational database. Nine of the tables map directly to the core '
  'asset-lifecycle entities; the remaining six were added to close gaps and to support the workflow and '
  'administrative features actually built into the system (physical location tracking, the disposal '
  'approval workflow, and the admin Settings module).')
data_table(
    ['Table', 'What It Stores'],
    [
        ['roles', 'The four fixed user roles: Admin, Asset Officer, Department Head, Top Management.'],
        ['users', 'Staff accounts: name, email, hashed password, role, department, active/inactive status.'],
        ['departments', 'University departments, each with an optional Head of Department and location.'],
        ['categories', 'Asset categories used to classify assets (e.g. Computers &amp; Laptops, Vehicles).'],
        ['locations', 'Physical building/room locations where an asset can be kept.'],
        ['assets', 'The master asset record: name, category, serial number, location, custodian department, '
                    'purchase date/cost, warranty expiry, status, description.'],
        ['asset_assigned', 'Allocation history: which department/custodian an asset was issued to and when.'],
        ['asset_transfers', 'A record of every time an asset moved from one department to another.'],
        ['asset_maintenance', 'Reported issues/repairs for an asset and their resolution status and cost.'],
        ['asset_audits', 'Physical stock-check records: found / missing / damaged, with remarks.'],
        ['requisitions', 'Department requests for new or replacement assets and their review outcome.'],
        ['asset_disposals', 'Disposal requests and the Top Management approval/rejection decision.'],
        ['settings', 'Key/value store for all admin-configurable system settings.'],
        ['activity_logs', 'A system-wide audit trail of every significant action taken in the system.'],
        ['login_logs', 'A record of every login attempt, successful or failed.'],
    ], col_widths=[3.6 * cm, 13 * cm], small=True
)
p('Every table includes <font face="Courier">created_at</font> and <font face="Courier">updated_at</font> '
  'timestamps, and foreign keys use sensible deletion rules (history tied to an asset cascades with it; '
  'records authored by a user are protected from deletion while that user still exists; optional links, '
  'like an asset&rsquo;s location, are simply cleared if the referenced row is removed).')
story.append(PageBreak())

# ==================================================================
# 2. PURPOSE OF THE SYSTEM
# ==================================================================
h1('2. Purpose of the System')
p('Somali National University owns a large and growing number of physical assets across several '
  'departments: computers, networking equipment, office furniture, lab equipment, and vehicles. Before a '
  'system like this exists, tracking who has what, where it is, whether it needs repair, and when it '
  'should be retired typically depends on scattered paper records or spreadsheets that are easy to lose, '
  'hard to audit, and impossible to control access to.')
p('This system exists to solve that problem by giving the university:')
bullets([
    '<b>One authoritative record per asset</b> that everyone with permission can see, instead of duplicated '
    'or conflicting spreadsheets.',
    '<b>A full history per asset</b> &mdash; every allocation, transfer, maintenance ticket, physical audit, and '
    'disposal request is preserved and visible from that asset&rsquo;s own detail page.',
    '<b>Role-based accountability</b> &mdash; every user only sees and does what their role allows, and every '
    'state-changing action is attributed to the specific user who performed it and time-stamped.',
    '<b>Controlled workflows</b> for the two processes that need independent sign-off: department '
    '<b>requisitions</b> (new asset requests, reviewed by Asset Officers/Admin) and <b>disposals</b> '
    '(reviewed and approved only by Top Management or Admin, so no asset can simply be written off by the '
    'department that wants to get rid of it).',
    '<b>Reporting</b> that lets management see the value and condition of university assets by department, '
    'by category, or by status, without manually compiling the numbers.',
    '<b>An audit trail</b> of both system activity and login attempts, so administrators can investigate any '
    'change or access issue after the fact.',
])
story.append(PageBreak())

# ==================================================================
# 3. MAIN USERS AND THEIR ROLES
# ==================================================================
h1('3. Main Users and Their Roles')
p('The system defines exactly four user roles. Every account in the system belongs to one of these roles, '
  'and the role determines both what appears in the sidebar and, more importantly, what the server will '
  'actually allow the user to do &mdash; every restricted page checks the role again on the server '
  'itself, so the check cannot be bypassed by simply guessing a URL.')
data_table(
    ['Role', 'Who They Typically Are', 'Scope of Responsibility'],
    [
        ['Admin', 'IT/system administrator for the university',
         'Full access to every module, including user management, master data '
         '(departments/categories/locations), and system Settings. The only role that can manage other '
         'user accounts.'],
        ['Asset Officer', 'Staff responsible for day-to-day asset handling',
         'Full operational control of assets, allocations, transfers, maintenance and audits; can request '
         'disposals and review requisitions; cannot manage users or system settings.'],
        ['Department Head', 'Head of an academic or administrative department',
         'A department-scoped view: sees and works with only the assets, allocations, transfers, audits and '
         'requisitions belonging to their own department. Can report maintenance issues and submit '
         'requisitions on behalf of their department, but cannot approve them.'],
        ['Top Management', 'University leadership / senior management',
         'A university-wide, mostly read-only view for oversight and reporting, plus the sole authority '
         '(alongside Admin) to approve or reject asset disposal requests.'],
    ], col_widths=[3.1 * cm, 4.3 * cm, 9.2 * cm]
)
h3('Role Permission Matrix')
data_table(
    ['Module', 'Admin', 'Asset Officer', 'Department Head', 'Top Management'],
    [
        ['Assets', 'Full', 'Full', 'View (own dept. only)', 'View (all)'],
        ['Allocations / Transfers', 'Full', 'Full', 'View (own dept. only)', 'View (all)'],
        ['Maintenance', 'Full', 'Full', 'View + Report issues only', 'View (all)'],
        ['Audits', 'Full', 'Full', 'View (own dept. only)', 'View (all)'],
        ['Requisitions', 'Review', 'Review', 'Submit (own dept.)', 'View (all)'],
        ['Disposals', 'Full', 'Request only', 'View (own dept. only)', 'Approve / Reject'],
        ['Users / Departments / Categories / Locations', 'Full', '&mdash;', '&mdash;', '&mdash;'],
        ['Reports', 'Full', 'Full', 'Own department scope', 'Full (view-only)'],
        ['Settings', 'Full', '&mdash;', '&mdash;', '&mdash;'],
    ], col_widths=[5.3 * cm, 2.2 * cm, 2.9 * cm, 3.4 * cm, 2.8 * cm], small=True
)
p('Every one of these restrictions is enforced <b>server-side</b>, in the PHP file itself, using a '
  '<font face="Courier">requireRole([...])</font> guard defined in <font face="Courier">includes/auth.php</font>. '
  'If a user without the right role opens a restricted page directly by URL, the system returns an '
  'HTTP 403 &ldquo;Access Denied&rdquo; page rather than simply hiding the sidebar link. Hiding a link in the '
  'menu is never the only protection in this system.')
story.append(PageBreak())


# ==================================================================
# 4. AUTHENTICATION / LOGIN
# ==================================================================
h1('4. Authentication / Login')
p('Access to every part of the system requires logging in first. There is no public-facing area or '
  'guest access &mdash; visiting any page while logged out redirects straight to the Login screen, and '
  'after a successful login the user is sent back to the page they originally asked for.')
h3('What It Is')
p('A single login form (<font face="Courier">modules/auth/login.php</font>) that authenticates a user by '
  'university-issued email and password, plus a logout action that ends the session.')
h3('How To Use It — Step by Step')
steps([
    'Open the system in a browser. If not logged in, you are shown the Login page automatically.',
    'Enter your university email address and password.',
    'Click <b>Login</b>.',
    'If your credentials are correct and your account is active, you are taken to the Dashboard (or back '
    'to whatever page you originally tried to open).',
    'To end your session, open the user menu in the top-right corner of any page and click <b>Logout</b>.',
])
h3('What Happens Behind the Scenes')
bullets([
    'The submitted password is checked with <font face="Courier">password_verify()</font> against a '
    'bcrypt hash stored in the database &mdash; passwords are never stored or compared in plain text.',
    'Every login attempt, successful or not, is written to the <b>login_logs</b> table together with the '
    'email that was tried, the outcome, the requesting IP address, and the browser&rsquo;s user agent string.',
    'On a successful login the session ID is regenerated (<font face="Courier">session_regenerate_id()</font>) '
    'to prevent session fixation, and an entry is added to the <b>Activity Log</b>.',
    'A hidden CSRF (Cross-Site Request Forgery) token is submitted with the login form and re-checked on '
    'the server; if it is missing or stale the login is rejected with &ldquo;Your session expired. Please '
    'try again.&rdquo;',
    'If the account&rsquo;s status is <b>inactive</b> (see the Users module), login is refused even with the '
    'correct password.',
])
h3('Error Messages Shown to the User')
data_table(
    ['Situation', 'Message Shown'],
    [
        ['Email or password left blank', 'Email and password are both required.'],
        ['Wrong email or wrong password', 'Invalid email or password.'],
        ['Account has been deactivated by an Admin', 'Your account has been deactivated. Contact the system administrator.'],
        ['Form re-submitted with a stale/missing security token', 'Your session expired. Please try again.'],
    ], col_widths=[7.6 * cm, 9 * cm]
)
h3('Session Security Notes')
bullets([
    'Session cookies are marked <b>HttpOnly</b> (not readable by JavaScript) and <b>SameSite=Lax</b>, and are '
    'marked <b>Secure</b> automatically whenever the site is served over HTTPS.',
    'PHP&rsquo;s strict session mode is enabled, which rejects uninitialized session IDs.',
    'The session length is configurable by the Admin from Settings &rarr; General (<font face="Courier">'
    'session_timeout_minutes</font>).',
])
h3('Permissions')
p('Login/Logout has no role restriction &mdash; every account type uses the same login form and the same '
  'security rules. What differs after login is what each role is allowed to see and do, as described in '
  'Section 3.')
story.append(PageBreak())

# ==================================================================
# 5. DASHBOARD EXPLANATION
# ==================================================================
h1('5. Dashboard Explanation')
p('The Dashboard (<font face="Courier">modules/dashboard/index.php</font>) is the first page every user '
  'lands on after logging in. It gives an at-a-glance summary of the state of the university&rsquo;s '
  'assets, scoped automatically to what that user&rsquo;s role is allowed to see.')
h3('Welcome Line')
p('Shows the logged-in user&rsquo;s name, role, and department (if they belong to one).')
h3('Summary Statistic Cards')
data_table(
    ['Card', 'Shows', 'Visible To'],
    [
        ['Total Assets / Department Assets', 'Count of all assets, or (for a Department Head) only assets '
         'belonging to their own department', 'Everyone'],
        ['Active Allocations', 'Count of allocation records currently marked Active', 'Everyone'],
        ['Pending Maintenance', 'Count of maintenance tickets with status Pending or In Progress '
         '(department-scoped for a Department Head)', 'Everyone'],
        ['Pending Disposals', 'Count of disposal requests awaiting a decision', 'Admin, Asset Officer, Top Management'],
        ['Pending Requisitions', 'Count of requisitions awaiting review (department-scoped for a Department Head)',
         'Admin, Asset Officer, Department Head'],
        ['Total Users', 'Count of all user accounts in the system', 'Admin only'],
    ], col_widths=[3.8 * cm, 8.6 * cm, 4.2 * cm], small=True
)
h3('Visual Insights')
bullets([
    '<b>Assets by Status</b> &mdash; a donut chart plus a matching 2&times;2 tile grid breaking assets down into '
    'Active, Under Repair, and Disposed.',
    '<b>Allocations by Status</b> &mdash; a donut chart breaking current allocation records into Active, '
    'Returned, and In Repair.',
    '<b>Activity — Last 7 Days</b> &mdash; a simple bar chart showing how many logged actions occurred on the '
    'system each day over the past week, for a quick sense of how active the system currently is.',
])
h3('Recent Activity Table')
p('Lists the twelve most recent entries from the system-wide Activity Log: the action taken, the module it '
  'happened in, a short description, who performed it, and when. For a Department Head, this feed is '
  'filtered to only asset-lifecycle events (assets, allocations, transfers, maintenance, audits, '
  'requisitions) so that administrative activity elsewhere in the system is not shown to them.')
h3('Why It Is Included')
p('The Dashboard exists so that any user, regardless of role, can understand the current state of the '
  'assets they are responsible for within seconds of logging in, without having to open several modules '
  'individually.')
story.append(PageBreak())

# ==================================================================
# 6. NAVIGATION / SIDEBAR EXPLANATION
# ==================================================================
h1('6. Navigation / Sidebar Explanation')
p('Every logged-in page shares the same layout: a fixed left <b>sidebar</b> for navigation, a <b>topbar</b> '
  'across the top of the content area, and the module&rsquo;s own content underneath. The sidebar is '
  'role-aware: menu items for modules a user is not allowed to use are not rendered at all.')
h3('Sidebar Structure')
data_table(
    ['Section', 'Menu Items', 'Notes'],
    [
        ['(top)', 'Dashboard', 'Always visible.'],
        ['Asset Lifecycle', 'Assets, Allocations, Transfers, Maintenance, Audits, Disposals',
         'Always visible; content within each is scoped by role as described per module.'],
        ['Requests', 'Requisitions', 'Always visible; who can submit vs. review differs by role.'],
        ['Insights', 'Reports', 'Always visible; report data is department-scoped for a Department Head.'],
        ['Administration', 'Users, Departments, Categories, Locations, Settings',
         'Only rendered for the Admin role. A non-Admin cannot see or reach these pages even by typing '
         'the URL directly &mdash; the server returns a 403 Access Denied page.'],
    ], col_widths=[3.2 * cm, 6.6 * cm, 6.8 * cm], small=True
)
h3('Topbar')
bullets([
    '<b>Menu toggle button</b> &mdash; collapses/expands the sidebar on smaller screens.',
    '<b>Page title</b> &mdash; the name of the module currently open.',
    '<b>Notification bell icon</b> &mdash; present in the topbar of every page. In the current build this is a '
    'visual element only; it is not wired to a live notifications feed or a notifications table in the '
    'database. The system&rsquo;s actual notification mechanism is the on-screen flash message banner '
    '(Section 14 &mdash; Notifications) shown immediately after an action is completed.',
    '<b>User menu</b> &mdash; shows the user&rsquo;s initial, name and role; opens a dropdown with links to '
    '<b>My Profile</b> and <b>Logout</b>.',
])
h3('Branding')
p('The sidebar header shows the university logo (an Admin-uploaded logo if one has been set in Settings, '
  'otherwise the bundled default) alongside the label &ldquo;Asset Management &mdash; University System&rdquo;.')
story.append(PageBreak())

# ==================================================================
# 7. EVERY SYSTEM MODULE
# ==================================================================
h1('7. System Modules')
p('This section documents every functional module in the system in detail, in the same order they '
  'appear in the sidebar. Each module is described using a consistent structure: what it is, why it '
  'exists, what it does, who can use it, what data it manages, its main features, step-by-step usage '
  'instructions, what happens when an action is performed, the exact permission rules that apply, '
  'important notes, and a realistic example of its use at the university.')
story.append(PageBreak())

# ---------------- 7.1 ASSETS ----------------
h2('7.1 Assets')
module_section(
    name='ASSETS',
    what_it_is='The central register of every physical item the university owns &mdash; the master record '
               'that every other module (allocations, transfers, maintenance, audits, disposals) attaches '
               'its history to.',
    purpose='Without one authoritative asset record, the same laptop or vehicle could be tracked '
            'inconsistently in different spreadsheets. This module gives every physical asset exactly one '
            'record, identified by an internal Asset ID, that the rest of the system refers back to.',
    what_it_does=[
        'Registers new assets with their category, serial number, purchase details, warranty, custodian '
        'department, and physical location.',
        'Lets Admin/Asset Officer edit an asset&rsquo;s details later.',
        'Shows a searchable, filterable, paginated list of every asset.',
        'Shows a single detail page per asset with five tabs covering its entire history: Allocations, '
        'Transfers, Maintenance, Audits, and Disposals.',
    ],
    who_uses_rows=[
        ['Admin', 'Register new assets, edit any asset, view all assets.'],
        ['Asset Officer', 'Register new assets, edit any asset, view all assets.'],
        ['Department Head', 'View only, restricted automatically to their own department&rsquo;s assets.'],
        ['Top Management', 'View all assets across the university, no editing.'],
    ],
    data_managed=[
        'Name, Category, Serial Number (optional), Location (building/room), Custodian Department',
        'Purchase Date, Purchase Cost, Warranty Expiry Date',
        'Status: Active, Under Repair, or Disposed',
        'Free-text Description',
    ],
    features=[
        'Search box (matches asset name or serial number) plus dropdown filters for Category, Department '
        '(hidden for Department Head, whose list is already locked to their department), and Status.',
        'Sortable columns (click any column header) and numbered pagination.',
        'Colour-coded status badges: green = Active, amber = Under Repair, red = Disposed.',
        'A single asset detail page consolidating that asset&rsquo;s complete lifecycle history in tabs, so '
        'no one has to cross-reference five different modules to understand one asset&rsquo;s story.',
    ],
    how_to_use=[
        'From the sidebar, click <b>Assets</b>.',
        'To register a new asset, click <b>+ Add Asset</b> (Admin/Officer only).',
        'Fill in Asset Name, Category, Purchase Date, and Purchase Cost (all required); optionally add '
        'Serial No., Location, Custodian Department, Warranty Expiry, and a Description.',
        'Click <b>Add Asset</b> to save. You are taken directly to the new asset&rsquo;s detail page.',
        'To review an asset&rsquo;s full history, click <b>View</b> on any row and use the tabs.',
        'To change an asset&rsquo;s details, click <b>Edit</b>, update the fields, and click <b>Save Changes</b>.',
    ],
    on_action=[
        'Saving a new or edited asset writes/updates the row in the <b>assets</b> table and records an '
        '&ldquo;Create Asset&rdquo; or &ldquo;Update Asset&rdquo; entry in the Activity Log.',
        'A confirmation message (&ldquo;Asset registered successfully.&rdquo; / &ldquo;Asset updated '
        'successfully.&rdquo;) is shown on the next page.',
        'A Department Head who tries to open another department&rsquo;s asset directly by URL is shown the '
        '403 Access Denied page instead of the asset.',
    ],
    permissions_rows=[
        ['Admin / Asset Officer', 'Add, edit, and view every asset.'],
        ['Department Head', 'View only, limited to assets whose Custodian Department matches their own.'],
        ['Top Management', 'View only, all assets, no add/edit.'],
    ],
    notes=[
        'There is no delete function for assets &mdash; assets are retired through the Disposals workflow '
        'instead, which preserves history rather than erasing the record.',
        'The Status field can technically be changed by hand on the Edit form, but the form itself warns '
        'that it is &ldquo;normally set automatically by maintenance/disposal records &mdash; override only '
        'for data correction.&rdquo; In normal day-to-day use, status changes automatically: opening or '
        'closing a maintenance ticket, or approving a disposal request, recalculates it for you (see the '
        'Maintenance and Disposals modules).',
    ],
    example='The ICT Asset Officer receives a newly purchased Dell laptop. They open Assets &rarr; + Add '
            'Asset, enter &ldquo;Dell Latitude 5420 Laptop&rdquo;, select Category &ldquo;Computers &amp; '
            'Laptops&rdquo;, record the serial number, set the Custodian Department to ICT Department, and '
            'enter the purchase cost and warranty date. Once saved, the ICT Department Head can immediately '
            'see this laptop in their own filtered Assets list, and it is now ready to be allocated to a '
            'specific staff member through the Allocations module.',
)

# ---------------- 7.2 ALLOCATIONS ----------------
h2('7.2 Allocations (Asset Assigned)')
module_section(
    name='ALLOCATIONS',
    what_it_is='The record of which department (and optionally which named staff member) currently holds '
               'each asset, and the history of past allocations.',
    purpose='An asset being &ldquo;owned&rdquo; by the university is not the same as knowing who currently '
            'has physical custody of it. This module answers &ldquo;who has this right now, and who has had '
            'it before?&rdquo;',
    what_it_does=[
        'Assigns (issues) an asset to a department and, optionally, a specific custodian (staff member) '
        'within that department.',
        'Tracks the assigned date, and later the outcome when the asset stops being actively held: Returned '
        'or sent to Repair.',
        'Keeps every past allocation as permanent history, visible both from this module&rsquo;s own list and '
        'from the asset&rsquo;s own detail page.',
    ],
    who_uses_rows=[
        ['Admin / Asset Officer', 'Create new allocations; mark an active allocation as Returned or Repair.'],
        ['Department Head', 'View only, limited to allocations for their own department.'],
        ['Top Management', 'View all allocations, no editing.'],
    ],
    data_managed=['Asset, Department the asset is allocated to, optional named Custodian, Assigned Date, '
                  'Return Date, Status (Active / Returned / Repair), Remarks, and who processed the '
                  'allocation.'],
    features=[
        'Filter the allocation list by Department (hidden for Department Head), Custodian, and Status.',
        '&ldquo;Mark Returned&rdquo; and &ldquo;Send to Repair&rdquo; one-click actions on any currently '
        'Active allocation, each with a confirmation prompt.',
        'Every allocation links back to its asset&rsquo;s detail page.',
    ],
    how_to_use=[
        'From the sidebar, click <b>Allocations</b>.',
        'Click <b>+ Assign Asset</b> (Admin/Officer only).',
        'Choose the Asset (disposed assets are excluded from the list), the destination Department, '
        'optionally a specific Custodian, and the Assigned Date; add Remarks if useful.',
        'Click to save &mdash; the asset&rsquo;s current custodian department is updated immediately.',
        'When the asset is later handed back or needs repair, find its row in the list and click '
        '<b>Mark Returned</b> or <b>Send to Repair</b>, and confirm the prompt.',
    ],
    on_action=[
        'Creating an allocation inserts a new row into <b>asset_assigned</b> with status Active, and updates '
        'the asset&rsquo;s Custodian Department to match.',
        'Marking Returned or Repair sets the Return Date to today and updates that allocation record&rsquo;s '
        'status &mdash; it does not, by itself, change the asset&rsquo;s own overall status field '
        '(that is driven only by Maintenance tickets and Disposals, described in their own sections).',
        'Every allocation action is written to the Activity Log.',
    ],
    permissions_rows=[
        ['Admin / Asset Officer', 'Full: assign, mark returned, send to repair.'],
        ['Department Head', 'View only, own department.'],
        ['Top Management', 'View only, all departments.'],
    ],
    notes=['An asset that has already been disposed cannot be selected when creating a new allocation.'],
    example='The ICT Asset Officer assigns Dell Latitude Laptop #1 to the ICT Department and names the ICT '
            'Department Head as the specific custodian. Two years later, when the laptop is handed back to '
            'IT stores, the Officer opens Allocations, finds that row, and clicks <b>Mark Returned</b>.',
)

# ---------------- 7.3 TRANSFERS ----------------
h2('7.3 Transfers')
module_section(
    name='TRANSFERS',
    what_it_is='A log of every time an asset has moved from one department to another.',
    purpose='Departments reorganize, and equipment gets reallocated. This module keeps a permanent, '
            'attributable record of inter-department movements, separate from the day-to-day allocation to '
            'a specific custodian.',
    what_it_does=['Records a transfer of an asset from its current department to a new department, with a '
                  'reason, and immediately updates the asset&rsquo;s custodian department.'],
    who_uses_rows=[
        ['Admin / Asset Officer', 'Create transfers; view all transfer history.'],
        ['Department Head', 'View only, limited to transfers where their department is the source or the '
         'destination.'],
        ['Top Management', 'View all transfer history, no editing.'],
    ],
    data_managed=['Asset, From Department, To Department, Transfer Date, Reason, and who handled the '
                  'transfer.'],
    features=['Full transfer history list, sortable by any column.',
              'Every transfer links back to the asset&rsquo;s own detail page.'],
    how_to_use=[
        'From the sidebar, click <b>Transfers</b>.',
        'Click <b>+ Transfer Asset</b> (Admin/Officer only).',
        'Choose the Asset, the destination department (To Department), the Transfer Date, and a Reason.',
        'Save &mdash; the asset&rsquo;s custodian department is updated to the new department immediately.',
    ],
    on_action=['A new row is added to <b>asset_transfers</b> recording the From/To departments '
               '(From is taken automatically from the asset&rsquo;s current department), the asset&rsquo;s '
               'Custodian Department is updated, and the action is written to the Activity Log.'],
    permissions_rows=[
        ['Admin / Asset Officer', 'Full: create transfers, view all.'],
        ['Department Head', 'View only, own department (as source or destination).'],
        ['Top Management', 'View only, all.'],
    ],
    notes=['A transfer is rejected with a validation error if the asset has already been disposed, or if '
           'the selected destination department is the same as the asset&rsquo;s current department.'],
    example='When the Engineering lecture hall is renovated, its projector is reassigned from the ICT '
            'Department to the Faculty of Engineering. The Asset Officer records this as a Transfer with the '
            'reason &ldquo;Projector moved to Engineering lecture hall&rdquo;, and the projector now appears '
            'under the Faculty of Engineering in the Assets list.',
)
story.append(PageBreak())

# ---------------- 7.4 MAINTENANCE ----------------
h2('7.4 Maintenance')
module_section(
    name='MAINTENANCE',
    what_it_is='The repair/service ticketing module for assets that break down or need servicing.',
    purpose='Assets fail or need routine servicing. This module gives the university a formal record of '
            'every issue reported, what it cost to fix, who fixed it, and how long it took &mdash; and it is '
            'the mechanism that automatically marks an asset as &ldquo;Under Repair&rdquo; while a ticket is '
            'open.',
    what_it_does=[
        'Lets an issue be reported against a specific asset.',
        'Lets Admin/Asset Officer progress a ticket through Pending &rarr; In Progress &rarr; Completed, '
        'recording the completion date, cost, and technician/vendor once resolved.',
        'Automatically recalculates the parent asset&rsquo;s overall status every time a ticket is reported '
        'or updated.',
    ],
    who_uses_rows=[
        ['Admin / Asset Officer', 'Report issues and update tickets through to completion.'],
        ['Department Head', 'Report issues for assets in their own department only; cannot update tickets.'],
        ['Top Management', 'View only, all maintenance activity, university-wide.'],
    ],
    data_managed=['Asset, Issue Description, Reported By, Reported Date, Status (Pending / In Progress / '
                  'Completed), Completed Date, Cost, Technician/Vendor.'],
    features=['Filter the maintenance list by Status.',
              'A &ldquo;Report Issue&rdquo; form open to Admin, Officer, and Department Head alike.',
              'An &ldquo;Update&rdquo; screen (Admin/Officer only) to progress and close a ticket.'],
    how_to_use=[
        'From the sidebar, click <b>Maintenance</b>.',
        'To report a problem, click <b>Report Issue</b>. Choose the affected Asset (a Department Head only '
        'sees assets belonging to their own department), describe the issue, and set the Reported Date.',
        'Submit &mdash; the ticket is created with status Pending.',
        'An Asset Officer or Admin later opens the ticket via <b>Update</b>, changes its Status, and, once '
        'marking it Completed, must also fill in the Completed Date; Cost and Technician/Vendor are optional '
        'at any stage.',
    ],
    on_action=[
        'Reporting an issue inserts a row into <b>asset_maintenance</b> with status Pending, and immediately '
        'triggers the system&rsquo;s status recalculation for that asset &mdash; typically flipping it to '
        '&ldquo;Under Repair&rdquo; in the Assets list.',
        'Updating a ticket&rsquo;s status also triggers the same recalculation, so once every open ticket for '
        'an asset is Completed, the asset automatically returns to &ldquo;Active&rdquo; status (unless an '
        'approved disposal exists, which always takes priority).',
        'Every report and update is written to the Activity Log.',
    ],
    permissions_rows=[
        ['Admin / Asset Officer', 'Report issues; update status, cost, technician, and completion date.'],
        ['Department Head', 'Report issues for their own department&rsquo;s assets only; view all statuses; '
         'cannot update a ticket once submitted.'],
        ['Top Management', 'View only.'],
    ],
    notes=['If a Department Head is somehow presented with an asset from another department (e.g. a stale '
           'page), the server independently re-checks the department match and blocks the submission with '
           '&ldquo;You can only report issues for assets in your own department.&rdquo;',
           'Marking a ticket Completed without a Completed Date is rejected with &ldquo;Completed date is '
           'required when marking as completed.&rdquo; A non-numeric Cost is rejected with &ldquo;Cost must '
           'be a number.&rdquo;'],
    example='The Finance office printer starts jamming repeatedly. The Finance Department Head reports the '
            'issue. The Asset Officer picks it up, sets it to In Progress while a technician is called, and '
            'once fixed, marks it Completed with a $40 cost and &ldquo;ICT Internal Team&rdquo; as the '
            'technician &mdash; at which point the printer automatically reverts from &ldquo;Under Repair&rdquo; '
            'back to &ldquo;Active&rdquo; in the Assets list.',
)

# ---------------- 7.5 AUDITS ----------------
h2('7.5 Audits')
module_section(
    name='AUDITS',
    what_it_is='A log of physical stock-check (verification) records for assets.',
    purpose='Owning an asset on paper is not the same as knowing it still physically exists and is in good '
            'condition. Audits give the university a formal, dated, attributable record every time someone '
            'physically checks an asset.',
    what_it_does=['Records the outcome of a physical check for a specific asset: Found, Missing, or '
                  'Damaged, with optional remarks.'],
    who_uses_rows=[
        ['Admin / Asset Officer', 'Record audits for any asset.'],
        ['Department Head', 'View only, limited to audits for their own department&rsquo;s assets.'],
        ['Top Management', 'View only, all audits.'],
    ],
    data_managed=['Asset, Audit Date, Result (Found / Missing / Damaged), Remarks, Audited By.'],
    features=['A permanent, read-only audit history list per asset and system-wide.',
              'Colour-coded result badges (green = Found, red = Missing/Damaged).'],
    how_to_use=[
        'From the sidebar, click <b>Audits</b>.',
        'Click <b>+ Record Audit</b> (Admin/Officer only).',
        'Choose the Asset being checked, the Audit Date, the Result, and optional Remarks.',
        'Submit to save the record permanently.',
    ],
    on_action=['A new row is added to <b>asset_audits</b> and logged in the Activity Log. Recording an '
               'audit result of Missing or Damaged does <b>not</b> automatically change the asset&rsquo;s '
               'Status field &mdash; that update, if needed (e.g. moving the asset toward disposal), is a '
               'separate, deliberate action taken through the Maintenance or Disposals modules.'],
    permissions_rows=[
        ['Admin / Asset Officer', 'Record new audits; view all.'],
        ['Department Head', 'View only, own department.'],
        ['Top Management', 'View only, all.'],
    ],
    notes=['Audit records are immutable once saved &mdash; there is no edit or delete function for audits, '
           'preserving them as a reliable historical record.',
           'Unlike other add forms, the asset dropdown here includes disposed assets, since an audit can '
           'still legitimately confirm that a disposed asset is indeed gone.'],
    example='During the annual stock take, the Asset Officer physically visits ICT Lab 1, confirms both '
            'laptops are present and in good condition, and records two &ldquo;Found&rdquo; audit entries. '
            'Later, a scanner that cannot be located anywhere is recorded as &ldquo;Missing&rdquo;, giving '
            'management the evidence needed to justify writing it off.',
)

# ---------------- 7.6 REQUISITIONS ----------------
h2('7.6 Requisitions')
module_section(
    name='REQUISITIONS',
    what_it_is='The formal request-and-approval workflow a department uses to ask for new or replacement '
               'assets.',
    purpose='Departments need equipment they don&rsquo;t yet have, or need to justify a replacement. Rather '
            'than an informal request, this module creates a trackable, reviewable request with a clear '
            'outcome.',
    what_it_does=['Lets a department submit a request describing what it needs, how many, and why. Lets '
                  'Admin/Asset Officer review that request and move it through a controlled status '
                  'workflow: Pending &rarr; Approved or Rejected, and Approved &rarr; Issued.'],
    who_uses_rows=[
        ['Admin', 'Submit requisitions on behalf of any department; review (approve/reject/issue) any '
         'requisition.'],
        ['Asset Officer', 'Review (approve/reject/issue) requisitions; cannot submit new ones.'],
        ['Department Head', 'Submit requisitions for their own department; view their own department&rsquo;s '
         'requests only; cannot review.'],
        ['Top Management', 'View all requisitions university-wide, read-only.'],
    ],
    data_managed=['Department, Requester, Category (optional), Quantity, Requisition Date, Purpose, '
                  'Description, Status, Reviewed By, Reviewed Date.'],
    features=['Filter the list by Status.',
              'A locked, pre-filled Department field for a Department Head submitting a request (they can '
              'only request for their own department); a free choice of department for Admin.',
              'A dedicated Review screen for Admin/Officer showing the full request with Approve/Reject '
              'buttons (while Pending) or a Mark Issued button (once Approved).'],
    how_to_use=[
        'To submit a request: from the sidebar click <b>Requisitions</b>, then <b>New Requisition</b>. '
        'Select a Category (optional), Quantity, Requisition Date, and describe the Purpose (and an optional '
        'longer Description). Submit &mdash; the request is created as Pending.',
        'To review a request: open <b>Requisitions</b>, click <b>Review</b> on a Pending request, read the '
        'details, and click <b>Approve</b> or <b>Reject</b> (both ask for confirmation).',
        'Once a request is Approved, return to the list and click <b>Mark Issued</b> once the requested '
        'asset(s) have actually been supplied to the department.',
    ],
    on_action=['Every decision updates the requisition&rsquo;s Status, Reviewed By, and Reviewed Date, and is '
               'written to the Activity Log. The system enforces the status workflow strictly: Pending may '
               'only move to Approved or Rejected; Approved may only move to Issued; Rejected and Issued are '
               'both dead ends with &ldquo;No further action available for this requisition.&rdquo; Any '
               'attempt to skip or reverse a step is rejected with &ldquo;Invalid action for the current '
               'status of this requisition.&rdquo;'],
    permissions_rows=[
        ['Admin', 'Submit for any department; approve, reject, or mark issued.'],
        ['Asset Officer', 'Approve, reject, or mark issued; cannot submit.'],
        ['Department Head', 'Submit for their own department only; cannot review.'],
        ['Top Management', 'View only.'],
    ],
    notes=['Approving or issuing a requisition does not automatically create a new asset record in the '
           'Assets module &mdash; a requisition tracks the request and its approval only. If new equipment '
           'is subsequently purchased to fulfil it, an Asset Officer/Admin registers it separately through '
           'the Assets module.'],
    example='The Library Department Head submits a requisition for two new laptops for the digital catalog '
            'stations, citing that the current PCs are over five years old. The Asset Officer reviews the '
            'request, approves it, and once the laptops arrive and are handed over, marks the requisition '
            'as Issued.',
)

# ---------------- 7.7 DISPOSALS ----------------
h2('7.7 Disposals')
module_section(
    name='DISPOSALS',
    what_it_is='The controlled workflow for retiring an asset permanently &mdash; selling, scrapping, or '
               'donating it.',
    purpose='Writing off a university asset is a decision with financial and accountability implications, so '
            'it must not be something a department or even an Asset Officer can decide alone. This module '
            'requires every disposal to be requested first and then approved by Top Management (or Admin) '
            'before it takes effect.',
    what_it_does=['Lets Admin/Asset Officer request the disposal of an asset with a method and reason. Lets '
                  'Top Management/Admin approve or reject that request. Approval is what finally marks the '
                  'asset as Disposed.'],
    who_uses_rows=[
        ['Admin', 'Request disposals and approve/reject them.'],
        ['Asset Officer', 'Request disposals only; cannot approve their own or anyone else&rsquo;s request.'],
        ['Department Head', 'View only, limited to disposal requests for their own department&rsquo;s assets.'],
        ['Top Management', 'Approve or reject pending disposal requests; view all.'],
    ],
    data_managed=['Asset, Requested By, Request Date, Method (Sold / Scrapped / Donated / Other), Reason, '
                  'Status (Pending / Approved / Rejected), Approved By, Approved Date, Disposal Date, '
                  'Remarks.'],
    features=['Filter the list by Status.',
              'A dedicated Review screen for Top Management/Admin with clearly labelled Approve Disposal / '
              'Reject buttons, each requiring confirmation.'],
    how_to_use=[
        'To request: from the sidebar click <b>Disposals</b>, then <b>Request Disposal</b> (Admin/Officer). '
        'Choose the Asset, Request Date, Method, and Reason, then submit. The request is created as Pending.',
        'To decide: Top Management/Admin opens <b>Disposals</b>, clicks <b>Review</b> on a pending request, '
        'reads the reason, and clicks <b>Approve Disposal</b> (with the warning &ldquo;This will mark it as '
        'disposed&rdquo;) or <b>Reject</b>.',
    ],
    on_action=['Approving a disposal sets its Approved By/Date and Disposal Date to today, and immediately '
               'triggers the system&rsquo;s asset-status recalculation, which sets the asset&rsquo;s Status to '
               '<b>Disposed</b> &mdash; from this point the asset is excluded from the &ldquo;available to '
               'assign/transfer&rdquo; lists elsewhere in the system. Rejecting a request updates its status '
               'only and leaves the asset untouched. Both decisions are written to the Activity Log.'],
    permissions_rows=[
        ['Admin', 'Request and approve/reject.'],
        ['Asset Officer', 'Request only.'],
        ['Department Head', 'View only, own department.'],
        ['Top Management', 'Approve or reject only (does not request).'],
    ],
    notes=['A disposal request cannot be created for an asset that is already Disposed, or for an asset that '
           'already has another disposal request Pending review.',
           'A disposal request that has already been reviewed cannot be reviewed a second time &mdash; '
           'reopening it shows &ldquo;This disposal request has already been reviewed.&rdquo;'],
    example='After repeated engine trouble, the Asset Officer requests disposal of the HR pool car by the '
            '&ldquo;sold&rdquo; method, citing high maintenance cost. Top Management reviews the case and '
            'approves it; the vehicle&rsquo;s status changes to Disposed and it drops out of the active fleet '
            'shown elsewhere in the system.',
)
story.append(PageBreak())

# ---------------- 7.8 REPORTS ----------------
h2('7.8 Reports')
module_section(
    name='REPORTS',
    what_it_is='A set of five pre-built management reports summarizing the asset register from different '
               'angles, each exportable to CSV.',
    purpose='Raw lists of records are hard to use for decision-making. Reports aggregate the same '
            'underlying data into the summaries management actually needs: how much is owned, by whom, in '
            'what condition, and at what cost.',
    what_it_does=['Renders five report types on demand and lets any of them be downloaded as a CSV file for '
                  'use in Excel or for record-keeping.'],
    who_uses_rows=[
        ['Admin / Asset Officer', 'Full access to all five reports, university-wide.'],
        ['Department Head', 'Same five reports, automatically scoped to their own department&rsquo;s data.'],
        ['Top Management', 'Full access to all five reports, university-wide, view/export only.'],
    ],
    data_managed=['Derived, read-only aggregates computed from the assets, categories, departments, '
                  'asset_maintenance, and asset_disposals tables &mdash; Reports does not store data of its '
                  'own.'],
    features=[
        '<b>Assets by Department</b> &mdash; asset count and total purchase value per department.',
        '<b>Assets by Category</b> &mdash; asset count and total purchase value per category.',
        '<b>Assets by Status</b> &mdash; asset count and total value split by Active / Under Repair / Disposed.',
        '<b>Maintenance Cost Report</b> &mdash; every completed maintenance job in a chosen date range, with '
        'a running total cost.',
        '<b>Disposal Report</b> &mdash; every disposal request in a chosen date range with its method, status, '
        'and dates.',
        '<b>Export CSV</b> button on every report, which exports exactly the report and date range currently '
        'on screen.',
    ],
    how_to_use=[
        'From the sidebar, click <b>Reports</b>.',
        'Choose a report tab: Assets by Department, Assets by Category, Assets by Status, Maintenance Cost, '
        'or Disposals.',
        'For the Maintenance Cost and Disposal reports, optionally set a From/To date range and click '
        '<b>Apply</b>.',
        'Click <b>Export CSV</b> at any time to download the currently displayed report as a spreadsheet '
        'file.',
    ],
    on_action=['Viewing a report runs a live, read-only query &mdash; nothing is written to the database. '
               'Exporting writes one entry to the Activity Log (&ldquo;Exported Report: [type]&rdquo;) '
               'before streaming the CSV file to the browser.'],
    permissions_rows=[
        ['Admin / Asset Officer / Top Management', 'View and export all five reports, all departments.'],
        ['Department Head', 'View and export all five reports, limited to their own department&rsquo;s data.'],
    ],
    notes=['There is no Import function anywhere in the system &mdash; Reports only supports exporting data '
           'out to CSV, never importing data in. Bulk data entry is not supported; assets, users, and other '
           'records must be entered one at a time through their respective Add forms.'],
    example='Before the annual budget meeting, the Finance Department Head opens Reports &rarr; Assets by '
            'Department to see the total value of equipment currently assigned to Finance, then exports it '
            'to CSV to attach to the budget report.',
)

# ---------------- 7.9 PROFILE ----------------
h2('7.9 My Profile')
module_section(
    name='PROFILE',
    what_it_is='A self-service page every logged-in user can use to update their own account details and '
               'change their own password.',
    purpose='Users need a way to correct their own name/email or change a password they suspect is '
            'compromised, without needing an Admin to do it for them every time.',
    what_it_does=['Lets a user update their display Name and Email. Lets a user change their own password '
                  'by confirming their current one.'],
    who_uses_rows=[['Every role', 'Every logged-in user can access their own Profile page; there is no '
                     'access to anyone else&rsquo;s profile from here.']],
    data_managed=['Own Name, Email, current Password (write-only, never displayed). Role and Department are '
                  'shown for reference but are read-only on this page &mdash; only an Admin can change a '
                  'user&rsquo;s role or department, from the Users module.'],
    features=['Two tabs: <b>Profile</b> (name/email) and <b>Change Password</b>.',
              'Immediate session update: changing your name or email here updates what is shown in the '
              'topbar right away, without needing to log out and back in.'],
    how_to_use=[
        'Open the user menu (top-right) and click <b>My Profile</b>.',
        'To update details: edit Name and/or Email, then submit.',
        'To change password: switch to the Change Password tab, enter your Current Password, then a New '
        'Password, then confirm it, and submit.',
    ],
    on_action=['Profile updates are written to the users table and logged (&ldquo;Update Profile&rdquo;). '
               'A successful password change is logged as &ldquo;Change Password&rdquo;. The new password is '
               'stored using the same one-way hashing as every other account.'],
    permissions_rows=[['Every role', 'Can edit their own Name, Email, and Password only. Cannot edit Role, '
                        'Department, or any other user&rsquo;s account from this page.']],
    notes=['Changing your email to one already used by another account is rejected with &ldquo;A user with '
           'this email already exists.&rdquo;',
           'Changing your password is rejected if the Current Password does not match '
           '(&ldquo;Current password is incorrect.&rdquo;), if the new password is shorter than 6 characters, '
           'or if the confirmation does not match the new password.'],
    example='A Department Head who recently married and changed their legal name updates their Name on the '
            'Profile page; the new name now appears throughout the system, including in Activity Log entries '
            'for actions they take going forward.',
)
story.append(PageBreak())

# ---------------- 7.10 USERS (Admin only) ----------------
h2('7.10 Users (Administration)')
p('<i>Users, Departments, Categories, Locations, and Settings are grouped under an &ldquo;Administration&rdquo; '
  'section of the sidebar that is only rendered for the Admin role.</i>')
module_section(
    name='USERS',
    what_it_is='The Admin-only screen for creating and managing every staff account in the system.',
    purpose='Someone has to control who is allowed into the system at all, and with which role and '
            'department. This module is the only place accounts are created, edited, or disabled.',
    what_it_does=['Lists every account with search and role filtering. Creates new accounts. Edits an '
                  'existing account&rsquo;s name, email, role, department, or resets its password. Deactivates '
                  'or reactivates an account.'],
    who_uses_rows=[['Admin', 'Full access &mdash; the only role that can see or use this module.']],
    data_managed=['Name, Email, Password (hashed), Role, Department (optional &mdash; Top Management has none), '
                  'Status (Active / Inactive).'],
    features=['Search by name/email and filter by Role.',
              'Add User form with a temporary password set by the Admin.',
              'Edit User form that can reset a password by entering a new one, or leave it blank to keep '
              'the existing password unchanged.',
              'A single Deactivate/Reactivate toggle button per user (label and colour change depending on '
              'current status) instead of a hard delete, so history authored by that user is preserved.'],
    how_to_use=[
        'From the sidebar (Admin only), click <b>Users</b>.',
        'To add someone: click <b>+ Add User</b>, fill in Name, Email, a temporary Password (minimum 6 '
        'characters), Role, and optionally a Department, then submit.',
        'To edit someone: click <b>Edit</b> on their row, change any field, and leave Password blank to '
        'keep it unchanged or fill it in to reset it.',
        'To disable access: click <b>Deactivate</b> on their row and confirm. To restore access later, click '
        '<b>Reactivate</b> on the same row.',
    ],
    on_action=['Creating a user inserts a new row with status Active and a bcrypt-hashed password. Editing '
               'updates the chosen fields (and the password only if a new one was supplied). Deactivating/'
               'reactivating simply flips the Status column. Every one of these actions is written to the '
               'Activity Log, and a deactivated user is refused at the login screen even with the correct '
               'password.'],
    permissions_rows=[['Admin', 'Create, edit, deactivate, and reactivate any account except cannot '
                        'deactivate their own currently logged-in account.']],
    notes=['An Admin cannot deactivate their own account (the Deactivate button does not even appear on '
           'their own row) &mdash; this prevents an Admin from accidentally locking themselves out.',
           'Creating or editing a user with an email already used by another account is rejected with '
           '&ldquo;A user with this email already exists.&rdquo;',
           'There is no hard delete for user accounts &mdash; only Deactivate, which preserves their '
           'authorship on historical records (assets they registered, maintenance they reported, etc.).'],
    example='When an Asset Officer leaves the university, the Admin opens Users, finds their account, and '
            'clicks Deactivate rather than deleting it &mdash; their name still correctly appears against '
            'every allocation, transfer, and maintenance record they created in the past.',
)
story.append(PageBreak())

# ---------------- 7.11-7.13 MASTER DATA (Admin only) ----------------
h2('7.11 – 7.13 Master Data: Departments, Categories, Locations')
p('Departments, Categories, and Locations are three small, Admin-only reference (&ldquo;master data&rdquo;) '
  'modules that every other module depends on for its dropdown lists. They share an identical design: a '
  'single list page with modal pop-up forms for adding and editing, and a protected delete.')
data_table(
    ['Module', 'What It Stores', 'Fields', 'Used By'],
    [
        ['Departments', 'Every university department that can own assets or submit requests.',
         'Department Name (required), Head of Department (optional, chosen from users with the Department '
         'Head role), Location (free text).',
         'Assets, Allocations, Transfers, Requisitions, Disposals, Reports, and the Users module (a user&rsquo;s '
         'own department).'],
        ['Categories', 'The classification used to group similar assets.',
         'Category Name (required), Description (optional).',
         'Assets, Requisitions, and the &ldquo;Assets by Category&rdquo; report.'],
        ['Locations', 'The physical building/room an asset can be kept in.',
         'Location Name (required), Building (optional), Room (optional).',
         'Assets (the Location field on the asset form).'],
    ], col_widths=[2.6 * cm, 4.6 * cm, 5 * cm, 4.4 * cm], small=True
)
h3('How To Use (applies to all three)')
steps([
    'From the sidebar (Admin only), open Departments, Categories, or Locations.',
    'Click <b>+ Add Department</b> / <b>+ Add Category</b> / <b>+ Add Location</b> to open a pop-up form, '
    'fill in the fields, and save.',
    'Click <b>Edit</b> on any row to open the same pop-up pre-filled with that record&rsquo;s current values.',
    'Click <b>Delete</b> on a row to remove it, after confirming the pop-up warning.',
])
h3('What Happens When You Perform an Action')
bullets([
    'Each list shows a live count of how many assets currently reference that department/category/location.',
    'Add and Edit are logged to the Activity Log (e.g. &ldquo;Create Department&rdquo;, &ldquo;Update '
    'Category&rdquo;).',
    'Delete is protected: if any asset, user, or history record still references the item, the deletion is '
    'blocked and a clear message is shown instead of a database error &mdash; for example, deleting a '
    'department in use shows &ldquo;This department cannot be deleted because it still has assets, users, '
    'or history linked to it.&rdquo; Categories and Locations show the equivalent message for their own '
    'linked assets.',
])
h3('Permissions')
p('All three modules are restricted to the Admin role only; no other role can see or reach them, even by '
  'direct URL.')
h3('Important Notes')
bullets([
    'A blank Name field is rejected on all three (&ldquo;Department name is required.&rdquo;, etc.).',
    'Removing a Department Head&rsquo;s assignment, or leaving a department without one, is allowed &mdash; '
    'Head of Department is always optional.',
])
h3('Real-World Example')
story.append(Table([[Paragraph(
    'Before the system can be used at all, the Admin sets up the university&rsquo;s six departments '
    '(ICT, Finance, Library, Registrar, HR, Faculty of Engineering), six asset categories (Computers &amp; '
    'Laptops, Networking Equipment, Office Furniture, Office Electronics, Lab Equipment, Vehicles), and the '
    'physical locations where assets are kept (ICT Lab 1, ICT Store, Library Reading Hall, and so on) so '
    'that every dropdown elsewhere in the system has real options to choose from.',
    ParagraphStyle('ex2', parent=Body, spaceAfter=0))]],
    colWidths=[16.6 * cm],
    style=TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), colors.HexColor('#eef7e1')),
        ('BOX', (0, 0), (-1, -1), 0.6, GREEN_DARK),
        ('LEFTPADDING', (0, 0), (-1, -1), 10), ('RIGHTPADDING', (0, 0), (-1, -1), 10),
        ('TOPPADDING', (0, 0), (-1, -1), 8), ('BOTTOMPADDING', (0, 0), (-1, -1), 8),
    ])))
story.append(Spacer(1, 14))
story.append(PageBreak())

# ---------------- 7.14 SETTINGS (Admin only, with sub-tabs) ----------------
h2('7.14 Settings (Administration)')
p('Settings is an Admin-only module organized into six tabs, sharing one sub-navigation bar: '
  '<b>General</b>, <b>Email/SMTP</b>, <b>Backup &amp; Restore</b>, <b>Activity Logs</b>, <b>Login Logs</b>, '
  'and <b>System Info</b>.')

h3('7.14.1 General Settings')
field_label('What it does:', 'Controls university branding and system-wide behaviour.')
bullets([
    'University Name and System Name (shown throughout the interface and on the login page).',
    'Academic Year, Language, Timezone, and Date Format.',
    'Session Timeout (minutes) &mdash; how long an inactive session stays logged in.',
    'Records Per Page &mdash; how many rows the paginated lists (e.g. Assets) show per page.',
    'Theme (light/dark).',
    'Logo upload &mdash; replaces the default university logo shown on the login page and sidebar '
    '(JPG/PNG/GIF, maximum 2MB).',
    '<b>Maintenance Mode</b> &mdash; a single checkbox that, when enabled, blocks access to the entire '
    'system for every role except Admin, showing a plain &ldquo;System is under maintenance&rdquo; message '
    'to everyone else. This is the one setting on this page with a system-wide effect beyond its own screen.',
])
field_label('Permissions:', 'Admin only.')
field_label('Important notes:', 'Session Timeout and Records Per Page must both be numeric and at least '
             '5; a non-numeric or too-small value is rejected with a specific error message. An oversized or '
             'wrong-format logo upload is rejected before being saved.')

h3('7.14.2 Email / SMTP Settings')
field_label('What it does:', 'Stores the outgoing mail server configuration (host, port, username, '
             'password, encryption type) that the system would use to send email.')
field_label('Permissions:', 'Admin only.')
field_label('Important notes:', 'The SMTP Password field can be left blank on an edit to keep the existing '
             'saved password; entering a new value replaces it. If a host is provided, the port must be '
             'numeric.')

h3('7.14.3 Backup &amp; Restore')
field_label('What it does:', 'Lets the Admin download a complete SQL backup of the live database, or '
             'restore the database from a previously downloaded .sql file.')
bullets([
    '<b>Download Backup (.sql)</b> &mdash; generates a full SQL dump (every table structure and every row of '
    'data) and downloads it as an attachment named after the database and the current date/time.',
    '<b>Restore Database</b> &mdash; uploads a .sql file (maximum 50MB) and executes it directly against the '
    'live database, overwriting existing data.',
])
field_label('Permissions:', 'Admin only.')
field_label('Important notes:', 'Restoring is a destructive, irreversible action &mdash; the confirmation '
             'prompt explicitly warns &ldquo;This will overwrite existing data with the contents of the '
             'uploaded backup. This cannot be undone.&rdquo; Only files with a .sql extension are accepted. '
             'A failed restore (e.g. an invalid SQL file) shows the underlying database error message so the '
             'Admin can diagnose it. There is no automatic scheduled backup &mdash; every backup is a manual, '
             'on-demand download initiated by the Admin.')

h3('7.14.4 Activity Logs')
field_label('What it does:', 'A searchable, paginated view of the system-wide audit trail introduced in '
             'Section 5 &mdash; every significant action taken by every user, with the module it happened '
             'in, a description, the acting user (or &ldquo;System&rdquo;), their IP address, and the '
             'timestamp.')
field_label('Main features:', 'Filter by Module; 25 records per page with numbered pagination.')
field_label('Permissions:', 'Admin only.')

h3('7.14.5 Login Logs')
field_label('What it does:', 'A record of every login attempt made against the system, successful or '
             'failed, including the email address that was tried, outcome, IP address, browser user agent, '
             'and timestamp.')
field_label('Main features:', 'Filter by Status (Success/Failed); 25 records per page with pagination.')
field_label('Why it is included:', 'Gives the Admin visibility into repeated failed login attempts, which '
             'can indicate someone guessing a password, and a record of exactly when a given account last '
             'logged in successfully.')
field_label('Permissions:', 'Admin only.')

h3('7.14.6 System Info')
field_label('What it does:', 'A read-only diagnostics screen showing the PHP version, MySQL version, web '
             'server software, operating system, server time and timezone, and disk space (free/total/used) '
             'on the server hosting the application.')
field_label('Why it is included:', 'Gives a technical evaluator or the Admin a quick way to confirm the '
             'server environment the system is actually running on, without needing shell/SSH access.')
field_label('Permissions:', 'Admin only. No actions can be performed from this page &mdash; it is purely '
             'informational.')
story.append(PageBreak())

# ==================================================================
# 8. MAIN WORKFLOWS
# ==================================================================
h1('8. Main Workflows')
p('The individual modules above combine into a small number of end-to-end workflows that represent how '
  'the system is actually used day to day. This section walks through the three most important ones.')

h2('8.1 Asset Lifecycle Workflow')
p('This is the core workflow the entire system is built around &mdash; the journey of a single physical '
  'asset from purchase to retirement.')
steps([
    '<b>Registration</b> &mdash; an Asset Officer or Admin registers the asset in the Assets module with its '
    'purchase details.',
    '<b>Allocation</b> &mdash; the asset is assigned to a department (and optionally a named custodian) in '
    'the Allocations module.',
    '<b>(Optional) Transfer</b> &mdash; if organizational needs change, the asset is moved to a different '
    'department via the Transfers module, which updates its custodian automatically.',
    '<b>(Ongoing) Maintenance</b> &mdash; if the asset breaks down, a ticket is reported and worked through '
    'to completion in the Maintenance module; this automatically flips the asset&rsquo;s status to '
    '&ldquo;Under Repair&rdquo; and back to &ldquo;Active&rdquo; again once resolved.',
    '<b>(Periodic) Audit</b> &mdash; during stock checks, the asset&rsquo;s physical presence and condition are '
    'confirmed in the Audits module.',
    '<b>Disposal</b> &mdash; once the asset reaches end of life, a disposal request is raised and, once '
    'approved by Top Management or Admin, the asset&rsquo;s status becomes permanently &ldquo;Disposed&rdquo;.',
])
p('At every step, the complete history remains visible from that one asset&rsquo;s detail page.')

h2('8.2 Requisition Approval Workflow')
p('This is how a department asks for something it does not yet have.')
steps([
    'A Department Head (or Admin, on a department&rsquo;s behalf) submits a requisition describing what is '
    'needed and why. Status: <b>Pending</b>.',
    'An Asset Officer or Admin reviews it and either <b>Approves</b> or <b>Rejects</b> it.',
    'If approved, once the requested item(s) are actually supplied to the department, the reviewer marks '
    'the requisition <b>Issued</b>.',
    'Rejected and Issued are both final states &mdash; no further action can be taken on that requisition.',
])

h2('8.3 Disposal Approval Workflow')
p('This is how an asset is retired, and it deliberately separates the person who wants to get rid of an '
  'asset from the person with authority to approve it.')
steps([
    'An Asset Officer or Admin requests disposal of a specific asset, choosing a method (Sold, Scrapped, '
    'Donated, Other) and giving a reason. Status: <b>Pending</b>.',
    'Top Management (or Admin) reviews the request and either <b>Approves</b> or <b>Rejects</b> it.',
    'Approval immediately and automatically sets the asset&rsquo;s status to <b>Disposed</b> across the whole '
    'system. Rejection leaves the asset exactly as it was, still Active or Under Repair.',
])
story.append(PageBreak())

# ==================================================================
# 9. DATA AND DATABASE OVERVIEW
# ==================================================================
h1('9. Data and Database Overview')
p('The system stores all of its data in a single MySQL/MariaDB database named '
  '<font face="Courier">university_asset_management</font>, made up of 15 InnoDB tables (see Section 1 for '
  'the full table-by-table description). A few relationships are worth calling out specifically because '
  'they explain behaviour seen throughout the system:')
bullets([
    '<b>Every user belongs to one role</b> (Admin, Asset Officer, Department Head, or Top Management) and, '
    'optionally, one department. This single link is what every role-based restriction in the system is '
    'built on.',
    '<b>Every asset belongs to one category</b>, and optionally one location and one custodian department. '
    'These are the three dropdowns filled by the Categories, Locations, and Departments master-data modules.',
    '<b>An asset&rsquo;s Status is a derived value</b>, not something typed in freely on a day-to-day basis: '
    'the system recalculates it automatically &mdash; Disposed wins if an approved disposal exists, otherwise '
    'Under Repair wins if any maintenance ticket is still open, otherwise the asset is Active. This '
    'recalculation runs automatically every time a maintenance ticket or disposal decision changes.',
    '<b>Deleting a record that other records depend on is prevented</b>, not silently allowed &mdash; for '
    'example a department that still has assets, users, or history cannot be deleted; the system shows a '
    'clear message instead of corrupting the data.',
    '<b>Every table has created_at/updated_at timestamps</b>, which is how the system can always show '
    '&ldquo;when&rdquo; a record was made or last changed without any extra work by the user.',
])
p('The <b>activity_logs</b> and <b>login_logs</b> tables are append-only audit tables: nothing in the system '
  'ever edits or deletes an existing log entry, so they form a permanent, tamper-evident record of who did '
  'what, and who tried to log in, over the lifetime of the system.')
story.append(PageBreak())

# ==================================================================
# 10. REPORTS AND ANALYTICS
# ==================================================================
h1('10. Reports and Analytics')
p('Analytical information appears in two places in the system:')
bullets([
    '<b>The Dashboard</b> (Section 5) gives real-time, at-a-glance analytics every time a user logs in: '
    'status donuts, a 7-day activity chart, and key counters &mdash; automatically scoped to what that '
    'user&rsquo;s role is allowed to see.',
    '<b>The Reports module</b> (Section 7.8) gives on-demand, exportable management reports: Assets by '
    'Department, Assets by Category, Assets by Status, Maintenance Cost, and Disposals, each downloadable '
    'as a CSV file.',
])
p('There is no separate charting/business-intelligence tool built into the system beyond these two areas; '
  'together they cover the reporting needs the system was designed for.')
story.append(PageBreak())

# ==================================================================
# 11. NOTIFICATIONS
# ==================================================================
h1('11. Notifications')
p('The system&rsquo;s actual notification mechanism is the on-screen <b>flash message banner</b> that '
  'appears at the top of the page immediately after an action is completed &mdash; for example '
  '&ldquo;Asset registered successfully.&rdquo;, &ldquo;Disposal request submitted for Top Management '
  'approval.&rdquo;, or an error such as &ldquo;Invalid email or password.&rdquo; These banners come in '
  'three colour-coded types:')
data_table(
    ['Type', 'Colour', 'Used For'],
    [
        ['Success', 'Green', 'Confirms an action completed correctly (e.g. a record was saved).'],
        ['Error', 'Red', 'Explains why an action was rejected (validation failure, permission issue, etc.).'],
        ['Info', 'Green-tinted (accent)', 'Neutral status information (e.g. a request was already reviewed).'],
    ], col_widths=[2.6 * cm, 3.5 * cm, 10.5 * cm]
)
p('A <b>notification bell icon</b> is also present in the topbar of every page (see Section 6). In the '
  'current build this icon is a visual placeholder only &mdash; there is no notifications table in the '
  'database and no backend feed wired up to it. It does not indicate unread items and clicking it has no '
  'effect. Any future real-time notification feature (e.g. alerting a Department Head the moment a '
  'requisition is approved) would need to be built on top of this icon; today, users learn about outcomes '
  'either from the flash banner shown at the moment they act, or by checking the relevant module&rsquo;s list '
  '(e.g. opening Requisitions to see if a request&rsquo;s status has changed).')
story.append(PageBreak())

# ==================================================================
# 12. SETTINGS — SUMMARY
# ==================================================================
h1('12. Settings — Summary')
p('All system-wide configuration lives in the Admin-only Settings module, documented in full in Section '
  '7.14. In summary, Settings covers: <b>General</b> (branding, locale, session length, pagination, theme, '
  'logo, maintenance mode), <b>Email/SMTP</b> (outgoing mail server configuration), <b>Backup &amp; '
  'Restore</b> (manual database backup/restore), <b>Activity Logs</b> and <b>Login Logs</b> (the system&rsquo;s '
  'audit trails), and <b>System Info</b> (read-only server diagnostics). No non-Admin role can see or reach '
  'any Settings page.')
story.append(PageBreak())

# ==================================================================
# 13. SECURITY FEATURES
# ==================================================================
h1('13. Security Features')
p('Security is implemented consistently across the codebase rather than bolted on in one place. The '
  'following protections apply system-wide:')
data_table(
    ['Protection', 'How It Works'],
    [
        ['SQL Injection prevention', 'Every database query in the system uses PDO prepared statements with '
         'bound parameters &mdash; no query is ever built by concatenating raw user input into SQL text.'],
        ['Password security', 'Passwords are hashed with PHP&rsquo;s <font face="Courier">password_hash()</font> '
         'and checked with <font face="Courier">password_verify()</font>; the plain password is never stored '
         'or logged.'],
        ['Cross-Site Scripting (XSS) prevention', 'Every piece of data rendered back into a page is escaped '
         'through the system&rsquo;s <font face="Courier">e()</font> helper (<font face="Courier">'
         'htmlspecialchars</font>) before being output.'],
        ['CSRF (Cross-Site Request Forgery) protection', 'Every form that changes data includes a hidden, '
         'session-bound CSRF token, and the receiving page rejects the submission if the token is missing or '
         'does not match.'],
        ['Server-side role enforcement', 'Every restricted page independently re-checks the logged-in '
         'user&rsquo;s role in PHP itself (<font face="Courier">requireRole()</font>) before doing anything '
         'else &mdash; a hidden sidebar link is never the only protection, and a direct URL guess is met with '
         'a 403 Access Denied page.'],
        ['Department-level data scoping', 'Department Head accounts have their visibility of assets, '
         'allocations, transfers, maintenance, audits, requisitions, and reports locked to their own '
         'department at the query level, not just hidden in the interface.'],
        ['Session hardening', 'Strict-mode sessions, HttpOnly cookies, SameSite=Lax, Secure cookies '
         'automatically over HTTPS, and session ID regeneration on every successful login.'],
        ['Account status enforcement', 'A deactivated account is refused at login even with the correct '
         'password.'],
        ['Self-lockout prevention', 'An Admin cannot deactivate their own account.'],
        ['Full audit trail', 'Every significant action (create/update/approve/reject/export/etc.) is written '
         'to the Activity Log with the acting user, module, description, and IP address; every login attempt '
         '(success or failure) is written to the Login Log.'],
        ['No error detail leakage', 'Display of PHP errors to the browser is disabled in configuration; '
         'errors are written only to a private server-side log file.'],
        ['File upload validation', 'The logo upload (Settings &rarr; General) and backup restore upload '
         '(Settings &rarr; Backup &amp; Restore) both validate file extension and size before accepting a file.'],
    ], col_widths=[4.6 * cm, 12 * cm], small=True
)
h3('Points Worth Flagging for Future Hardening')
p('As an honest technical note for evaluators: two areas of the current build carry more inherent risk than '
  'the rest of the system and would be worth extra attention before a production rollout beyond the '
  'university&rsquo;s trusted Admin group:')
bullets([
    'The <b>Restore Database</b> feature (Settings &rarr; Backup &amp; Restore) executes an uploaded .sql '
    'file directly against the live database with no sandboxing beyond checking its file extension and '
    'size &mdash; it should only ever be used with backup files the Admin generated themselves.',
    'The <b>SMTP password</b> is stored in the same plain key/value settings table as other configuration, '
    'rather than in a separately encrypted secret store.',
])
story.append(PageBreak())

# ==================================================================
# 14. BACKUP AND RESTORE
# ==================================================================
h1('14. Backup and Restore')
p('Backup and Restore is available to the Admin role from Settings &rarr; Backup &amp; Restore (Section '
  '7.14.3). There is no scheduled/automatic backup job built into the system &mdash; both operations are '
  'manual and Admin-initiated:')
bullets([
    '<b>Download Backup</b> generates a complete, timestamped .sql file containing every table&rsquo;s '
    'structure and data, and downloads it straight to the Admin&rsquo;s computer. This should be done '
    'regularly and the file stored safely outside the server.',
    '<b>Restore Database</b> uploads a previously downloaded .sql file and runs it against the live '
    'database, completely overwriting current data. This is irreversible and should only be used to recover '
    'from a serious data problem, using a backup file the Admin trusts.',
])
story.append(PageBreak())

# ==================================================================
# 15. SEARCH, FILTER, ADD, EDIT, DELETE, VIEW, EXPORT, IMPORT
# ==================================================================
h1('15. Search, Filter, Add, Edit, Delete, View, Export and Import Functions')
p('These generic actions are not implemented identically everywhere &mdash; each module only offers the '
  'ones that make sense for the kind of record it manages. The table below is a precise, module-by-module '
  'reference so nothing is assumed to exist where it does not.')
data_table(
    ['Module', 'Search / Filter', 'Add', 'Edit', 'Delete', 'View', 'Export'],
    [
        ['Assets', 'Search (name/serial) + Category/Dept./Status filters', 'Yes', 'Yes', 'No*', 'Yes (with full history tabs)', 'No'],
        ['Allocations', 'Department/Custodian/Status filters', 'Yes (Assign)', 'No', 'No', 'Yes', 'No'],
        ['Transfers', '&mdash;', 'Yes', 'No', 'No', 'Yes', 'No'],
        ['Maintenance', 'Status filter', 'Yes (Report Issue)', 'Yes (Update status/cost)', 'No', 'Yes', 'No'],
        ['Audits', '&mdash;', 'Yes (Record Audit)', 'No', 'No', 'Yes', 'No'],
        ['Requisitions', 'Status filter', 'Yes (Submit)', 'No (Review only: Approve/Reject/Issue)', 'No', 'Yes', 'No'],
        ['Disposals', 'Status filter', 'Yes (Request)', 'No (Review only: Approve/Reject)', 'No', 'Yes', 'No'],
        ['Users', 'Search (name/email) + Role filter', 'Yes', 'Yes', 'No (Deactivate/Reactivate instead)', 'Yes', 'No'],
        ['Departments / Categories / Locations', '&mdash;', 'Yes', 'Yes', 'Yes (blocked if in use)', 'Yes', 'No'],
        ['Reports', 'Report-type tabs + date range (2 of 5 reports)', '&mdash;', '&mdash;', '&mdash;', 'Yes', 'Yes (CSV)'],
        ['Activity Logs / Login Logs', 'Module or Status filter + pagination', '&mdash;', '&mdash;', '&mdash;', 'Yes', 'No'],
        ['Settings', '&mdash;', '&mdash;', 'Yes', '&mdash;', 'Yes', 'No'],
    ], col_widths=[3.5 * cm, 4.4 * cm, 2 * cm, 2.6 * cm, 2 * cm, 1.6 * cm, 1.5 * cm], small=True
)
p('<i>* Assets have no delete function by design &mdash; an asset is retired through the Disposals '
  'workflow instead, which keeps its full history intact rather than erasing it.</i>')
h3('Import')
p('<b>There is no data-import feature anywhere in the system.</b> Every record &mdash; assets, users, '
  'departments, categories, locations, allocations, transfers, maintenance tickets, audits, requisitions, '
  'and disposals &mdash; must be entered one at a time through its own Add form. The only direction data '
  'moves in bulk is <b>out</b> of the system, via the CSV export in Reports. Bulk/spreadsheet import is not '
  'part of the current build and should not be assumed to exist.')
h3('Search and Sort Behaviour')
p('Search and dropdown filters (where available) work by reloading the page with the filter values in the '
  'URL, so a filtered view can be bookmarked or shared as a link. Independently, every list table&rsquo;s '
  'column headers are clickable to sort the currently visible page of rows client-side (ascending, then '
  'descending) &mdash; this sorting only reorders the rows already on screen and does not fetch additional '
  'data from the server.')
story.append(PageBreak())

# ==================================================================
# 16. ERROR MESSAGES AND IMPORTANT SYSTEM BEHAVIOUR
# ==================================================================
h1('16. Error Messages and Important System Behaviour')
p('The system favours clear, specific, user-facing error messages over generic failures. The most common '
  'ones a user will encounter are grouped below by cause.')
h3('Access and Session Errors')
data_table(
    ['Message', 'Cause'],
    [
        ['403 — Access Denied (full page)', 'You tried to open a page your role is not allowed to use, '
         'either via a hidden menu item or by typing the URL directly.'],
        ['Your session expired. Please try again.', 'A form was submitted with a missing or stale CSRF '
         'security token, usually because the page was open a long time or was reloaded from a cached copy.'],
        ['Invalid or expired form submission. Please go back and try again.', 'A generic version of the same '
         'CSRF check failing on a non-login form.'],
        ['Your account has been deactivated. Contact the system administrator.', 'An Admin has deactivated '
         'the account being used to log in.'],
    ], col_widths=[7 * cm, 9.6 * cm]
)
h3('Validation Errors (representative examples)')
data_table(
    ['Message', 'Where It Appears'],
    [
        ['This field is required. / [Field] is required.', 'Any required field left blank, across every '
         'add/edit form.'],
        ['Purchase cost must be a number.', 'Assets — Add/Edit.'],
        ['This asset has been disposed and cannot be assigned/transferred.', 'Allocations/Transfers — Add.'],
        ['Completed date is required when marking as completed.', 'Maintenance — Update.'],
        ['A disposal request for this asset is already pending review.', 'Disposals — Request.'],
        ['This department/category/location cannot be deleted because it still has assets/users/history '
         'linked to it.', 'Departments/Categories/Locations — Delete.'],
        ['A user with this email already exists.', 'Users — Add/Edit, and Profile.'],
        ['Current password is incorrect.', 'Profile — Change Password.'],
        ['Invalid action for the current status of this requisition.', 'Requisitions — Review.'],
    ], col_widths=[9.5 * cm, 7.1 * cm], small=True
)
h3('Notable System Behaviour')
bullets([
    'Every add/edit form is validated twice: once in the browser (for a fast, friendly experience) and '
    'again, independently and authoritatively, on the server &mdash; the browser check can be bypassed, the '
    'server check cannot.',
    'Whenever a database delete would break a link to another table, the system catches that failure and '
    'shows a plain-English explanation instead of a raw database error.',
    'If <b>Maintenance Mode</b> is switched on in Settings, every role except Admin is shown a plain '
    '&ldquo;System is under maintenance&rdquo; page instead of the application, until it is switched off '
    'again.',
])
story.append(PageBreak())

# ==================================================================
# 17. FREQUENTLY USED WORKFLOWS — QUICK REFERENCE
# ==================================================================
h1('17. Frequently Used Workflows — Quick Reference')
p('A condensed, task-oriented index of the day-to-day actions covered in detail earlier in this document.')
data_table(
    ['I want to…', 'Go to', 'Who can do this'],
    [
        ['Register a new asset', 'Assets → + Add Asset', 'Admin, Asset Officer'],
        ['Give an asset to a department/staff member', 'Allocations → + Assign Asset', 'Admin, Asset Officer'],
        ['Move an asset to another department', 'Transfers → + Transfer Asset', 'Admin, Asset Officer'],
        ['Report a broken asset', 'Maintenance → Report Issue', 'Admin, Asset Officer, Department Head'],
        ['Close out a repair ticket', 'Maintenance → Update', 'Admin, Asset Officer'],
        ['Record a stock-check result', 'Audits → + Record Audit', 'Admin, Asset Officer'],
        ['Ask for a new/replacement asset', 'Requisitions → New Requisition', 'Admin, Department Head'],
        ['Approve/reject a requisition', 'Requisitions → Review', 'Admin, Asset Officer'],
        ['Ask to retire an asset', 'Disposals → Request Disposal', 'Admin, Asset Officer'],
        ['Approve/reject a disposal', 'Disposals → Review', 'Admin, Top Management'],
        ['See the value of assets by department', 'Reports → Assets by Department', 'Everyone (scoped)'],
        ['Download data as a spreadsheet', 'Reports → Export CSV', 'Everyone (scoped)'],
        ['Add a staff account', 'Users → + Add User', 'Admin'],
        ['Turn off a leaver&rsquo;s access', 'Users → Deactivate', 'Admin'],
        ['Update my own name/email', 'Profile', 'Everyone'],
        ['Change my own password', 'Profile → Change Password', 'Everyone'],
        ['Change the university name/logo', 'Settings → General', 'Admin'],
        ['Take a database backup', 'Settings → Backup & Restore → Download Backup', 'Admin'],
        ['See who logged in recently', 'Settings → Login Logs', 'Admin'],
        ['See what changed in the system recently', 'Settings → Activity Logs (or Dashboard)', 'Admin (full) / Everyone (own feed on Dashboard)'],
    ], col_widths=[6.3 * cm, 5.6 * cm, 4.7 * cm], small=True
)
story.append(PageBreak())

# ==================================================================
# 18. FINAL SYSTEM SUMMARY
# ==================================================================
h1('18. Final System Summary')
p('The University Asset Management System gives Somali National University a single, role-secured, '
  'web-based application for tracking every physical asset it owns from the moment it is purchased to the '
  'moment it is retired. Instead of scattered spreadsheets, it provides:')
bullets([
    'One authoritative record per asset, with its complete allocation, transfer, maintenance, audit, and '
    'disposal history in one place.',
    'Four clearly separated roles &mdash; Admin, Asset Officer, Department Head, Top Management &mdash; each '
    'seeing and able to do exactly what their responsibilities require, enforced on the server, not just '
    'hidden in the menu.',
    'Two independent approval workflows &mdash; requisitions and disposals &mdash; that require sign-off from '
    'someone other than the person who initiated the request, so no single person can unilaterally acquire '
    'or write off university property.',
    'Automatic, self-maintaining asset status (Active / Under Repair / Disposed), so staff do not need to '
    'remember to update a status field by hand.',
    'Five exportable management reports and a live role-scoped Dashboard for day-to-day and strategic '
    'oversight.',
    'A full, tamper-evident audit trail of both system activity and login attempts.',
    'Consistent, defence-in-depth security: prepared statements, hashed passwords, output escaping, CSRF '
    'protection, and server-side role checks applied uniformly across every module.',
])
p('What the system deliberately does <b>not</b> include, as of this documentation, is worth stating plainly '
  'for anyone evaluating it: there is no bulk data import, no live/real-time notification feed behind the '
  'topbar bell icon, no scheduled automatic backups, and no mobile app &mdash; the system is a '
  'browser-based, desktop-and-tablet-first web application. These are reasonable directions for future '
  'development, not gaps in what is documented here.')
p('This document reflects the system exactly as it was built and verified at the time of writing, module '
  'by module, permission by permission, based on a direct review of the application&rsquo;s source code and '
  'a live walkthrough of its running instance.')
story.append(Spacer(1, 20))

# ==================================================================
# DOCUMENT TEMPLATE / BUILD
# ==================================================================
PAGE_W, PAGE_H = A4
MARGIN = 2 * cm

def draw_cover_background(canv, doc):
    canv.saveState()
    canv.setFillColor(NAVY)
    canv.rect(0, 0, PAGE_W, PAGE_H, fill=1, stroke=0)
    canv.setFillColor(GREEN)
    canv.rect(0, PAGE_H - 0.45 * cm, PAGE_W, 0.45 * cm, fill=1, stroke=0)
    canv.rect(0, 0, PAGE_W, 0.45 * cm, fill=1, stroke=0)
    canv.restoreState()

def draw_normal_background(canv, doc):
    canv.saveState()
    canv.setFillColor(NAVY)
    canv.rect(0, PAGE_H - 0.28 * cm, PAGE_W, 0.28 * cm, fill=1, stroke=0)
    canv.setFont('Helvetica', 7.6)
    canv.setFillColor(GRAY)
    canv.drawRightString(PAGE_W - MARGIN, PAGE_H - MARGIN + 0.35 * cm,
                          'University Asset Management System — Somali National University')
    canv.restoreState()

frame_cover = Frame(0, 0, PAGE_W, PAGE_H, id='cover', leftPadding=2.6 * cm, rightPadding=2.6 * cm,
                     topPadding=1 * cm, bottomPadding=1 * cm)
frame_normal = Frame(MARGIN, MARGIN + 0.6 * cm, PAGE_W - 2 * MARGIN, PAGE_H - 2 * MARGIN - 1.2 * cm,
                      id='normal')

class DocTemplate(BaseDocTemplate):
    def afterFlowable(self, flowable):
        if isinstance(flowable, Paragraph):
            text = flowable.getPlainText()
            style_name = flowable.style.name
            if style_name == 'H1' and not text.startswith('Table of Contents'):
                key = 'h1-%s' % id(flowable)
                self.canv.bookmarkPage(key)
                self.canv.addOutlineEntry(text, key, level=0, closed=False)
                self.notify('TOCEntry', (0, text, self.page, key))
            elif style_name == 'H2':
                key = 'h2-%s' % id(flowable)
                self.canv.bookmarkPage(key)
                self.canv.addOutlineEntry(text, key, level=1, closed=True)
                self.notify('TOCEntry', (1, text, self.page, key))


# Rebuild doc as our TOC-aware subclass with the same templates.
doc = DocTemplate(OUT, pagesize=A4,
                   leftMargin=MARGIN, rightMargin=MARGIN, topMargin=MARGIN, bottomMargin=MARGIN,
                   title='University Asset Management System — Documentation',
                   author='Somali National University',
                   subject='System Documentation')
doc.addPageTemplates([
    PageTemplate(id='Cover', frames=[frame_cover], onPage=draw_cover_background),
    PageTemplate(id='Normal', frames=[frame_normal], onPage=draw_normal_background),
])


class NumberedCanvas(canvas_mod.Canvas):
    def __init__(self, *args, **kwargs):
        canvas_mod.Canvas.__init__(self, *args, **kwargs)
        self._saved_page_states = []

    def showPage(self):
        self._saved_page_states.append(dict(self.__dict__))
        self._startPage()

    def save(self):
        total = len(self._saved_page_states)
        for state in self._saved_page_states:
            self.__dict__.update(state)
            if self._pageNumber > 1:
                self._draw_footer(total)
            canvas_mod.Canvas.showPage(self)
        canvas_mod.Canvas.save(self)

    def _draw_footer(self, total):
        self.saveState()
        self.setStrokeColor(colors.HexColor('#d8dde3'))
        self.setLineWidth(0.6)
        self.line(MARGIN, MARGIN - 0.35 * cm, PAGE_W - MARGIN, MARGIN - 0.35 * cm)
        self.setFont('Helvetica', 8)
        self.setFillColor(GRAY)
        self.drawString(MARGIN, MARGIN - 0.7 * cm,
                         'University Asset Management System — Somali National University')
        self.drawRightString(PAGE_W - MARGIN, MARGIN - 0.7 * cm,
                              'Page %d of %d' % (self._pageNumber - 1, total - 1))
        self.restoreState()


story.insert(0, NextPageTemplate('Cover'))
doc.multiBuild(story, canvasmaker=NumberedCanvas)
print('PDF built:', OUT)
