( function ( wp ) {
	/**
	 * @since 1.7.0
	 * Content protextion indicators in site editore mode
	 */
	'use strict';

	if (
		! wp ||
		! wp.data ||
		! wp.data.select ||
		! wp.data.subscribe
	) {
		return;
	}

	// Translator
	const { __ } = wp.i18n;

	const ROW_SELECTOR =
		'.dataviews-view-list__item-wrapper';

	const ITEM_BUTTON_SELECTOR =
		'button[id$="-item-wrapper"]';

	const PROTECTED_CLASS =
		'pcgd-protected-page';

	const PAGE_QUERY = {
		order: 'asc',
		orderby: 'title',
		orderby_hierarchy: true,
		page: 1,
		per_page: 100,
		search: '',
		status: 'draft,future,pending,private,publish',
		_embed: 'author,wp:featuredmedia',
	};

	let protectedPageIds = new Set();
	let observer = null;
	let scheduled = false;

	/**
	 * Extract the WordPress page ID from the DataViews item button.
	 *
	 * Example:
	 * view-list-0-36-item-wrapper
	 *
	 * @param {Element} button DataViews item button.
	 * @return {string|null} Page ID or null.
	 */
	const getPageIdFromButton = function ( button ) {
		if ( ! button || ! button.id ) {
			return null;
		}

		const match = button.id.match(
			/-(\d+)-item-wrapper$/
		);

		return match ? match[ 1 ] : null;
	};

	/**
	 * Collect protected page IDs.
	 */
	const updateProtectedPageIds = function () {
		const pages = wp.data
			.select( 'core' )
			.getEntityRecords(
				'postType',
				'page',
				PAGE_QUERY
			);

		if ( ! Array.isArray( pages ) ) {
			return false;
		}

		protectedPageIds = new Set(
			pages
				.filter( function ( page ) {
					return (
						page &&
						page.pcgd_protected === true
					);
				} )
				.map( function ( page ) {
					return String( page.id );
				} )
		);

		return true;
	};

	/**
	 * Mark protected pages currently rendered by DataViews.
	 */
	const markProtectedPages = function () {
		const rows = document.querySelectorAll(
			ROW_SELECTOR
		);

		rows.forEach( function ( row ) {
			const itemButton = row.querySelector(
				ITEM_BUTTON_SELECTOR
			);

			const pageId = getPageIdFromButton(
				itemButton
			);

			if ( ! pageId ) {
				return;
			}

			row.classList.toggle(
				PROTECTED_CLASS,
				protectedPageIds.has( pageId )
			);
		} );
	};

	/**
	 * Batch DOM updates produced by React.
	 */
	const scheduleMarking = function () {
		if ( scheduled ) {
			return;
		}

		scheduled = true;

		window.requestAnimationFrame(
			function () {
				scheduled = false;

				updateProtectedPageIds();
				markProtectedPages();
			}
		);
	};

	/**
	 * Observe React-rendered DataViews rows.
	 */
	const startObserver = function () {
		if ( observer ) {
			return;
		}

		observer = new MutationObserver(
			scheduleMarking
		);

		observer.observe(
			document.body,
			{
				childList: true,
				subtree: true,
			}
		);

		scheduleMarking();
	};

	/**
	 * Wait until the page collection is available.
	 */
	const unsubscribe = wp.data.subscribe(
		function () {
			if ( ! updateProtectedPageIds() ) {
				return;
			}

			startObserver();
			unsubscribe();
		}
	);

	
	/**
	 * Second part
	 * For content details pages opened in canvas
	 */

	let currentDetectedPageId = null;
	let currentPage = null;
	let headerObserverStarted = false;

	/**
	 * Render or remove the protection indicator.
	 */
	const renderProtectionIndicator = function () {
		const existingIndicator = document.getElementById(
			'pcgd-protection-indicator'
		);

		const urlParams = new URLSearchParams(
			window.location.search
		);

		const isCanvasEditor =
			urlParams.get( 'canvas' ) === 'edit';

		if (
			! isCanvasEditor ||
			! currentPage ||
			currentPage.pcgd_protected !== true
		) {
			if ( existingIndicator ) {
				existingIndicator.remove();
			}

			return false;
		}

		const editorHeaderCenter = document.querySelector(
			'.editor-header__center'
		);

		const documentBar = editorHeaderCenter?.querySelector(
			'.editor-document-bar'
		);

		if ( ! editorHeaderCenter || ! documentBar ) {
			return false;
		}

		if ( existingIndicator ) {
			return true;
		}

		const indicator = document.createElement( 'div' );

		indicator.id = 'pcgd-protection-indicator';
		indicator.className = 'pcgd-protection-indicator';
		indicator.textContent = __(
			'Protected by ClientGuard',
			'plugiva-clientguard'
		);

		documentBar.insertAdjacentElement(
			'afterend',
			indicator
		);

		return true;
	};

	/**
	 * Watch for the React editor header.
	 */
	const startHeaderObserver = function () {
		if ( headerObserverStarted ) {
			return;
		}

		headerObserverStarted = true;

		const observer = new MutationObserver(
			function () {
				if ( renderProtectionIndicator() ) {
					observer.disconnect();
					headerObserverStarted = false;
				}
			}
		);

		observer.observe( document.body, {
			childList: true,
			subtree: true,
		} );

		if ( renderProtectionIndicator() ) {
			observer.disconnect();
			headerObserverStarted = false;
		}
	};

	/**
	 * Detect page changes in the Site Editor SPA.
	 */
	wp.data.subscribe( function () {
		const urlParams = new URLSearchParams(
			window.location.search
		);

		const currentPostId = urlParams.get( 'postId' );

		if ( ! currentPostId ) {
			currentDetectedPageId = null;
			currentPage = null;

			renderProtectionIndicator();

			return;
		}

		const pageId = Number( currentPostId );

		if ( pageId === currentDetectedPageId ) {
			return;
		}

		const page = wp.data
			.select( 'core' )
			.getEntityRecord(
				'postType',
				'page',
				pageId
			);

		if ( ! page ) {
			return;
		}

		currentDetectedPageId = pageId;
		currentPage = page;

		renderProtectionIndicator();

		// run only for protected pages
		if ( page.pcgd_protected === true ) {
			startHeaderObserver();
		}

	} );

} )( window.wp );
