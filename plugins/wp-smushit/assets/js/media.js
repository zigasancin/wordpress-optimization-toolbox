/**
 * Adds a Smush Now button and displays stats in Media Attachment Details Screen
 *
 *
 */
(function( $, _ ) {

	// Local reference to the WordPress media namespace.
	var smush_media = wp.media;

	if( 'undefined' != typeof smush_media.view &&
        'undefined' != typeof smush_media.view.Attachment.Details.TwoColumn ) {
        // Local instance of the Attachment Details TwoColumn used in the edit attachment modal view
        var smushMediaTwoColumn = smush_media.view.Attachment.Details.TwoColumn;

        /**
         * Add Smush details to attachment.
         */
        smush_media.view.Attachment.Details.TwoColumn = smushMediaTwoColumn.extend({

            render: function () {
                // Get Smush status for the image
                this.getSmushDetails(this.model.get('id'));
            },

            getSmushDetails: function (id) {
                wp.ajax.send('smush_get_attachment_details', {
                    data: {
                        _nonce: smush_vars.nonce.get_smush_status,
                        id: id
                    }
                }).done(_.bind(this.renderSmush, this));
            },

            renderSmush: function (response) {
                // Render parent media.view.Attachment.Details
                smushMediaTwoColumn.prototype.render.apply(this);

                this.renderSmushStatus(response);
            },

            renderSmushStatus: function (response) {
                if (!response) {
                    return;
                }
                var $detailsHtml = this.$el.find('.attachment-compat');
                var html = this.generateHTML(response);
                $detailsHtml.append(html);
            },

            generateHTML: function (response) {
                var template = _.template('<label class="setting smush-stats" data-setting="description"><span class="name"><%= label %></span><span class="value"><%= value %></span></label>');
                var html = template({
                    label: smush_vars.strings['stats_label'],
                    value: response
                });

                return html;
            }
        });
    }

    // Local instance of the Attachment Details TwoColumn used in the edit attachment modal view
    var smushAttachmentDetails = smush_media.view.Attachment.Details;

    /**
     * Add Smush details to attachment.
     */
    smush_media.view.Attachment.Details = smushAttachmentDetails.extend( {

        render: function() {
            // Get Smush status for the image
            this.getSmushDetails( this.model.get('id') );
        },

        getSmushDetails: function( id ) {
            wp.ajax.send( 'smush_get_attachment_details', {
                data: {
                    _nonce: smush_vars.nonce.get_smush_status,
                    id: id
                }
            } ).done( _.bind( this.renderSmush, this ) );
        },

        renderSmush: function( response ) {
            // Render parent media.view.Settings.AttachmentDisplay
            smushAttachmentDetails.prototype.render.apply( this );

            this.renderSmushStatus( response );
        },

        renderSmushStatus: function( response ) {
            if ( ! response ) {
                return;
            }
            var $detailsHtml = this.$el.parent().find('.compat-item');
            var html = this.generateHTML( response );
            $detailsHtml.append( html );
        },

        generateHTML: function (response) {
            var template = _.template('<label class="setting smush-stats" data-setting="description"><span class="name"><%= label %></span><span class="value"><%= value %></span></label>');
            var html = template({
                label: smush_vars.strings['stats_label'],
                value: response
            });

            return html;
        }
    } );

})( jQuery, _ );
