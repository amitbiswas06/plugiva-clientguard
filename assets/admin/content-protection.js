(function () {

	/**
	 * Content Protection UI
	 * Provides an interface for searching and selecting pages to protect, with AJAX-powered search and dynamic list management.
	 * @since 1.8.0
	 */

	"use strict";

	const container = document.getElementById('pcgd-content-protection');

	if (!container) {
		return;
	}

	const input 		= container.querySelector('#pcgd-content-search');
	const searchButton 	= container.querySelector('.pcgd-content-search-button');
	const results 		= container.querySelector('.pcgd-content-search-results');
	const typeButtons 	= container.querySelectorAll('.pcgd-content-type-pill');

	const message 		= results.querySelector('.pcgd-search-results-message');
	const toolbar 		= results.querySelector('.pcgd-search-results-toolbar');
	const resultList 	= results.querySelector('.pcgd-search-results-list');
	const note 			= results.querySelector('.pcgd-search-results-note');
	const template 		= results.querySelector('.pcgd-search-result-template');

	const selectAllCheckbox = results.querySelector('.pcgd-select-all-checkbox');
	const protectButton 	= results.querySelector('.pcgd-protect-selected');
	const protectedList 	= container.querySelector('.pcgd-protected-list');

	if (!input || !searchButton || !results) {
		return;
	}

	/**
	 * Get the currently selected content type.
	 *
	 * @return {string} Selected content type.
	 */
	function getSelectedContentType() {
		const selectedButton = container.querySelector(
			'.pcgd-content-type-pill.selected-pill'
		);

		return selectedButton
			? selectedButton.dataset.contentType
			: 'page';
	}

	/**
	 * Display a plain-text text in the results container.
	 *
	 * @param {string} text Text to display.
	 */
	function showMessage(text) {
		results.hidden 		= false;
		message.textContent = text;
		message.hidden 		= false;

		toolbar.hidden = true;
		resultList.replaceChildren();
		note.hidden = true;
	}

	/**
	 * Search supported content through WordPress AJAX.
	 */
	function searchContent() {
		const term = input.value.trim();

		if (term.length < 3) {
			showMessage('Enter at least 3 characters to search.');
			return;
		}

		showMessage('Searching…');

		const data = new FormData();

		data.append('action', 'pcgd_search_pages');
		data.append('term', term);
		data.append('post_type', getSelectedContentType());
		data.append('nonce', pcgdAdmin.nonce);

		fetch(pcgdAdmin.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: data
		})
			.then(function (response) {
				if (!response.ok) {
					throw new Error('HTTP request failed.');
				}

				return response.json();
			})
			.then(function (response) {
				if (!response.success || !Array.isArray(response.data)) {
					showMessage('Unable to search this content type.');
					return;
				}

				renderResults(response.data);
			})
			.catch(function () {
				showMessage('Search failed. Please try again.');
			});
	}

	/**
	 * Render search results using DOM APIs and plain text.
	 *
	 * @param {Array} items Search results.
	 */
	function renderResults(items) {
		results.hidden 		= false;
		message.textContent = '';
		message.hidden 		= true;
		toolbar.hidden 		= true;
		resultList.replaceChildren();
		note.hidden = true;

		if (items.length === 0) {
			showMessage('No matching content found.');
			return;
		}

		items.forEach(function (item) {
			const id = Number(item.id);

			if (!Number.isInteger(id) || id <= 0) {
				return;
			}

			if (
				protectedList &&
				protectedList.querySelector(
					`.pcgd-protected-item[data-id="${id}"]`
				)
			) {
				return;
			}

			const fragment 		= template.content.cloneNode(true);
			const resultItem 	= fragment.querySelector('.pcgd-search-result-item');
			const checkbox 		= fragment.querySelector('.pcgd-search-result-checkbox');
			const title 		= fragment.querySelector('.pcgd-search-result-title');
			const status 		= fragment.querySelector('.pcgd-publication-status');

			resultItem.dataset.id 	= String(id);
			checkbox.value 			= String(id);
			title.textContent 		= String(item.title || '');

			// Post status
			const statusLabels = {
				publish: 'Published',
				draft: 'Draft',
				pending: 'Pending review',
				future: 'Scheduled',
				private: 'Private'
			};

			const statusLabel = statusLabels[item.status] || item.status;

			status.classList.remove(
				'pcgd-status-published',
				'pcgd-status-draft'
			);

			if (item.status === 'publish') {
				status.classList.add('pcgd-status-published');
			} else {
				status.classList.add('pcgd-status-draft');
			}

			status.textContent = statusLabel;
			// Post status end

			resultList.appendChild(fragment);
		});

		if (resultList.children.length === 0) {
			showMessage('All matching content is already protected.');
			return;
		}

		toolbar.hidden 	= false;
		note.hidden 	= false;

		const resultCheckboxes = resultList.querySelectorAll(
			'.pcgd-search-result-checkbox'
		);

		function updateSelectionControls() {
			const selectedCount = Array.from(resultCheckboxes).filter(
				function (checkbox) {
					return checkbox.checked;
				}
			).length;

			protectButton.disabled = selectedCount === 0;

			selectAllCheckbox.checked =
				resultCheckboxes.length > 0 &&
				selectedCount === resultCheckboxes.length;

			selectAllCheckbox.indeterminate =
				selectedCount > 0 &&
				selectedCount < resultCheckboxes.length;
		}

		selectAllCheckbox.checked 		= false;
		selectAllCheckbox.indeterminate = false;
		protectButton.disabled 			= true;

		selectAllCheckbox.onchange = function () {
			resultCheckboxes.forEach(function (checkbox) {
				checkbox.checked = selectAllCheckbox.checked;
			});

			updateSelectionControls();
		};

		resultCheckboxes.forEach(function (checkbox) {
			checkbox.addEventListener('change', updateSelectionControls);
		});
	}

	/**
	 * Find an existing protected-content category or create one.
	 *
	 * @param {string} postType  Content type identifier.
	 * @param {string} typeLabel Human-readable content type label.
	 * @return {HTMLElement} The category's protected-items list.
	 */
	function getOrCreateContentCategory(postType, typeLabel) {

		// Find the category for this content type.
		let category = Array.from(
			protectedList.querySelectorAll('.pcgd-content-category')
		).find(function (item) {
			return item.dataset.postType === postType;
		});

		// Create the category if it doesn't exist.
		if (!category) {

			// Remove the empty-state message when adding the first item.
			const emptyState = protectedList.querySelector('.pcgd-protected-empty');

			if (emptyState) {
				emptyState.remove();
			}

			category 					= document.createElement('li');
			category.className 			= 'pcgd-content-category';
			category.dataset.postType 	= postType;

			const heading 		= document.createElement('strong');
			heading.className 	= 'pcgd-content-category-title';
			heading.textContent = typeLabel;

			const itemsList 	= document.createElement('ul');
			itemsList.className = 'pcgd-protected-items';

			category.appendChild(heading);
			category.appendChild(itemsList);

			protectedList.appendChild(category);
		}

		return category.querySelector('.pcgd-protected-items');
	}

	/**
	 * Create and append a protected-content list item.
	 *
	 * @param {Object} item Protected-content details returned by AJAX.
	 */
	function createProtectedItem(item) {

		const id = Number(item.id);

		if (!Number.isInteger(id) || id <= 0) {
			return;
		}

		if (
			typeof item.post_type !== 'string'
			|| !/^[a-z0-9_-]+$/.test(item.post_type)
		) {
			return;
		}

		// Prevent duplicate entries.
		const existingItems = protectedList.querySelectorAll('.pcgd-protected-item');

		for (const existingItem of existingItems) {
			if (existingItem.dataset.id === String(id)) {
				return;
			}
		}

		// Find or create the category for this content type.
		const itemsList = getOrCreateContentCategory(
			item.post_type,
			String(item.type_label || item.post_type)
		);

		const listItem 		= document.createElement('li');
		listItem.className 	= 'pcgd-protected-item';
		listItem.dataset.id = String(id);

		const title 		= document.createElement('span');
		title.className 	= 'pcgd-protected-item-title';
		title.textContent 	= String(item.title || '');

		const viewLink 		= document.createElement('a');
		viewLink.className 	= 'pcgd-view';
		viewLink.textContent = 'View';
		viewLink.target 	= '_blank';
		viewLink.rel 		= 'noopener noreferrer';

		// Accept only valid HTTP or HTTPS permalinks.
		try {
			const permalink = new URL(
				String(item.permalink || ''),
				window.location.origin
			);

			if (!['http:', 'https:'].includes(permalink.protocol)) {
				return;
			}

			viewLink.href = permalink.href;
		} catch (error) {
			return;
		}

		const removeButton 			= document.createElement('button');
		removeButton.type 			= 'button';
		removeButton.className 		= 'button-link pcgd-remove';
		removeButton.dataset.id 	= String(id);
		removeButton.textContent 	= 'Remove';

		const hiddenInput 	= document.createElement('input');
		hiddenInput.type 	= 'hidden';
		hiddenInput.name 	= 'pcgd_settings[protected_content][]';
		hiddenInput.value 	= String(id);

		listItem.appendChild(title);
		listItem.appendChild(viewLink);
		listItem.appendChild(removeButton);
		listItem.appendChild(hiddenInput);

		itemsList.appendChild(listItem);
	}

	/**
	 * Save selected content to the protected-content list.
	 *
	 * @since 1.8.0
	 */
	async function addProtectedContent() {

		const selectedIds = Array.from(
			resultList.querySelectorAll('.pcgd-search-result-checkbox:checked')
		).map(function (checkbox) {
			return checkbox.value;
		});

		if (selectedIds.length === 0) {
			return;
		}

		protectButton.disabled 		= true;
		protectButton.textContent 	= 'Protecting…';

		const formData = new FormData();

		formData.append('action', 'pcgd_add_protected_content');
		formData.append('nonce', pcgdAdmin.nonce);

		selectedIds.forEach(function (id) {
			formData.append('post_ids[]', id);
		});

		try {

			const response = await fetch(pcgdAdmin.ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin'
			});

			const data = await response.json();

			if (!response.ok || !data.success) {
				throw new Error(
					data.data && data.data.message
						? data.data.message
						: 'Unable to protect the selected content.'
				);
			}

			const addedIds = Array.isArray(data.data.added_ids)
				? data.data.added_ids.map(Number)
				: [];

			const addedItems = Array.isArray(data.data.added_items)
				? data.data.added_items
				: [];

			/*
			* Add successfully protected items to the existing list.
			*/
			addedItems.forEach(function (item) {
				if (addedIds.includes(Number(item.id))) {
					createProtectedItem(item);
				}
			});

			/*
			* Remove successfully protected items from the search results.
			* The server has already saved these IDs.
			*/
			addedIds.forEach(function (id) {
				const resultItem = Array.from(
					resultList.querySelectorAll('.pcgd-search-result-item')
				).find(function (item) {
					return item.dataset.id === String(id);
				});

				if (resultItem) {
					resultItem.remove();
				}
			});

			/*
			* Reset the remaining selections.
			* Rejected items remain available for another attempt.
			*/
			const remainingCheckboxes = resultList.querySelectorAll(
				'.pcgd-search-result-checkbox'
			);

			remainingCheckboxes.forEach(function (checkbox) {
				checkbox.checked = false;
			});

			selectAllCheckbox.checked = false;
			selectAllCheckbox.indeterminate = false;
			protectButton.disabled = true;

			/*
			* Update the results message and controls.
			*/
			if (resultList.children.length === 0) {

				toolbar.hidden = true;
				note.hidden = true;

				message.textContent = 'All matching content is now protected.';
				message.hidden = false;

			} else {

				toolbar.hidden = false;
				note.hidden = false;

				message.textContent = data.data.message;
				message.hidden = false;
			}

		} catch (error) {

			showMessage(
				error.message || 'An unexpected error occurred.'
			);

		} finally {

			protectButton.textContent 	= 'Protect selected';
			protectButton.disabled 		= true;

			selectAllCheckbox.checked 		= false;
			selectAllCheckbox.indeterminate = false;
		}
	}

	/**
	 * Remove protection from a content item.
	 *
	 * @param {HTMLElement} removeButton The clicked Remove button.
	 * @since 1.8.0
	 */
	async function removeProtectedContent(removeButton) {

		const postId 		 = removeButton.dataset.id;
		const protectedItem  = removeButton.closest('.pcgd-protected-item');
		const messageElement = container.querySelector('.pcgd-protected-message');

		if (!postId || !protectedItem || !messageElement) {
			return;
		}

		// Prevent duplicate requests while removal is in progress.
		if (removeButton.disabled) {
			return;
		}

		removeButton.disabled 	 = true;
		removeButton.textContent = 'Removing…';

		const formData = new FormData();

		formData.append('action', 'pcgd_remove_protected_content');
		formData.append('nonce', pcgdAdmin.nonce);
		formData.append('post_id', postId);

		try {

			const response = await fetch(pcgdAdmin.ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin'
			});

			const data = await response.json();

			if (!response.ok || !data.success) {
				throw new Error(
					data.data && data.data.message
						? data.data.message
						: 'Unable to remove content protection.'
				);
			}

			// Remove the item only after the server confirms success.
			const category = protectedItem.closest('.pcgd-content-category');

			protectedItem.remove();

			// Remove an empty category.
			if (
				category
				&& !category.querySelector('.pcgd-protected-item')
			) {
				category.remove();
			}

			// Display the success message.
			messageElement.textContent 	= data.data.message;
			messageElement.hidden 		= false;

			// Display the empty state when no protected items remain.
			if (!protectedList.querySelector('.pcgd-protected-item')) {

				const emptyMessage = document.createElement('li');

				emptyMessage.className 	 = 'pcgd-protected-empty description';
				emptyMessage.textContent = 'No content is currently protected.';

				protectedList.appendChild(emptyMessage);
			}

		} catch (error) {

			// Keep the item visible when removal fails.
			messageElement.textContent =
				error.message || 'An unexpected error occurred.';

			messageElement.hidden = false;

		} finally {

			// The button may have been removed after a successful request.
			// Restoring it is necessary only if it still exists.
			if (removeButton.isConnected) {
				removeButton.disabled 	 = false;
				removeButton.textContent = 'Remove';
			}
		}
	}

	/**
	 * Remove protection from all content items.
	 *
	 * @since 1.8.0
	 */
	async function removeAllProtectedContent() {

		if (!protectedList || !removeAllButton) {
			return;
		}

		if (!protectedList.querySelector('.pcgd-protected-item')) {
			return;
		}

		/*
		* Confirm this destructive operation.
		*/
		if (!window.confirm(
			'Are you sure you want to remove protection from all content?'
		)) {
			return;
		}

		const messageElement = container.querySelector('.pcgd-protected-message');

		if (!messageElement) {
			return;
		}

		removeAllButton.disabled = true;
		removeAllButton.textContent = 'Removing…';

		const formData = new FormData();

		formData.append('action', 'pcgd_remove_all_protected_content');
		formData.append('nonce', pcgdAdmin.nonce);

		try {

			const response = await fetch(pcgdAdmin.ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin'
			});

			const data = await response.json();

			if (!response.ok || !data.success) {
				throw new Error(
					data.data && data.data.message
						? data.data.message
						: 'Unable to remove protection from all content.'
				);
			}

			/*
			* Update the interface only after server confirmation.
			*/
			protectedList.replaceChildren();

			const emptyMessage = document.createElement('li');

			emptyMessage.className = 'pcgd-protected-empty description';
			emptyMessage.textContent = 'No content is currently protected.';

			protectedList.appendChild(emptyMessage);

			messageElement.textContent = data.data.message;
			messageElement.hidden = false;

		} catch (error) {

			/*
			* Keep the protected list intact if the request fails.
			*/
			messageElement.textContent =
				error.message || 'An unexpected error occurred.';

			messageElement.hidden = false;

		} finally {

			removeAllButton.disabled = false;
			removeAllButton.textContent = 'Remove all';
		}
	}

	// event listeners

	searchButton.addEventListener('click', searchContent);

	protectButton.addEventListener('click', addProtectedContent);

	protectedList.addEventListener('click', function (event) {

		const removeButton = event.target.closest('.pcgd-remove');
		if (!removeButton || !protectedList.contains(removeButton)) {
			return;
		}
		removeProtectedContent(removeButton);
	});

	const removeAllButton = container.querySelector('.pcgd-remove-all');

	if (removeAllButton) {
		removeAllButton.addEventListener('click', removeAllProtectedContent);
	}

	input.addEventListener('keydown', function (event) {
		if (event.key === 'Enter') {
			event.preventDefault();
			searchContent();
		}
	});

	/*
	 * Update the selected tab's visual and accessibility state.
	 * Search automatically when the user changes content type
	 * and has already entered a valid search term.
	 */
	typeButtons.forEach(function (button) {
		button.addEventListener('click', function () {
			typeButtons.forEach(function (typeButton) {
				const isSelected = typeButton === button;

				typeButton.classList.toggle('selected-pill', isSelected);
				typeButton.setAttribute('aria-pressed', String(isSelected));
			});

			if (input.value.trim().length >= 3) {
				searchContent();
			} else {
				results.hidden		= true;
				message.textContent = '';
				message.hidden		= true;
				toolbar.hidden		= true;

				resultList.replaceChildren();

				note.hidden 					= true;
				selectAllCheckbox.checked 		= false;
				selectAllCheckbox.indeterminate = false;
				protectButton.disabled 			= true;
			}
		});
	});

})();


