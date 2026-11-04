<dialog id="deleteExamDialog" class="exam-delete-dialog" aria-labelledby="deleteExamTitle" aria-describedby="deleteExamDescription">
    <h3 id="deleteExamTitle">Apakah anda yakin ingin menghapus ujian ini?</h3>
    <p>Ujian: <strong id="deleteExamName"></strong></p>
    <p id="deleteExamDescription">Semua soal, jawaban, dan riwayat pengerjaan terkait juga akan dihapus.</p>
    <p id="deleteExamCountdown" role="status" hidden></p>
    <div class="actions">
        <button type="button" class="btn ghost" id="cancelDeleteExam">Batal</button>
        <button type="button" class="btn primary" id="nextDeleteExam">Ya, lanjutkan</button>
        <button type="button" class="btn danger" id="confirmDeleteExam" disabled hidden>Hapus Ujian</button>
    </div>
</dialog>
@push('scripts')
<script src="{{ asset('js/exam-delete.js') }}"></script>
@endpush
