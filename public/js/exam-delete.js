(() => {
    const dialog = document.getElementById('deleteExamDialog');
    if (!dialog) return;
    const title = document.getElementById('deleteExamTitle');
    const name = document.getElementById('deleteExamName');
    const countdown = document.getElementById('deleteExamCountdown');
    const next = document.getElementById('nextDeleteExam');
    const confirm = document.getElementById('confirmDeleteExam');
    const cancel = document.getElementById('cancelDeleteExam');
    let activeForm = null;
    let timer = null;
    let readyAt = null;

    const reset = () => {
        clearInterval(timer);
        timer = null;
        readyAt = null;
        activeForm = null;
        confirm.disabled = true;
    };

    document.querySelectorAll('form[data-delete-exam]').forEach(form => {
        form.addEventListener('submit', event => {
            event.preventDefault();
            if (dialog.open) return;
            reset();
            activeForm = form;
            name.textContent = form.dataset.examTitle;
            title.textContent = 'Apakah anda yakin ingin menghapus ujian ini?';
            countdown.hidden = true;
            next.hidden = false;
            confirm.hidden = true;
            dialog.showModal();
            cancel.focus();
        });
    });

    next.addEventListener('click', () => {
        if (!activeForm || readyAt !== null) return;
        title.textContent = 'Apakah ujian yang ingin dihapus sudah benar?';
        next.hidden = true;
        confirm.hidden = false;
        countdown.hidden = false;
        readyAt = performance.now() + 10000;
        const tick = () => {
            const remaining = Math.max(0, Math.ceil((readyAt - performance.now()) / 1000));
            countdown.textContent = remaining > 0
                ? `Tombol hapus akan aktif dalam ${remaining} detik.`
                : 'Silakan periksa kembali nama ujian sebelum menghapus.';
            confirm.disabled = remaining > 0;
            if (remaining === 0) clearInterval(timer);
        };
        tick();
        timer = setInterval(tick, 250);
        cancel.focus();
    });

    cancel.addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', reset);
    confirm.addEventListener('click', () => {
        if (!activeForm || readyAt === null || performance.now() < readyAt || confirm.disabled) return;
        confirm.disabled = true;
        cancel.disabled = true;
        confirm.textContent = 'Menghapus...';
        // Native submission retains the existing CSRF token and DELETE method.
        HTMLFormElement.prototype.submit.call(activeForm);
    });
})();
