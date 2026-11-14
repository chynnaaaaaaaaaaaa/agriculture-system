</main>

<?php if (Auth::isAdmin()): ?>
<!-- One shared delete confirmation dialog used by every module -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" id="deleteForm" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="deleteId">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirm deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to permanently delete
                <strong id="deleteName"></strong>? This cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Yes, delete</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('deleteModal')?.addEventListener('show.bs.modal', function (ev) {
    const b = ev.relatedTarget;
    document.getElementById('deleteForm').action = b.dataset.action;
    document.getElementById('deleteId').value    = b.dataset.id;
    document.getElementById('deleteName').textContent = b.dataset.name;
});
</script>
<?= $extraScripts ?? '' ?>
</body>
</html>
