    </div> <!-- End .app-container -->
    
    <script src="/smart-planner/assets/js/script.js?v=<?php echo time(); ?>"></script>
    <?php if (isset($page_js)): ?>
    <script src="/smart-planner/assets/js/<?php echo $page_js; ?>?v=<?php echo time(); ?>"></script>
    <?php endif; ?>
    <!-- Service Worker Unregister Script (Fixes Cache Issues) -->
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(function(registrations) {
                for(let registration of registrations) {
                    registration.unregister();
                }
            });
        }
    </script>
</body>
</html>
