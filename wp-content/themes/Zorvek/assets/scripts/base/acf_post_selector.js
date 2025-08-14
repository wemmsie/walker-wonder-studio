// jQuery(document).ready(function ($) {
//     // Function to find the taxonomy field for a given post type field
//     function getTaxonomyField(postTypeField) {
//         return postTypeField.closest('.acf-fields').find('[data-name="post_taxonomy"] select');
//     }

//     // Function to refresh the taxonomy field based on selected post type
//     function refreshTaxonomyField(postTypeField) {
//         var selectedPostType = postTypeField.val();
//         var taxonomies = taxonomyData[selectedPostType] || {};
//         var taxonomyField = getTaxonomyField(postTypeField);

//         // Check if taxonomy field has already been populated
//         if (taxonomyField.data('populated')) {
//             return; // Exit if already populated
//         }

//         // Clear existing options
//         taxonomyField.empty();

//         // Add a "Select Taxonomy" placeholder option
//         taxonomyField.append('<option value="">Select Taxonomy</option>');

//         // Populate the taxonomy field with the relevant options
//         $.each(taxonomies, function (taxonomy, terms) {
//             $.each(terms, function (index, term) {
//                 var option = $('<option></option>')
//                     .attr('value', term.term_id)
//                     .text(term.name);

//                 // Check if the term is in the selected values
//                 if (selectedTaxonomies[selectedPostType] && selectedTaxonomies[selectedPostType].indexOf(term.term_id.toString()) !== -1) {
//                     option.prop('selected', true); // Mark option as selected
//                 }

//                 taxonomyField.append(option);
//             });
//         });

//         // Mark the field as populated
//         taxonomyField.data('populated', true);

//         // Trigger change event to update any dependent fields or UI elements
//         taxonomyField.trigger('change');
//     }

//     // Function to handle updates
//     function handleUpdate(event) {
//         var postTypeField = $(event.target).closest('.acf-fields').find('[data-name="post_type"] select');
//         refreshTaxonomyField(postTypeField);
//     }

//     // Initial population for all existing fields
//     $('[data-name="post_type"] select').each(function () {
//         refreshTaxonomyField($(this));
//     });

//     // Listen for changes to any post type field
//     $(document).on('change', '[data-name="post_type"] select', handleUpdate);

//     // Handle ACF field append event for dynamically added fields
//     acf.add_action('append', function($el) {
//         var postTypeField = $el.find('[data-name="post_type"] select');
//         if (postTypeField.length) {
//             refreshTaxonomyField(postTypeField);
//         }
//     });
// });
