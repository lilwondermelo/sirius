<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="ru">
	<head>
		<title>Конструктор данных</title>
		<link rel="stylesheet" href="css/style.css">
		<script src="js/jquery-3.5.1.min.js"></script>
		<script src="https://telegram.org/js/telegram-web-app.js"></script>
		<script src="js/cart.js"></script>
		<script src="js/render.js"></script>
		<script src="js/api.js"></script>
		<script src="js/main.js"></script>
		<script src="js/auth.js"></script>
		<script src="js/event-listener.js"></script>
	</head>
	<body>
		<div class="app_bgr"></div>
		<div id="app_container">
			<header>
				<div class="header-actions">
                    <div class="main_title_button admin-feature" id="admin_panel_btn" style="display: none;">Админ Панель</div>
					<div class="main_title_cart"><img src="media/icons/cart.png" alt=""></div>
                    <div class="main_title_button" id="auth_btn">Авторизация</div>
                    <div class="main_title_button" id="logout_btn" style="display: none;">Выйти</div>
				</div>
				<div class="flex_row main_menu_row" id="main_menu">
					<div class="menu_item" cat-id=0>
						<div class="menu_item_img"><img src="media/images/menu/0.png" alt="">
						</div>
						<div class="menu_item_name">Сириус</div>
					</div>
					<!-- Категории будут загружены сюда динамически -->
				</div>
			</header>
			<div class="flex_row main_table_row" id="main_table">
				
			</div>
            <div id="admin_panel" class="admin-panel" style="display: none;">
                <div class="admin-panel-tabs">
                    <div class="admin-panel-tab active" data-tab="suppliers">Поставщики</div>
                    <div class="admin-panel-tab" data-tab="arrivals">Поступления</div>
                </div>
                <div class="admin-panel-content">
                    <div class="admin-panel-tab-content active" data-tab-content="suppliers"></div>
                    <div class="admin-panel-tab-content" data-tab-content="arrivals"></div>
                </div>
            </div>
            <div id="auth_page" style="display: none;">
                <div class="admin-panel-tabs">
                    <div class="admin-panel-tab auth-tab active" data-tab="login">Вход</div>
                    <div class="admin-panel-tab auth-tab" data-tab="register">Регистрация</div>
                </div>
                <div class="admin-panel-content">
                    <div class="admin-panel-tab-content auth-tab-content active" data-tab-content="login">
                        <form id="login_form" class="auth-form">
                            <h2>Вход</h2>
                            <input type="text" name="login" placeholder="Логин" required>
                            <input type="password" name="password" placeholder="Пароль" required>
                            <button type="submit">Войти</button>
                        </form>
                    </div>
                    <div class="admin-panel-tab-content auth-tab-content" data-tab-content="register">
                        <form id="register_form" class="auth-form">
                            <h2>Регистрация</h2>
                            <input type="text" name="login" placeholder="Логин" required>
                            <input type="password" name="password" placeholder="Пароль" required>
                            <input type="password" name="password_confirm" placeholder="Подтвердите пароль" required>
                            <button type="submit">Зарегистрироваться</button>
                        </form>
                    </div>
                </div>
            </div>
		</div>
		<div class="popup" id="popup">
			<div class="popup_inner"></div>
		</div>
		<div class="tip">
			Подсказка
		</div>
		<script src="js/ready.js"></script>
		<script src="js/supplier.js"></script>
		<script src="js/arrivals.js"></script>
	</body>
</html>