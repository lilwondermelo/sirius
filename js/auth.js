let isUserAdmin = false;

$(document).ready(function() {
    // Show login popup
    $('#login_btn').on('click', function() {
        $('#login_popup').show();
    });

    // Show register popup
    $('#register_btn').on('click', function() {
        $('#register_popup').show();
    });

    // Hide popup on click outside
    $('.popup').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });

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
                    $('#register_popup').hide();
                    $('#login_popup').show();
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
                    $('#login_btn').hide();
                    $('#register_btn').hide();
                    $('#logout_btn').show();
                    if (isUserAdmin) {
                        $('.admin-feature').show();
                    } else {
                        $('.admin-feature').hide();
                    }
                } else {
                    isUserAdmin = false;
                    $('#login_btn').show();
                    $('#register_btn').show();
                    $('#logout_btn').hide();
                    $('.admin-feature').hide();
                }
            }
        });
    }

    checkAuthStatus().always(function() {
        // Now that we know the user's role, load the main content
        loadData(0);
        loadMenu();
    });
});