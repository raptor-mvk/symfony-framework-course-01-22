function addFollowerForm($collectionHolder, $newLinkLi) {
    var prototype = $collectionHolder.data('prototype');
    var index = $collectionHolder.data('index');
    var newForm = prototype;
    newForm = newForm.replace(/__name__/g, index);
    $collectionHolder.data('index', index + 1);
    var $newFormLi = $('<li></li>').append(newForm);
    $newLinkLi.before($newFormLi);
}

var $collectionHolder;
var $addFollowerButton = $('<button type="button" class="btn-info btn add_follower_link">Add a follower</button>');
var $newLinkLi = $('<li></li>').append($addFollowerButton);

jQuery(document).ready(function() {
    $collectionHolder = $('ul.followers');
    $collectionHolder.append($newLinkLi);
    $collectionHolder.data('index', $collectionHolder.find('input').length);
    $addFollowerButton.on('click', function(e) {
        addFollowerForm($collectionHolder, $newLinkLi);
    });
});
