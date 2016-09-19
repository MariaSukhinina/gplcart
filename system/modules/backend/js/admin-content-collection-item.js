(function ($) {

    Backend.include.collection = Backend.include.collection || {attach: {}};

    Backend.include.collection.attach.autocomplete = function () {

        var input = $('form#edit-collection-item input[name$="[input]"]');
        var value = $('form#edit-collection-item input[name$="[value]"]');

        input.autocomplete({
            minLength: 2,
            source: function (request, response) {
                $.post(GplCart.settings.urn, {
                    term: request.term,
                    token: GplCart.settings.token
                }, function (data) {
                    response($.map(data, function (value, key) {
                        return {
                            label: value.title ? value.title + ' (' + key + ')' : '--',
                            value: key
                        }
                    }));
                });
            },
            select: function (event, ui) {
                input.val(ui.item.label);
                value.val(ui.item.value);
                return false;
            },
            search: function () {
                value.val('');
            }
        }).autocomplete("instance")._renderItem = function (ul, item) {
            return $("<li>").append("<a>" + item.label + "</a>").appendTo(ul);
        };
    }

    /**
     * Call attached methods above when DOM is ready
     * @returns {undefined}
     */
    $(function () {
        Backend.init(Backend.include.collection);
    });

})(jQuery);

