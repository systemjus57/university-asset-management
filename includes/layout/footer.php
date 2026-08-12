        </main>
        <footer class="app-footer">
            &copy; <?= date('Y') ?> <?= e($uniName ?? 'Somali National University') ?> — <?= e(t('footer.system_name')) ?>
        </footer>
    </div>
</div>
<script src="<?= APP_URL ?>/static/js/main.js?v=<?= filemtime(APP_ROOT . '/static/js/main.js') ?>"></script>
</body>
</html>
