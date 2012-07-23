M.gradereport_quick_edit = {};

M.gradereport_quick_edit.init = function(Y) {
    // Make toggle links
    Y.all('.include').each(function(link) {
        var type = link.getAttribute('class').split(" ")[2];

        var toggle = function(checked) {
            return function(input) {
                input.getDOMNode().checked = checked;
                Y.Event.simulate(input.getDOMNode(), 'change');
            };
        };

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
                text.getDOMNode().disabled = !checked;
            });
        });
    });
};
