<?php header('Content-Type: application/xml') ?>
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
    <url>
        <loc><?= url('/') ?></loc>
    </url>
    <url>
        <loc><?= url('/articles') ?></loc>
    </url>
    <?php if ($CategoryCount > 0): ?>
        <?php foreach ($CategoryList as $CategoryItem): ?>
            <url>
                <loc><?= url('/articles?category=' . $CategoryItem['id']) ?></loc>
            </url>
        <?php endforeach ?>
    <?php endif ?>
    <?php if ($PostCount > 0): ?>
        <?php foreach ($PostList as $PostDetail): ?>
            <url>
                <loc><?= url('/articles/' . $PostDetail['id'] . '-' . $PostDetail['slug'] . '.html') ?></loc>
                <lastmod><?=date('Y-m-d\TH:i:s+00:00', $PostDetail['time'])?></lastmod>
            </url>
        <?php endforeach ?>
    <?php endif ?>
    <?php if ($ChapterCount > 0): ?>
        <?php foreach ($ChapterList as $ChapterDetail): ?>
            <url>
                <loc><?= url('/view-chap/' . $ChapterDetail['id'] . '-' . $ChapterDetail['slug'] . '.html') ?></loc>
                <lastmod><?=date('Y-m-d\TH:i:s+00:00', $ChapterDetail['time'])?></lastmod>
            </url>
        <?php endforeach ?>
    <?php endif ?>
</urlset>