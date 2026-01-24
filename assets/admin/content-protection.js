(function () {
	const searchBtn = document.getElementById('pcgd-page-search-btn');
	if (!searchBtn) return;

	const input = document.getElementById('pcgd-page-search');
	const results = document.getElementById('pcgd-search-results');
	const list = document.getElementById('pcgd-protected-list');

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchBtn.click();
        }
    });

	searchBtn.addEventListener('click', function () {
		const term = input.value.trim();
		if (term.length < 2) {
			results.innerHTML = '';
			return;
		}

		results.innerHTML = 'Searching…';

		const data = new FormData();
		data.append('action', 'pcgd_search_pages');
		data.append('term', term);
		data.append('nonce', pcgdAdmin.nonce);

		fetch(pcgdAdmin.ajaxUrl, {
			method: 'POST',
			body: data
		})
		.then(r => r.json())
		.then(response => {
			results.innerHTML = '';

			if (!response.success || !response.data.length) {
				results.innerHTML = '<p class="description">No pages found.</p>';
				return;
			}

			response.data.forEach(page => {

                if (list.querySelector(`li[data-id="${page.id}"]`)) {
                    return;
                }

                const row = document.createElement('div');
                row.className = 'pcgd-search-row';
                row.innerHTML = `
                    ${page.title}
                    <a href="${page.link}" target="_blank" class="pcgd-view">View</a>
                    <button type="button"
                            class="button pcgd-add"
                            data-id="${page.id}"
                            data-title="${page.title}">
                        Add
                    </button>
                `;

                results.appendChild(row);
            });

		});
	});

	results.addEventListener('click', function (e) {
		if (!e.target.classList.contains('pcgd-add')) return;

		const id = e.target.dataset.id;
		const title = e.target.dataset.title;

		if (list.querySelector(`li[data-id="${id}"]`)) {
			return;
		}

		const li = document.createElement('li');
		li.dataset.id = id;
		li.innerHTML = `
			${title}
			<button type="button" class="button-link pcgd-remove">Remove</button>
			<input type="hidden" name="pcgd_settings[protected_content][]" value="${id}" />
		`;

		list.appendChild(li);

        // After adding to protected list
        e.target.closest('.pcgd-search-row').remove();

	});

	list.addEventListener('click', function (e) {
		if (!e.target.classList.contains('pcgd-remove')) return;
		e.target.closest('li').remove();
	});
})();
