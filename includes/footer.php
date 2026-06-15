    </main>
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Smart Event Planner. All rights reserved.</p>
    </footer>
    <script src="/smart_event_planner/assets/js/script.js"></script>
    <?php if (isset($page_js)): ?>
    <script src="/smart_event_planner/assets/js/<?php echo $page_js; ?>"></script>
    <?php endif; ?>
</body>
</html>
