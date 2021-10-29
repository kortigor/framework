<?php
$this->title = $header;
?>
<div class='my-5'>
    <h1 class='text-danger mt-0'><?= $header ?></h1>
    <h3><?= $message ?></h3>
    <p class="lead bg-light p-2 mt-3">in <?= $file ?> at line: <?= $line ?></p>
    <hr>
    <small>
        <pre><?= $trace ?></pre>
    </small>
    <hr>
    <p>Exception thrown: <span class="badge badge-secondary"><?= $class ?></span></p>
    <?php if ($previous) : ?>
    <!-- <hr> -->
    <p>Previously thrown:</p>
    <table class="table small">
        <thead>
            <tr>
                <th>#</th>
                <th>Exception</th>
                <th>in file (line)</th>
            </tr>
        </thead>
        <?php foreach ($previous as $i => $p) : ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= $p['class'] ?></td>
            <td><?= $p['file'] ?> (<?= $p['line'] ?>)</td>
        </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    <?php endif ?>
    <hr>
    <a class='btn btn-outline-secondary get-back' href='#'>
        <i class='fa fa-angle-double-left'></i> Вернуться
    </a>
</div>