GplCart.theme.productUpdateCategories = function (element) {

    var id = element.find('option:selected').val();
    var selectedCatId = ('category_id' in GplCart.settings.product) || '';
    var selectedBrandCatId = ('brand_category_id' in GplCart.settings.product) || '';

    $.get(GplCart.settings.urn, {store_id: id}, function (data) {

        var options = '';

        for (var i in data.catalog) {
            if (selectedCatId === i) {
                options += '<option value="' + i + '" selected>' + data.catalog[i] + '</option>';
            } else {
                options += '<option value="' + i + '">' + data.catalog[i] + '</option>';
            }
        }

        $('select[name$="[category_id]"]').html(options).selectpicker('refresh');

        var options = '';

        for (var i in data.brand) {
            if (selectedBrandCatId === i) {
                options += '<option value="' + i + '" selected>' + data.brand[i] + '</option>';
            } else {
                options += '<option value="' + i + '">' + data.brand[i] + '</option>';
            }
        }

        $('select[name$="[brand_category_id]"]').html(options).selectpicker('refresh');
    });
};

GplCart.theme.productLoadFields = function (classId, fieldType) {

    $.ajax({
        url: GplCart.settings.urn + '?product_class_id=' + classId,
        dataType: 'html',
        success: function (data) {

            var attrForm = $(data).find('div#attribute-form').html();
            var opForm = $(data).find('#option-form').html();

            $('#attribute-form-wrapper').html(attrForm);
            $('#option-form-wrapper').html(opForm);
            $('.selectpicker').selectpicker('show');
        },
        error: function (error) {
            alert(GplCart.text('Unable to load product class fields'));
        }
    });
};

