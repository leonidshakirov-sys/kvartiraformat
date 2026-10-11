<section class="admin-panel"><h1>Заявки</h1>
<div class="geo-list" style="margin-bottom:16px">
    <a href="/admin/leads" class="<?= ($leadFilter ?? '') === '' ? 'is-active' : '' ?>">Все</a>
    <a href="/admin/leads?type=apartment">Ремонт квартир</a>
    <a href="/admin/leads?type=house">Ремонт домов</a>
    <a href="/admin/leads?type=commercial">Коммерческий ремонт</a>
</div>
<?php require __DIR__ . '/leads-table.php'; ?>
<h2>Изменить статус</h2>
<form method="post" class="admin-form inline"><?= App\Core\Csrf::field() ?><input type="hidden" name="action" value="lead_status"><input name="id" type="number" placeholder="ID заявки" required><select name="status"><option value="new">Новая</option><option value="in_progress">В работе</option><option value="done">Закрыта</option><option value="spam">Спам</option></select><input name="manager_comment" placeholder="Комментарий менеджера"><button class="btn btn-primary">Сохранить</button></form>
</section>
