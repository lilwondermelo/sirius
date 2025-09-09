let isUserAdmin = false;

$(document).ready(function() {
    // Handle login form submission
    $('#login_form').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $.ajax({
            url: 'server/core/_ajaxListener.class.php?action=login',
            type: 'POST',
            data: formData,
            success: function(response) {
                const res = JSON.parse(response);
                if (res.success) {
                    location.reload();
                } else {
                    alert('Ошибка входа: ' + res.error);
                }
            },
            error: function() {
                alert('Произошла ошибка сети.');
            }
        });
    });

    // Handle register form submission
    $('#register_form').on('submit', function(e) {
        e.preventDefault();
        
        const password = $(this).find('input[name="password"]').val();
        const passwordConfirm = $(this).find('input[name="password_confirm"]').val();

        if (password !== passwordConfirm) {
            alert('Пароли не совпадают.');
            return;
        }

        const formData = $(this).serialize();

        $.ajax({
            url: 'server/core/_ajaxListener.class.php?action=register',
            type: 'POST',
            data: formData,
            success: function(response) {
                const res = JSON.parse(response);
                if (res.success) {
                    alert('Регистрация прошла успешно! Теперь вы можете войти.');
                    // Switch to the login tab
                    $('.auth-tab[data-tab="login"]').click();
                } else {
                    alert('Ошибка регистрации: ' + res.error);
                }
            },
            error: function() {
                alert('Произошла ошибка сети.');
            }
        });
    });

    // Handle logout
    $('#logout_btn').on('click', function() {
        $.ajax({
            url: 'server/core/_ajaxListener.class.php?action=logout',
            type: 'POST',
            success: function() {
                location.reload();
            }
        });
    });

    // Function to check auth status on page load
    function checkAuthStatus() {
        return $.ajax({
            url: 'server/core/_ajaxListener.class.php?action=checkAuth',
            type: 'GET',
            success: function(response) {
                const res = JSON.parse(response);
                if (res.loggedIn) {
                    isUserAdmin = (res.role === 'admin');
                    $('#auth_btn').hide(); // Hide the main auth button
                    $('#logout_btn').show();
                    if (isUserAdmin) {
                        $('.admin-feature').show();
                    } else {
                        $('.admin-feature').hide();
                    }
                } else {
                    isUserAdmin = false;
                    $('#auth_btn').show(); // Show the main auth button
                    $('#logout_btn').hide();
                    $('.admin-feature').hide();
                }
            }
        });
    }

    checkAuthStatus().always(function() {
        // Now that we know the user's role, load the main content
        loadData(currentCategory);
        loadMenu();
    });
});