document.addEventListener('DOMContentLoaded', function () {
    const tagsContainer = document.querySelector('.tags-container');
    const addTagButton = document.querySelector('.add-tag-button');
    let tagCount = document.querySelectorAll('.tag-input').length;

    addTagButton.addEventListener('click', function () {
        const tagInput = document.createElement('div');
        tagInput.className = 'tag-input mb-2 d-flex';
        tagInput.innerHTML = `
            <input type="text" name="article[tags][${tagCount}]" 
                   class="form-control me-2" placeholder="Entrez un tag">
            <button type="button" class="btn btn-outline-danger remove-tag">
                <i class="fas fa-times"></i>
            </button>
        `;
        tagsContainer.appendChild(tagInput);
        tagCount++;
    });

    tagsContainer.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-tag')) {
            e.target.closest('.tag-input').remove();
        }
    });
}); 