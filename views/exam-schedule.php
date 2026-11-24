<header class="page-header">
    <h1>Jadwal Ujian</h1>
    <p class="muted">Jadwal berjalan dan mendatang dalam WIB.</p>
</header>
<section class="panel" aria-label="Daftar jadwal ujian">
    <?php require __DIR__ . '/exam-list.php'; ?>
    <?php
    $pagingPath = '/jadwal';
    $pagingFilters = [];
    $pagingKey = 'hal';
    $pagingLabel = 'Halaman jadwal';
    require __DIR__ . '/community-pagination.php';
    ?>
</section>
