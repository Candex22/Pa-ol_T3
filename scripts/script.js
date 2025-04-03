// Setup delete confirmation modal
document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', function () {
        const id = this.getAttribute('data-id');
        const name = this.getAttribute('data-name');

        document.getElementById('toolName').textContent = name;
        document.getElementById('confirmDelete').href = 'herramientas_lista.php?delete=' + id;

        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    });
});