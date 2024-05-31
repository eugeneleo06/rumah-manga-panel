    
    <?php
        include 'nav_links.php';
        $current_page = basename($_SERVER['PHP_SELF']);
    ?>
    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.html">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-laugh-wink"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Rumah Manga Panel</div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <?php foreach ($nav_links as $link): ?>
                <!-- Nav Item - Dashboard -->
                <li class="nav-item <?php echo ($current_page == $link['url']) ? 'active' : ''; ?>">
                    <a class="nav-link" href="<?php echo $link['url']; ?>" >
                        <span><?php echo $link['text']; ?></span>
                    </a>
                </li>
            <?php endforeach; ?>

        </ul>
        <!-- End of Sidebar -->