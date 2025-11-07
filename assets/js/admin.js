/**
 * Admin JavaScript for Log Changes plugin.
 *
 * @package LogChanges
 */

(function($) {
	'use strict';
	
	$(document).ready(function() {
		/**
		 * Toggle log details visibility.
		 */
		$('.toggle-details').on('click', function(e) {
			e.preventDefault();
			
			var logId = $(this).data('log-id');
			var detailsDiv = $('#details-' + logId);
			
			if (detailsDiv.is(':visible')) {
				detailsDiv.slideUp(200);
				$(this).text(logChangesL10n.showDetails || 'Show Details');
			} else {
				detailsDiv.slideDown(200);
				$(this).text(logChangesL10n.hideDetails || 'Hide Details');
			}
		});
		
		/**
		 * Auto-submit filter form on select change.
		 */
		$('#filter-action, #filter-object, #filter-user').on('change', function() {
			$(this).closest('form').submit();
		});
		
		/**
		 * Clear search on ESC key.
		 */
		$('#log-search').on('keydown', function(e) {
			if (e.key === 'Escape' || e.keyCode === 27) {
				$(this).val('');
			}
		});
		
		/**
		 * Add loading state to filter form.
		 */
		$('.log-changes-filters form').on('submit', function() {
			var submitButton = $(this).find('input[type="submit"]');
			submitButton.prop('disabled', true).val(logChangesL10n.loading || 'Loading...');
		});
		
		/**
		 * Highlight search terms in results.
		 */
		var searchTerm = $('#log-search').val();
		if (searchTerm && searchTerm.length > 2) {
			highlightSearchTerms(searchTerm);
		}
		
		/**
		 * Highlight search terms in the description.
		 *
		 * @param {string} term Search term to highlight.
		 */
		function highlightSearchTerms(term) {
			var regex = new RegExp('(' + term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
			
			$('.description-text').each(function() {
				var element = $(this);
				var text = element.text();
				
				// Split text by the search term while preserving case
				var parts = text.split(regex);
				
				if (parts.length > 1) {
					// Clear the element
					element.empty();
					
					// Rebuild with mark tags around matches
					for (var i = 0; i < parts.length; i++) {
						if (i % 2 === 0) {
							// Non-matching text
							element.append(document.createTextNode(parts[i]));
						} else {
							// Matching text - wrap in mark tag
							var mark = $('<mark>').text(parts[i]);
							element.append(mark);
						}
					}
				}
			});
		}
		
		/**
		 * Add keyboard shortcuts.
		 */
		$(document).on('keydown', function(e) {
			// Ctrl/Cmd + K to focus search
			if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
				e.preventDefault();
				$('#log-search').focus();
			}
		});
		
		/**
		 * Add tooltips to action and object badges.
		 */
		$('.action-badge, .object-badge').each(function() {
			$(this).attr('title', 'Click to filter by this ' + ($(this).hasClass('action-badge') ? 'action' : 'object type'));
		});
		
		/**
		 * Click on badge to filter.
		 */
		$('.action-badge').on('click', function() {
			var actionType = $(this).text().trim().toLowerCase();
			window.location.href = addOrUpdateUrlParam('filter_action', actionType);
		});
		
		$('.object-badge').on('click', function() {
			var objectType = $(this).text().trim().toLowerCase();
			window.location.href = addOrUpdateUrlParam('filter_object', objectType);
		});
		
		/**
		 * Add or update URL parameter.
		 *
		 * @param {string} param Parameter name.
		 * @param {string} value Parameter value.
		 * @return {string} Updated URL.
		 */
		function addOrUpdateUrlParam(param, value) {
			var url = new URL(window.location.href);
			url.searchParams.set(param, value);
			url.searchParams.delete('paged'); // Reset pagination
			return url.toString();
		}
		
		/**
		 * Confirm before clearing all logs (if we add this feature).
		 */
		$('.clear-all-logs').on('click', function(e) {
			if (!confirm(logChangesL10n.confirmClearAll || 'Are you sure you want to clear all logs? This action cannot be undone.')) {
				e.preventDefault();
			}
		});
		
		/**
		 * Add export functionality hint.
		 */
		if ($('.log-changes-table tbody tr').length > 0) {
			$('.wrap h1').after('<p class="description">Track all changes to your WordPress site. Use the filters to find specific changes.</p>');
		}
	});
	
})(jQuery);
