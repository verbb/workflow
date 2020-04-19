/** global: Craft */
/** global: Garnish */
/**
 * Editor User Group Select Input
 */
Craft.EditorUserGroupSelectInput = Garnish.Base.extend(
    {
        id: null,
        editorUserGroups: [],

        sort: null,
        $container: null,

        init: function(id, editorUserGroups) {
            this.id = id;
            this.editorUserGroups = editorUserGroups;
            this.$container = $('#' + this.id);

            this.initSort();
        },

        initSort: function() {
            this.sort = new Garnish.DragSort({
                container: this.$container,
                // filter: $.proxy(function() {
                //     // Only return all the selected items if the target item is selected
                //     if (this.elementSort.$targetItem.hasClass('sel')) {
                //         return this.elementSelect.getSelectedItems();
                //     } else {
                //         return this.elementSort.$targetItem;
                //     }
                // }, this),
                ignoreHandleSelector: '.delete',
                // axis: this.getElementSortAxis(),
                collapseDraggees: true,
                magnetStrength: 4,
                helperLagBase: 1.5,
                // onSortChange: (this.settings.selectable ? $.proxy(function() {
                //         this.elementSelect.resetItemOrder();
                //     }, this) : null)
            });
        },
    });
