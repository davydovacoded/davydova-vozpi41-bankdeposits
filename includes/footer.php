<?php

declare(strict_types=1);
?>
        </main>
        <?php if (($currentSection ?? '') !== 'home'): ?>
            <footer class="site-footer"><span>Учебная система / Лабораторная работа №3</span></footer>
        <?php endif; ?>
    </div>
</div>
<div class="project-modal" id="project-modal" hidden>
    <div class="project-modal-backdrop" data-modal-close></div>
    <section class="project-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="project-modal-title" tabindex="-1">
        <button class="project-modal-close" type="button" aria-label="Закрыть окно" data-modal-close>×</button>
        <h2 id="project-modal-title">Информация о проекте</h2>
        <div class="project-modal-grid">
            <article><h3>Разработчик</h3><p>Давыдова Анастасия</p><small>ВОЗПИ41</small></article>
            <article><h3>Преподаватель</h3><p>Ступина Мария Валерьевна</p><small>Разработка Web-приложений</small></article>
            <article><h3>Технологии</h3><p>PHP (PDO), MySQL, HTML, CSS, JavaScript, InfinityFree</p></article>
            <article><h3>О проекте</h3><p>Лабораторная работа №3 «Банковские вклады». Веб-приложение для управления клиентами, видами вкладов, банковскими счетами и формирования годовой ведомости доходов.</p></article>
        </div>
    </section>
</div>
</body>
</html>