(function () {

	/**
	 * Client Mode UI Control
	 * Disables certain settings and menu options when Client Mode is active, to prevent conflicts and confusion.
	 * @since 1.1.0
	 */

	"use strict";

	const clientMode = document.querySelector('input[name="pcgd_settings[client_mode]"]');
	if (!clientMode) return;

	const controlledSettings = [
		'lock_theme_switch',
		'lock_appearance_management', // @since 1.6.0
		'lock_plugin_install',
		'protect_site_urls', // @since 1.2.0
	];

	const controlledMenus = [
		'plugins.php',
		'themes.php',
		'tools.php',
		'acf',
	];

	function toggleClientModeUI() {

		const isEnabled = clientMode.checked;

		// Settings fields
		controlledSettings.forEach(function (key) {
			const field = document.querySelector(`input[name="pcgd_settings[${key}]"]`);
			if (!field) return;

			field.disabled = isEnabled;
		});

		// Menu checkboxes
		const menuFields = document.querySelectorAll('input[name="pcgd_settings[hide_menus][]"]');

		menuFields.forEach(function (el) {

			if (controlledMenus.includes(el.value)) {

				// Appearance Governance may already manage this menu.
				// @since 1.6.0
				if ('themes.php' === el.value && !isEnabled) {
					return;
				}

				el.disabled = isEnabled;
			}
		});
	}

	clientMode.addEventListener('change', toggleClientModeUI);

	// Run on load
	toggleClientModeUI();

})();
