<?php
/**
 * Translation strings for the app shell (sidebar, topbar, footer), the
 * login page, the dashboard, and the Settings > General page — the
 * screens every user sees. Other modules aren't translated yet; add
 * keys here and wrap the string in t('key.name') to extend coverage.
 */
return [
    // Sidebar brand
    'brand.title'    => ['en' => 'Asset Management', 'so' => 'Maaraynta Hantida'],
    'brand.subtitle' => ['en' => 'University System', 'so' => 'Nidaamka Jaamacadda'],

    // Sidebar nav
    'nav.dashboard'     => ['en' => 'Dashboard', 'so' => 'Guud Ahaan'],
    'nav.section.assets' => ['en' => 'Asset Lifecycle', 'so' => 'Wareegga Hantida'],
    'nav.assets'        => ['en' => 'Assets', 'so' => 'Hantida'],
    'nav.assigned'      => ['en' => 'Allocations', 'so' => 'Qaybinta'],
    'nav.transfers'     => ['en' => 'Transfers', 'so' => 'Wareejinta'],
    'nav.maintenance'   => ['en' => 'Maintenance', 'so' => 'Dayactirka'],
    'nav.audits'        => ['en' => 'Audits', 'so' => 'Baaritaanka'],
    'nav.disposals'     => ['en' => 'Disposals', 'so' => 'Ka Saaridda'],
    'nav.section.requests' => ['en' => 'Requests', 'so' => 'Codsiyada'],
    'nav.requisitions'  => ['en' => 'Requisitions', 'so' => 'Codsiyada Alaabta'],
    'nav.section.insights' => ['en' => 'Insights', 'so' => 'Falanqaynta'],
    'nav.reports'       => ['en' => 'Reports', 'so' => 'Warbixinnada'],
    'nav.section.admin' => ['en' => 'Administration', 'so' => 'Maamulka'],
    'nav.users'         => ['en' => 'Users', 'so' => 'Isticmaalayaasha'],
    'nav.departments'   => ['en' => 'Departments', 'so' => 'Waaxyaha'],
    'nav.categories'    => ['en' => 'Categories', 'so' => 'Qaybaha'],
    'nav.locations'     => ['en' => 'Locations', 'so' => 'Goobaha'],
    'nav.settings'      => ['en' => 'Settings', 'so' => 'Dejinta'],

    // Topbar
    'topbar.notifications'    => ['en' => 'Notifications', 'so' => 'Ogeysiisyada'],
    'topbar.all_caught_up'    => ['en' => "You're all caught up.", 'so' => 'Wax cusub ma jiraan.'],
    'topbar.my_profile'       => ['en' => 'My Profile', 'so' => 'Astaantayda'],
    'topbar.logout'           => ['en' => 'Logout', 'so' => 'Ka Bax'],

    // Pending alerts (functions.php: getPendingAlerts) — aggregate counts awaiting action
    'alert.maintenance' => ['en' => 'open maintenance ticket', 'so' => 'shaqo dayactir oo furan'],
    'alert.disposal'    => ['en' => 'pending disposal', 'so' => 'codsi ka saarid ah oo sugaya'],
    'alert.requisition' => ['en' => 'pending requisition', 'so' => 'codsi alaab oo sugaya'],

    // Personal decision notifications — %s is filled with a category/asset name via sprintf()
    'alert.requisition.approved' => ['en' => 'Your requisition for %s was approved.', 'so' => 'Codsigaaga %s ayaa la ansixiyay.'],
    'alert.requisition.rejected' => ['en' => 'Your requisition for %s was rejected.', 'so' => 'Codsigaaga %s waa la diiday.'],
    'alert.requisition.issued'   => ['en' => 'Your requisition for %s has been issued.', 'so' => 'Codsigaaga %s waa la bixiyay.'],
    'alert.disposal.approved'    => ['en' => 'Your disposal request for %s was approved.', 'so' => 'Codsigaaga ka saaridda %s ayaa la ansixiyay.'],
    'alert.disposal.rejected'    => ['en' => 'Your disposal request for %s was rejected.', 'so' => 'Codsigaaga ka saaridda %s waa la diiday.'],
    'alert.maintenance.completed' => ['en' => 'Your reported issue for %s has been resolved.', 'so' => 'Dhibaatada aad ka warbixisay %s waa la xaliyay.'],

    // Footer
    'footer.system_name' => ['en' => 'University Asset Management System', 'so' => 'Nidaamka Maaraynta Hantida Jaamacadda'],

    // Login page
    'login.subtitle'  => ['en' => 'University Asset Management System', 'so' => 'Nidaamka Maaraynta Hantida Jaamacadda'],
    'login.field'     => ['en' => 'Email or Username', 'so' => 'Iimaylka ama Magaca Isticmaale'],
    'login.password'  => ['en' => 'Password', 'so' => 'Furaha Sirta ah'],
    'login.button'    => ['en' => 'Login', 'so' => 'Gal'],
    'login.hint'      => ['en' => 'Use your university-issued email or username and password. Contact the Admin if you cannot access your account.', 'so' => 'Isticmaal iimaylka ama magaca isticmaalaha ee jaamacadu ku siisay iyo furahaaga sirta ah. Haddii aadan gali karin xisaabtaada, la xiriir Maamulaha.'],
    'login.err.session_expired' => ['en' => 'Your session expired. Please try again.', 'so' => 'Waqtigu wuu dhacay. Fadlan isku day mar kale.'],
    'login.err.required'        => ['en' => 'Username/email and password are both required.', 'so' => 'Magaca isticmaale/iimaylka iyo furaha sirta ah labaduba waa lama huraan.'],
    'login.err.invalid'         => ['en' => 'Invalid username/email or password.', 'so' => 'Magaca isticmaale/iimaylka ama furaha sirta ah waa khalad.'],
    'login.err.deactivated'     => ['en' => 'Your account has been deactivated. Contact the system administrator.', 'so' => 'Xisaabtaada waa la joojiyay. La xiriir maamulaha nidaamka.'],

    // Dashboard
    'dash.welcome'              => ['en' => 'Welcome back,', 'so' => 'Soo dhawow,'],
    'dash.total_assets'         => ['en' => 'Total Assets', 'so' => 'Wadarta Hantida'],
    'dash.department_assets'    => ['en' => 'Department Assets', 'so' => 'Hantida Waaxda'],
    'dash.active_allocations'   => ['en' => 'Active Allocations', 'so' => 'Qaybinta Firfircoon'],
    'dash.pending_maintenance'  => ['en' => 'Pending Maintenance', 'so' => 'Dayactir Sugaya'],
    'dash.pending_disposals'    => ['en' => 'Pending Disposals', 'so' => 'Ka Saarid Sugaya'],
    'dash.pending_requisitions' => ['en' => 'Pending Requisitions', 'so' => 'Codsiyo Sugaya'],
    'dash.total_users'          => ['en' => 'Total Users', 'so' => 'Wadarta Isticmaalayaasha'],
    'dash.assets_by_status'     => ['en' => 'Assets by Status', 'so' => 'Hantida Xaaladeeda'],
    'dash.allocations_by_status' => ['en' => 'Allocations by Status', 'so' => 'Qaybinta Xaaladeeda'],
    'dash.status.active'        => ['en' => 'Active', 'so' => 'Firfircoon'],
    'dash.status.under_repair'  => ['en' => 'Under Repair', 'so' => 'Dayactir ku jira'],
    'dash.status.disposed'      => ['en' => 'Disposed', 'so' => 'La saaray'],
    'dash.status.returned'      => ['en' => 'Returned', 'so' => 'La soo celiyay'],
    'dash.tile.active_assets'   => ['en' => 'Active Assets', 'so' => 'Hantida Firfircoon'],
    'dash.tile.department_total' => ['en' => 'Department Total', 'so' => 'Wadarta Waaxda'],
    'dash.activity_7_days'      => ['en' => 'Activity — Last 7 Days', 'so' => 'Firfircoonaanta — 7-dii Maalmood ee u dambeeyay'],
    'dash.recent_activity'      => ['en' => 'Recent Activity', 'so' => 'Firfircoonaanta Dhawaan'],
    'dash.table.action'         => ['en' => 'Action', 'so' => 'Ficil'],
    'dash.table.module'         => ['en' => 'Module', 'so' => 'Qaybta'],
    'dash.table.description'    => ['en' => 'Description', 'so' => 'Sharaxaad'],
    'dash.table.by'             => ['en' => 'By', 'so' => 'Qofka Sameeyay'],
    'dash.table.when'           => ['en' => 'When', 'so' => 'Waqtiga'],
    'dash.no_activity'          => ['en' => 'No recent activity recorded yet.', 'so' => 'Wali lama duubin wax firfircoonaan ah.'],
    'assets'                    => ['en' => 'Assets', 'so' => 'Hantida'],
    'allocations'                => ['en' => 'Allocations', 'so' => 'Qaybinta'],

    // Weekday abbreviations (dashboard activity chart)
    'day.Mon' => ['en' => 'Mon', 'so' => 'Isn'],
    'day.Tue' => ['en' => 'Tue', 'so' => 'Tal'],
    'day.Wed' => ['en' => 'Wed', 'so' => 'Arb'],
    'day.Thu' => ['en' => 'Thu', 'so' => 'Kha'],
    'day.Fri' => ['en' => 'Fri', 'so' => 'Jim'],
    'day.Sat' => ['en' => 'Sat', 'so' => 'Sab'],
    'day.Sun' => ['en' => 'Sun', 'so' => 'Axd'],

    // Settings sub-navigation (shared across all Settings pages)
    'settings.title_prefix'   => ['en' => 'Settings —', 'so' => 'Dejinta —'],
    'settings.tab.general'    => ['en' => 'General', 'so' => 'Guud'],
    'settings.tab.smtp'       => ['en' => 'Email (SMTP)', 'so' => 'Iimaylka (SMTP)'],
    'settings.tab.backup'     => ['en' => 'Backup & Restore', 'so' => 'Kaydinta & Soo Celinta'],
    'settings.tab.logs'       => ['en' => 'Activity Logs', 'so' => 'Diiwaanka Firfircoonaanta'],
    'settings.tab.login_logs' => ['en' => 'Login Logs', 'so' => 'Diiwaanka Galitaanka'],
    'settings.tab.system'     => ['en' => 'System Info', 'so' => 'Macluumaadka Nidaamka'],

    // Settings > General form
    'settings.university_name'    => ['en' => 'University Name', 'so' => 'Magaca Jaamacadda'],
    'settings.system_name'        => ['en' => 'System Name', 'so' => 'Magaca Nidaamka'],
    'settings.academic_year'      => ['en' => 'Academic Year', 'so' => 'Sanad Dugsiyeedka'],
    'settings.language'           => ['en' => 'Language', 'so' => 'Luqadda'],
    'settings.timezone'           => ['en' => 'Time Zone', 'so' => 'Waqtiga Aagga'],
    'settings.date_format'        => ['en' => 'Date Format', 'so' => 'Qaabka Taariikhda'],
    'settings.session_timeout'    => ['en' => 'Session Timeout (minutes)', 'so' => 'Waqtiga Fadhiga (daqiiqado)'],
    'settings.records_per_page'   => ['en' => 'Records Per Page', 'so' => 'Diiwaannada Bog kasta'],
    'settings.theme'              => ['en' => 'Theme', 'so' => 'Muuqaalka'],
    'settings.theme.light'        => ['en' => 'Light', 'so' => 'Iftiin'],
    'settings.theme.dark'         => ['en' => 'Dark', 'so' => 'Madow'],
    'settings.logo'               => ['en' => 'University Logo', 'so' => 'Sumadda Jaamacadda'],
    'settings.maintenance_mode'   => ['en' => 'Enable Maintenance Mode (blocks access for everyone except Admin)', 'so' => 'Daar Habka Dayactirka (wuxuu xannibayaa gelitaanka qof kasta marka laga reebo Maamulaha)'],
    'settings.save'               => ['en' => 'Save Settings', 'so' => 'Kaydi Dejinta'],
    'settings.err.university_name' => ['en' => 'University name is required.', 'so' => 'Magaca jaamacadda waa lama huraan.'],
    'settings.err.session_timeout'  => ['en' => 'Session timeout must be at least 5 minutes.', 'so' => 'Waqtiga fadhigu waa inuu ugu yaraan ahaadaa 5 daqiiqo.'],
    'settings.err.records_per_page' => ['en' => 'Records per page must be at least 5.', 'so' => 'Diiwaannada bog kasta waa inay ugu yaraan ahaadaan 5.'],
    'settings.saved'                => ['en' => 'Settings saved successfully.', 'so' => 'Dejinta si guul leh ayaa loo kaydiyay.'],
];
