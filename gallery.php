<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

$lang = get_language();
$db = get_db();

// Check if gallery is enabled
$galleryActive = get_setting($db, 'gallery_active');
if ($galleryActive === '0') {
    redirect('/');
}

$groupId = isset($_GET['group']) ? (int)$_GET['group'] : 0;

$pageTitle = lang('gallery.title') . ' | Solicitador';
require_once __DIR__ . '/includes/header.php';

if ($groupId > 0):
    // Show images within a specific group
    try {
        $groupStmt = $db->prepare("SELECT * FROM gallery_groups WHERE id = ? AND active = 1");
        $groupStmt->execute([$groupId]);
        $group = $groupStmt->fetch();
    } catch (PDOException $e) {
        $group = null;
    }

    if ($group):
        $groupName = $lang === 'fr' ? $group['name_fr'] : $group['name_pt'];
        try {
            $imgStmt = $db->prepare("SELECT * FROM gallery_images WHERE group_id = ? AND active = 1 ORDER BY sort_order ASC, created_at DESC");
            $imgStmt->execute([$groupId]);
            $images = $imgStmt->fetchAll();
        } catch (PDOException $e) {
            $images = [];
        }
?>

<section class="gallery-section" id="gallery">
    <div class="container">
        <a href="/gallery.php" class="back-link" style="display:inline-block;margin-bottom:1.5rem;color:var(--navy);text-decoration:none;font-weight:600;">&larr; <?= lang('gallery.back') ?></a>
        <h2 class="section-title"><?= sanitize($groupName) ?></h2>
        <?php if (!empty($images)): ?>
        <div class="gallery-grid">
            <?php foreach ($images as $gImage):
                $gTitle = $lang === 'fr' ? ($gImage['title_fr'] ?: $gImage['title_pt']) : $gImage['title_pt'];
            ?>
            <div class="gallery-item" onclick="openLightbox('<?= sanitize($gImage['image_url']) ?>', '<?= sanitize(addslashes($gTitle)) ?>')">
                <img src="<?= sanitize($gImage['image_url']) ?>" alt="<?= sanitize($gTitle) ?>" loading="lazy">
                <?php if (!empty($gTitle)): ?>
                <div class="gallery-item-caption">
                    <strong><?= sanitize($gTitle) ?></strong>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-center" style="margin-top:2rem;color:var(--text-muted);"><?= lang('gallery.no_images') ?></p>
        <?php endif; ?>
    </div>
</section>

<?php
    else:
        // Group not found
?>
<section class="gallery-section">
    <div class="container">
        <a href="/gallery.php" class="back-link" style="display:inline-block;margin-bottom:1.5rem;color:var(--navy);text-decoration:none;font-weight:600;">&larr; <?= lang('gallery.back') ?></a>
        <p class="text-center" style="margin-top:2rem;color:var(--text-muted);"><?= lang('gallery.group_not_found') ?></p>
    </div>
</section>
<?php
    endif;

else:
    // Show all groups
    try {
        $groupsStmt = $db->query("SELECT * FROM gallery_groups WHERE active = 1 ORDER BY sort_order ASC, created_at DESC");
        $groups = $groupsStmt->fetchAll();
    } catch (PDOException $e) {
        $groups = [];
    }
?>

<section class="gallery-section" id="gallery">
    <div class="container">
        <h2 class="section-title"><?= lang('gallery.title') ?></h2>
        <?php if (!empty($groups)): ?>
        <div class="gallery-groups-grid">
            <?php foreach ($groups as $grp):
                $grpName = $lang === 'fr' ? $grp['name_fr'] : $grp['name_pt'];
                // Get image count
                try {
                    $countStmt = $db->prepare("SELECT COUNT(*) as cnt FROM gallery_images WHERE group_id = ? AND active = 1");
                    $countStmt->execute([$grp['id']]);
                    $imgCount = $countStmt->fetch()['cnt'];
                } catch (PDOException $e) {
                    $imgCount = 0;
                }
                // Get cover image
                $coverUrl = $grp['cover_image_url'] ?? '';
                if (empty($coverUrl)) {
                    try {
                        $coverStmt = $db->prepare("SELECT image_url FROM gallery_images WHERE group_id = ? AND active = 1 ORDER BY sort_order ASC LIMIT 1");
                        $coverStmt->execute([$grp['id']]);
                        $coverRow = $coverStmt->fetch();
                        $coverUrl = $coverRow ? $coverRow['image_url'] : '';
                    } catch (PDOException $e) {
                        $coverUrl = '';
                    }
                }
            ?>
            <a href="/gallery.php?group=<?= (int)$grp['id'] ?>" class="gallery-group-card">
                <div class="gallery-group-image">
                    <?php if ($coverUrl): ?>
                    <img src="<?= sanitize($coverUrl) ?>" alt="<?= sanitize($grpName) ?>" loading="lazy">
                    <?php else: ?>
                    <div class="gallery-group-placeholder">&#x1F5BC;</div>
                    <?php endif; ?>
                </div>
                <div class="gallery-group-info">
                    <h3><?= sanitize($grpName) ?></h3>
                    <span class="gallery-group-count"><?= (int)$imgCount ?> <?= lang('gallery.images') ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-center" style="margin-top:2rem;color:var(--text-muted);"><?= lang('gallery.no_groups') ?></p>
        <?php endif; ?>
    </div>
</section>

<?php endif; ?>

<!-- Lightbox -->
<div class="lightbox-overlay" id="lightboxOverlay" onclick="closeLightbox()">
    <button class="lightbox-close" onclick="closeLightbox()" aria-label="Close">&times;</button>
    <div class="lightbox-content" onclick="event.stopPropagation()">
        <img id="lightboxImg" src="" alt="">
        <p id="lightboxCaption"></p>
    </div>
</div>

<script>
function openLightbox(src, caption) {
    var overlay = document.getElementById('lightboxOverlay');
    var img = document.getElementById('lightboxImg');
    var cap = document.getElementById('lightboxCaption');
    img.src = src;
    img.alt = caption || '';
    cap.textContent = caption || '';
    cap.style.display = caption ? 'block' : 'none';
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    var overlay = document.getElementById('lightboxOverlay');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLightbox();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
