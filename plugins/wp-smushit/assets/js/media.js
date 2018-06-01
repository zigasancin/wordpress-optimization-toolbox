/**
 * Adds a Smush Now button and displays stats in Media Attachment Details Screen
 *
 *
 */
(function ($, _) {

    // Local reference to the WordPress media namespace.
    var smush_media = wp.media;

    if ('undefined' != typeof smush_media.view &&
        'undefined' != typeof smush_media.view.Attachment.Details.TwoColumn) {
        // Local instance of the Attachment Details TwoColumn used in the edit attachment modal view
        var smushMediaTwoColumn = smush_media.view.Attachment.Details.TwoColumn;

        /**
         /*
         * Add Smush details to attachment.
         */
        smush_media.view.Attachment.Details.TwoColumn = smushMediaTwoColumn.extend({

            initialize: function () {
                // Always make sure that our content is up to date.
                this.model.on('change', this.render, this);
            },

            render: function () {
                // Ensure that the main attachment fields are rendered.
                smush_media.view.Attachment.prototype.render.apply(this, arguments);

                if (typeof (this.model.get('smush')) == 'undefined') {
                    return this;
                }

                // Detach the views, append our custom fields, make sure that our data is fully updated and re-render the updated view.
                this.views.detach();

                var $detailsHtml = this.$el.find('.settings');

                //Create the template
                var template = _.template('<label class="setting smush-stats" data-setting="description"><span class="name"><%= label %></span><span class="value"><%= value %></span></label>');
                var html = template({
                    label: smush_vars.strings['stats_label'],
                    value: this.model.get('smush')
                });

                $detailsHtml.append(html);
                this.model.fetch();
                this.views.render();

                return this;
            }
        });
    }

    // Local instance of the Attachment Details TwoColumn used in the edit attachment modal view
    var smushAttachmentDetails = smush_media.view.Attachment.Details;

    /**
     * Add Smush details to attachment.
     */
    smush_media.view.Attachment.Details = smushAttachmentDetails.extend({

        initialize: function () {
            // Always make sure that our content is up to date.
            this.model.on('change', this.render, this);
        },
        render: function () {
            // Ensure that the main attachment fields are rendered.
            smush_media.view.Attachment.prototype.render.apply(this, arguments);

            if (typeof (this.model.get('smush')) == 'undefined') {
                return this;
            }

            // Detach the views, append our custom fields, make sure that our data is fully updated and re-render the updated view.
            this.views.detach();

            var template = _.template('<label class="setting smush-stats" data-setting="description"><span class="name"><%= label %></span><span class="value"><%= value %></span></label>');
            var html = template({
                label: smush_vars.strings['stats_label'],
                value: this.model.get('smush')
            });

            this.$el.append(html);

            this.model.fetch();
            this.views.render();

            return this;
        }
    });

})(jQuery, _);
