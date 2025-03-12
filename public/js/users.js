document.addEventListener('DOMContentLoaded', function() {
    function loadMoreHandler() {
        const btn = document.getElementById('load-more-btn');
        if (!btn) return;

        const nextUrl = btn.getAttribute('data-next-url');
        if (!nextUrl) return;

        btn.disabled = true;
        btn.innerText = 'Loading...';

        axios.get(nextUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function(response) {
                const data = response.data;
                const grid = document.getElementById('user-grid');

                if (grid && data.html) {
                    grid.insertAdjacentHTML('beforeend', data.html);
                }

                const currentPageElem = document.querySelector('[data-page-id="' + data.current_page + '"]');

                if (currentPageElem) {
                    currentPageElem.outerHTML = `<span data-page-id="${data.current_page}" class="pagination-item p-3 min-w-[48px] text-center
                                                              bg-white bg-yellow-800/50 bg-gradient-to-bl from-yellow-700/50 via-transparent
                                                              dark:ring-1 dark:ring-inset dark:ring-yellow/5 rounded-full shadow-2xl shadow-gray-500/20
                                                              text-gray-700 dark:text-gray-300 hover:bg-yellow-100 dark:hover:bg-gray-700
                                                              transition-colors duration-195">
                                                        ${data.current_page}
                                                    </span>`;
                }
                if (data.next_page_url) {
                    btn.setAttribute('data-next-page', data.current_page + 1);
                    btn.setAttribute('data-next-url', data.next_page_url);
                    btn.disabled = false;
                    btn.innerText = 'Load More';
                } else {
                    btn.style.display = 'none';
                }
            })
            .catch(function(error) {
                console.error(error);
                btn.disabled = false;
                btn.innerText = 'Load More';
            });
    }

    const loadMoreBtn = document.getElementById('load-more-btn');

    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', loadMoreHandler);
    }

    const appContainer = document.getElementById('app');
    const usersStoreRoute = appContainer ? appContainer.getAttribute('data-users-store-route') : '/users';
    const userForm = document.querySelector(`form[action="${usersStoreRoute}"]`);

    if (userForm) {
        userForm.addEventListener('submit', function(e) {
            const token = localStorage.getItem('auth_token');

            if (!token) {
                return;
            }
            e.preventDefault();

            let formData = new FormData(userForm);

            formData.delete('auth_token');

            axios.post('/api/users', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'Authorization': `Bearer ${token}`
                }
            })
                .then(function(response) {
                })
                .catch(function(error) {
                    const errors = error.response.data;
                    console.error(errors);
                    alert(errors.message || 'Error creating user. Please check the form data and try again.');
                });
        });
    }
});
