document.addEventListener('DOMContentLoaded', function() {
    function setupSearch(searchInputId, listSelector) {
        var search = document.getElementById(searchInputId);
        if (search) {
            search.addEventListener('input', function() {
                var filter = this.value.toLowerCase();
                document.querySelectorAll(listSelector).forEach(function(li) {
                    var text = li.textContent.toLowerCase();
                    li.style.display = text.includes(filter) ? '' : 'none';
                });
            });
        }
    }

    setupSearch('pp-goal-search', '.pp-goal-pages-list li');
    setupSearch('pp-conversion-search', '.pp-conversion-pages-list li');
});