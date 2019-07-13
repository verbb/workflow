// ==========================================================================

// Workflow Plugin for Craft CMS
// Author: Verbb - https://verbb.io/

// ==========================================================================

if (typeof Craft.Workflow === typeof undefined) {
    Craft.Workflow = {};
}

(function($) {

$('[data-workflow-btn]').on('click', function(e) {
    e.preventDefault();

    // Prevent browser prompt when trying to submit the form - thinking its changed
    $(window).off('beforeunload');

    var $form = $(this).parents('form');
    var $btn = $(e.currentTarget);

    if ($btn.attr('data-action')) {
        $('<input type="hidden" name="action"/>').val($btn.attr('data-action')).appendTo($form);
    }

    if ($btn.attr('data-redirect')) {
        $('<input type="hidden" name="redirect"/>').val($btn.attr('data-redirect')).appendTo($form);
    }

    if ($btn.attr('data-param')) {
        $('<input type="hidden"/>').attr({ name: $btn.attr('data-param'), value: $btn.attr('data-value') }).appendTo($form);
    }

    $form.trigger({
        type: 'submit',
        customTrigger: true,
    });
});

})(jQuery);
