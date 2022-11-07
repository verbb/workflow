// ==========================================================================

// Workflow Plugin for Craft CMS
// Author: Verbb - https://verbb.io/

// ==========================================================================

if (typeof Craft.Workflow === typeof undefined) {
    Craft.Workflow = {};
}

(function($) {

$('[data-workflow-view-changes]').on('click', function(e) {
    e.preventDefault();

    new Craft.Workflow.ViewChangesModal($(this).data('review-id'));
});

Craft.Workflow.ViewChangesModal = Garnish.Modal.extend({
    init: function(reviewId) {
        this.reviewId = reviewId;

        var $container = $('<div class="modal workflow-review-compare-modal"></div>').appendTo(Garnish.$bod),
            $body = $('<div class="body"><div class="spinner big"></div></div>').appendTo($container),
            $footer = $('<div class="footer"/>').appendTo($container);

        this.base($container, this.settings);
        
        this.$buttons = $('<div class="buttons right"/>').appendTo($footer);
        this.$cancelBtn = $('<div class="btn">' + Craft.t('workflow', 'Close') + '</div>').appendTo(this.$buttons);
        this.$body = $body;

        this.addListener(this.$cancelBtn, 'activate', 'onFadeOut');
    },

    onFadeIn: function() {
        var data = {
            reviewId: this.reviewId,
        };

        Craft.sendActionRequest('POST', 'workflow/reviews/get-compare-modal-body', { data })
            .then((response) => {
                this.$body.html(response.data.html);

                Craft.appendHeadHtml(response.data.headHtml);
                Craft.appendBodyHtml(response.data.footHtml);

                Craft.initUiElements(this.$body);
            });
        this.base();
    },

    onFadeOut: function() {
        this.hide();
        this.destroy();
        this.$shade.remove();
        this.$container.remove();

        this.removeListener(this.$cancelBtn, 'click');
    },

    show: function() {
        this.base();
    }
});

})(jQuery);
