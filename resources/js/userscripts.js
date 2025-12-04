document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('select-all');
    if (!selectAll) return;

    selectAll.addEventListener('change', function(e) {
        const checked = e.target.checked;
        document.querySelectorAll('.select-item').forEach(cb => cb.checked = checked);
    });
});