$(function () {

    /**************************************** Product class fields ****************************************/

    $('#product-class-fields tbody').sortable({
        handle: '.handle',
        stop: function () {
            $('input[name$="[weight]"]').each(function (i) {
                $(this).val(i);
                $(this).closest('tr').find('td .weight').text(i);
            });

            GplCart.theme.alert(GplCart.text('Changes will not be saved until the form is submitted'), 'warning');
        }
    });

    $(document).on('click', '#product-class-fields input[name$="[remove]"]', function () {
        $(this).closest('tr').toggleClass('danger', this.checked);
    });

    /**************************************** Product edit form ****************************************/

    $('form#edit-product select[name$="[store_id]"]').change(function () {
        GplCart.theme.productUpdateCategories($(this));
    });

    // Load product class fields
    $('form#edit-product [name$="[product_class_id]"]').change(function () {
        GplCart.theme.productLoadFields($(this).val(), false);
    });

    // Refresh product class fields
    $(document).on('click', '.refresh-fields', function () {
        GplCart.theme.productLoadFields($('[name$="[product_class_id]"]').val(), $(this).attr('data-field-type'));
        return false;
    });

    // Add new option combination
    $(document).on('click', '#option-form-wrapper .add-option-combination', function () {

        var count = $('#option-form-wrapper tbody tr').size() + 1;
        var html = '<tr>';

        $('#option-form-wrapper tfoot select').each(function () {
            html += '<td class="active">';
            html += '<select data-live-search="true" class="form-control selectpicker" name="product[combination][' + count + '][fields][' + $(this).attr('data-field-id') + ']">';
            html += $(this).html();
            html += '</select>';
            html += '</td>';
        });

        html += '<td>';
        html += '<input maxlength="255" class="form-control" name="product[combination][' + count + '][sku]" value="">';
        html += '</td>';
        html += '<td>';
        html += '<input class="form-control" name="product[combination][' + count + '][price]" value="">';
        html += '</td>';
        html += '<td>';
        html += '<input class="form-control" name="product[combination][' + count + '][stock]" value="">';
        html += '</td>';
        html += '<td>';
        html += '<a href="#" onclick="return false;" class="btn btn-default select-image"><i class="fa fa-image"></i></a>';
        html += '<input type="hidden" name="product[combination][' + count + '][file_id]" value="">';
        html += '<input type="hidden" name="product[combination][' + count + '][path]" value="">';
        html += '<input type="hidden" name="product[combination][' + count + '][thumb]" value="">';
        html += '</td>';
        html += '<td>';
        html += '<a href="#" onclick="return false;" class="btn btn-danger btn-default remove-option-combination"><i class="fa fa-trash"></i></a>';
        html += '</td>';
        html += '</tr>';

        $('#option-form-wrapper table tbody').append(html);
        $('.selectpicker').selectpicker();
        return false;
    });

    // Delete option combination
    $(document).on('click', '#option-form-wrapper .remove-option-combination', function () {
        $(this).closest('tr').remove();
        return false;
    });

    // Select image for option combination
    $(document).on('click', '#option-form-wrapper .select-image', function () {

        if ($(this).find('img').length) {
            $(this).html('<i class="fa fa-image"></i>');
            $(this).siblings('input').val('');
            return false;
        }

        var images = 0;

        var html = '<div class="row">';
        $('.image-container').find('.thumb').each(function () {

            var src = $(this).find('img').attr('src');
            var path = $(this).find('input[name$="[path]"]').val();

            html += '<div class="col-md-3">';
            html += '<div class="thumbnail">';
            html += '<img data-file-path="' + path + '" src="' + src + '" class="img-responsive combination-image">';
            html += '</div>';
            html += '</div>';

            images++;
        });

        html += '</div>';

        if (images < 1) {
            return false;
        }

        GplCart.theme.modal(html, 'select-image-modal');

        var position = $(this).closest('tr').index();
        $('#select-image-modal').attr('data-active-row', position); // remember clicked row pos

        $('#select-image-modal img').each(function () {
            var path = $(this).attr('data-file-path');
            if ($('#option-form-wrapper tbody input[name$="[path]"][value="' + path + '"]').size()) {
                $(this).css('opacity', 0.5);
            }
        });

        return false;
    });

    // Set selected image
    $(document).on('click', 'img.combination-image', function () {

        var src = $(this).attr('src');
        var path = $(this).attr('data-file-path');
        var position = $(this).closest('#select-image-modal').attr('data-active-row');
        var element = $('#option-form-wrapper tbody tr').eq(position).find('.select-image');
        var html = '<img style="height:20px; width:20px;" src="' + src + '" class="img-responsive combination-image">';

        element.html(html);
        element.siblings('input[name$="[path]"]').val(path);
        element.siblings('input[name$="[thumb]"]').val(src);

        $('#select-image-modal').modal('hide');
    });

    /*************************** Related products ******************************/

    $('.related-product').autocomplete({
        minLength: 2,
        source: function (request, response) {
            $.post(GplCart.settings.base + 'ajax',
                    {store_id: $('select[name$="[store_id]"] option:selected').val(),
                        status: 1,
                        term: request.term,
                        action: 'getProductsAjax',
                        token: GplCart.settings.token}, function (data) {
                response($.map(data, function (value, key) {
                    return {
                        label: value.title ? value.title + ' (' + value.product_id + ')' : '--',
                        value: value.product_id,
                        url: value.url,
                    };
                }));
            });
        },
        select: function (event, ui) {

            var html = '<span class="related-product-item tag">';
            html += '<input type="hidden" name="product[related][]" value="' + ui.item.value + '">';
            html += '<span class="btn btn-default">';
            html += '<a target="_blank" href="' + ui.item.url + '">' + ui.item.label + '</a> <span class="badge">';
            html += '<i class="fa fa-times remove"></i>';
            html += '</span></span>';
            html += '</span>';

            $('#related-products').append(html);
            $('.related-product').val('');

            return false;
        }

    }).autocomplete("instance")._renderItem = function (ul, item) {
        return $("<li>").append("<a>" + item.label + "</a>").appendTo(ul);
    };

    // Remove related product item
    $(document).on('click', '.related-product-item .remove', function () {
        $(this).closest('.related-product-item').remove();
    });

    /*************************** Product listing ******************************/

    $(document).on('change keyup paste', 'form#products :input', function () {
        $(this).closest('tr').find('.save-row').removeClass('disabled');
    });

    $(document).on('click', 'form#products td .cancel-row', function (e) {
        $(this).closest('tr').remove();
    });

    $(document).on('click', 'form#products td .save-row', function (e) {

        var save = $(this);
        var tr = save.closest('tr');
        var inputs = tr.find(':input');

        var values = inputs.serialize() + '&' + $.param({
            token: GplCart.settings.token,
            save: 1
        });

        var message = GplCart.text('Validation errors');
        var messageType = 'danger';

        $.ajax({
            method: 'POST',
            processData: false,
            url: GplCart.settings.urn,
            dataType: 'json',
            data: values,
            beforeSend: function () {
                inputs.prop('disabled', true);
            },
            success: function (data) {
                if (typeof data === 'object') {
                    if ('success' in data) {
                        message = data.success;
                        messageType = 'success';
                        tr.find('.text_error').remove();
                    } else if ('error' in data) {

                        if (typeof data.error === 'string') {
                            message = data.error;
                            messageType = 'danger';
                        } else {
                            $.each(data.error, function (i, v) {
                                var hint = '<div class="small text_error text-danger">' + v + '</div>';
                                var input = tr.find(':input[name$="[' + i + ']"]');
                                input.nextAll('.help-block').remove();
                                input.after(hint);
                            });
                        }
                    }
                }

                GplCart.theme.alert(message, messageType);
            },
            complete: function () {
                inputs.prop('disabled', false);
                save.addClass('disabled');
            }
        });

        return false;
    });
});