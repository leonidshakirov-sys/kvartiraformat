<section class="page-hero">
    <div class="container">
        <nav class="breadcrumbs"><a href="/">Главная</a><span>Портфолио</span></nav>
        <h1><?= e($h1) ?></h1>
        <p>Пока нет фотографий выполненных объектов, здесь собраны иллюстративные интерьеры после ремонта. Они помогают понять уровень отделки и не выдаются за работы компании.</p>
    </div>
</section>
<section class="section">
    <div class="container">
        <div class="portfolio-grid">
            <?php foreach ($portfolio as $index => $item): $headingTag = 'h2'; require __DIR__ . '/../partials/portfolio-card.php'; endforeach; ?>
        </div>
    </div>
</section>
