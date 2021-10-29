<div class='my-5'>
    <h3 class='text-danger mt-0'>
        <i class='fa fa-exclamation-triangle'></i> <b><?= t('У нас проблемы') ?>&hellip;</b>
    </h3>
    <?= $message ?>
    <small><?= $trace ?></small>
    <a class='btn btn-outline-secondary get-back' href='#'>
        <i class='fa fa-angle-double-left'></i> <?= t('Вернуться') ?>
    </a>
</div>