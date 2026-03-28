<?php
// Determine the current page for active class
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<aside class="admin-sidebar">
    <div class="sidebar-logo">Solicitador</div>
    <nav class="sidebar-nav">
        <a href="/admin/index.php" class="sidebar-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">&#x1F4CA; Painel</a>
        <a href="/admin/appointments.php" class="sidebar-link <?= $currentPage === 'appointments.php' || $currentPage === 'appointment-detail.php' ? 'active' : '' ?>">&#x1F4C5; Consultas</a>
        <a href="/admin/services.php" class="sidebar-link <?= $currentPage === 'services.php' || $currentPage === 'service-edit.php' ? 'active' : '' ?>">&#x1F4CB; Serviços</a>
        <a href="/admin/banners.php" class="sidebar-link <?= $currentPage === 'banners.php' || $currentPage === 'banner-edit.php' ? 'active' : '' ?>">&#x1F5BC; Banners</a>
        <a href="/admin/menu.php" class="sidebar-link <?= $currentPage === 'menu.php' || $currentPage === 'menu-edit.php' ? 'active' : '' ?>">&#x2630; Menu</a>
        <a href="/admin/blog.php" class="sidebar-link <?= $currentPage === 'blog.php' || $currentPage === 'blog-edit.php' ? 'active' : '' ?>">&#x1F4DD; Blog</a>
        <a href="/admin/documents.php" class="sidebar-link <?= $currentPage === 'documents.php' || $currentPage === 'document-edit.php' ? 'active' : '' ?>">&#x1F4C4; Documentos</a>
        <a href="/admin/messages.php" class="sidebar-link <?= $currentPage === 'messages.php' || $currentPage === 'message-detail.php' ? 'active' : '' ?>">&#x2709; Mensagens</a>
        <a href="/admin/settings.php" class="sidebar-link <?= $currentPage === 'settings.php' ? 'active' : '' ?>">&#x2699; Definições</a>
    </nav>
    <div class="sidebar-footer">
        <span><?= sanitize($_SESSION['admin_username'] ?? '') ?></span>
        <a href="/admin/logout.php" class="sidebar-link">&#x1F6AA; Sair</a>
    </div>
</aside>
