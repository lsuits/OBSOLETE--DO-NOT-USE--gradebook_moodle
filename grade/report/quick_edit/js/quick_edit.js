M.gradereport_quick_edit = {};

M.gradereport_quick_edit.init = function(Y) {
    var toggle = function(check) {
        return function(input) {
            if (check) {
                input.setAttribute('checked', 'CHECKED');
            } else {
                input.removeAttribute('checked');
            }
        };
    };

    // Made toggle links
    Y.all('.include').each(function(link) {
        var type = link.getAttribute('class').split(" ")[2];

        link.on('click', function() {
            Y.all('input[name^=' + type + ']').each(toggle(link.hasClass('all')));
            return false;
        });
    });

    // Override Toggle
    Y.all('input[name^=override_]').each(function(input) {
        input.on('change', function() {
            var checked = input.getDOMNode().checked;
            var names = input.getAttribute('name').split("_");

            var itemid = names[1];
            var userid = names[2];

            var interest = '_' + itemid + '_' + userid;

            Y.all('input[name$=' + interest + ']').filter('input[type=text]').each(function(text) {
                if (!checked) {
                    text.setAttribute('disabled', 'DISABLED');
                } else {
                    text.removeAttribute('disabled');
                }
            });
        });
    });
};
